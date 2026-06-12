<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MfaChallenge;
use App\Models\User;
use App\Notifications\EmailOtpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Controlador de autenticación principal (Login / Logout).
 *
 * Gestiona el inicio y cierre de sesión para los tres roles del sistema.
 * Implementa reCAPTCHA (requisito 3), rate limiting (requisito 10),
 * logs de desarrollo y auditoría (requisitos 6 y 7), y manejo seguro
 * de sesión con regeneración de token (requisito 15).
 *
 * Flujo por rol:
 * - Guest (1FA): credenciales + reCAPTCHA → acceso directo.
 * - User  (2FA): credenciales + reCAPTCHA → OTP por email.
 * - Admin (3FA): credenciales + reCAPTCHA → OTP → identidad/PIN.
 *
 * @package App\Http\Controllers\Auth
 * @standard PHPDoc (PSR-5)
 */
class AuthController extends Controller
{
    /**
     * Procesa el inicio de sesión del usuario.
     *
     * Valida credenciales, verifica reCAPTCHA, aplica rate limiting
     * y redirige según el rol del usuario al siguiente factor de
     * autenticación o al dashboard correspondiente.
     *
     * @param  Request  $request  Debe contener: email, password, recaptcha_token.
     * @return JsonResponse       Respuesta JSON con redirect o next_step según
     *                            el rol (guest→dashboard, user/admin→2FA).
     *
     * @throws ValidationException Si las credenciales son incorrectas
     *                             o el reCAPTCHA falla.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'           => ['required', 'email'],
            'password'        => ['required', 'string'],
            'recaptcha_token' => ['required', 'string'],
        ], [
            'email.required'           => 'El correo electrónico es obligatorio.',
            'email.email'              => 'Ingresa un correo electrónico válido.',
            'password.required'        => 'La contraseña es obligatoria.',
            'recaptcha_token.required'  => 'Completa la verificación reCAPTCHA.',
        ]);

        // --- Verificación de reCAPTCHA ---
        Log::debug('Login: Verificando reCAPTCHA', [
            'email' => $validated['email'],
            'ip'    => $request->ip(),
        ]);

        $recaptchaResponse = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => config('services.recaptcha.secret'),
            'response' => $validated['recaptcha_token'],
            'remoteip' => $request->ip(),
        ]);

        if (!$recaptchaResponse->json('success')) {
            Log::warning('Login: reCAPTCHA inválido', [
                'email' => $validated['email'],
                'ip'    => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => ['Por favor completa el reCAPTCHA correctamente.'],
            ]);
        }

        // --- Rate Limiting: máximo 5 intentos por email+IP por minuto ---
        $key = strtolower($validated['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            Log::channel('audit')->warning('Rate limit alcanzado en login', [
                'que'    => 'Intento de login bloqueado por rate limit',
                'quien'  => $validated['email'],
                'cuando' => now()->toDateTimeString(),
                'donde'  => $request->ip(),
            ]);

            return response()->json([
                'message' => "Demasiados intentos. Intenta de nuevo en {$seconds} segundos.",
            ], 429);
        }

        // --- Verificación de credenciales ---
        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            Log::channel('audit')->warning('Intento de login fallido', [
                'que'    => 'Credenciales incorrectas',
                'quien'  => $validated['email'],
                'cuando' => now()->toDateTimeString(),
                'donde'  => $request->ip(),
            ]);

            Log::warning('Login: Credenciales incorrectas', [
                'email' => $validated['email'],
                'ip'    => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => ['Las credenciales no son correctas.'],
            ]);
        }

        RateLimiter::clear($key);

        // --- Limpieza de sesión pendiente previa ---
        $request->session()->forget([
            'pending_user_id',
            'pending_mfa_id',
            'pending_role',
            'pending_admin_request_id',
            'auth_stage',
        ]);

        // --- INVITADO: acceso directo (1FA) ---
        if ($user->isGuest()) {
            Auth::login($user);
            $request->session()->regenerate();

            Log::channel('audit')->info('Login exitoso (guest)', [
                'que'     => 'Inicio de sesión de invitado (1FA)',
                'quien'   => $user->email,
                'cuando'  => now()->toDateTimeString(),
                'donde'   => $request->ip(),
                'user_id' => $user->id,
            ]);

            Log::info('Login: Acceso de invitado concedido', [
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);

            return response()->json([
                'message'  => 'Acceso de invitado concedido.',
                'role'     => 'guest',
                'redirect' => '/guest/dashboard',
            ]);
        }

        // --- USUARIO y ADMIN: envían OTP por email (2FA) ---
        $otp = (string) random_int(100000, 999999);

        $challenge = MfaChallenge::create([
            'user_id'    => $user->id,
            'type'       => 'email_otp',
            'code_hash'  => Hash::make($otp),
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $user->notify(new EmailOtpNotification($otp, 10));

        $request->session()->put([
            'pending_user_id' => $user->id,
            'pending_mfa_id'  => $challenge->id,
            'pending_role'    => $user->role,
            'auth_stage'      => 'email_otp_pending',
        ]);

        Log::channel('audit')->info('OTP enviado por email', [
            'que'          => 'Código OTP generado y enviado para 2FA',
            'quien'        => $user->email,
            'cuando'       => now()->toDateTimeString(),
            'donde'        => $request->ip(),
            'user_id'      => $user->id,
            'challenge_id' => $challenge->id,
        ]);

        Log::info('Login: OTP enviado', [
            'user_id'      => $user->id,
            'challenge_id' => $challenge->id,
            'role'         => $user->role,
        ]);

        return response()->json([
            'message'   => 'Se envió el código al correo.',
            'next_step' => '/auth/two-factor',
            'role'      => $user->role,
        ]);
    }

    /**
     * Cierra la sesión del usuario autenticado.
     *
     * Invalida la sesión actual, regenera el token CSRF y
     * registra el evento de cierre de sesión en los logs de auditoría.
     *
     * @param  Request  $request  La petición HTTP actual.
     * @return JsonResponse       Confirmación de cierre de sesión.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            Log::channel('audit')->info('Cierre de sesión', [
                'que'     => 'Usuario cerró sesión',
                'quien'   => $user->email,
                'cuando'  => now()->toDateTimeString(),
                'donde'   => $request->ip(),
                'user_id' => $user->id,
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('Logout: Sesión cerrada correctamente');

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}