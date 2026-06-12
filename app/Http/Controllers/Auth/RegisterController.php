<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Controlador de registro público de cuentas Guest.
 *
 * Gestiona la creación de nuevas cuentas de tipo "guest" a través
 * de la interfaz pública. Incluye verificación de reCAPTCHA,
 * rate limiting, sanitización de datos y logs de auditoría.
 *
 * @package App\Http\Controllers\Auth
 * @standard PHPDoc (PSR-5)
 */
class RegisterController extends Controller
{
    /**
     * Registra una nueva cuenta de usuario con rol guest.
     *
     * Flujo:
     * 1. Valida el token reCAPTCHA contra la API de Google.
     * 2. Aplica rate limiting (5 intentos por IP por minuto).
     * 3. Valida los datos del formulario (nombre, email, password).
     * 4. Crea el usuario con rol 'guest' y password encriptado con bcrypt.
     * 5. Registra el evento en los logs de auditoría y desarrollo.
     *
     * @param  Request  $request  Debe contener: name, email, password,
     *                            password_confirmation, recaptcha_token.
     * @return JsonResponse       Respuesta JSON con mensaje de éxito (201)
     *                            o error (422, 429).
     *
     * @throws ValidationException Si los datos no pasan la validación
     *                             o el reCAPTCHA falla.
     */
    public function register(Request $request): JsonResponse
    {
        // --- Rate Limiting: máximo 5 registros por IP por minuto ---
        $rateLimitKey = 'register|' . $request->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            Log::channel('audit')->warning('Rate limit alcanzado en registro', [
                'que'    => 'Intento de registro bloqueado por rate limit',
                'quien'  => $request->input('email', 'desconocido'),
                'cuando' => now()->toDateTimeString(),
                'donde'  => $request->ip(),
            ]);

            return response()->json([
                'message' => "Demasiados intentos de registro. Intenta de nuevo en {$seconds} segundos.",
            ], 429);
        }

        // --- Validación de reCAPTCHA ---
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'string', Password::defaults(), 'confirmed'],
            'recaptcha_token'       => ['required', 'string'],
        ], [
            'name.required'         => 'El nombre es obligatorio.',
            'name.max'              => 'El nombre no puede exceder 255 caracteres.',
            'email.required'        => 'El correo electrónico es obligatorio.',
            'email.email'           => 'Ingresa un correo electrónico válido.',
            'email.unique'          => 'Este correo ya está registrado.',
            'password.required'     => 'La contraseña es obligatoria.',
            'password.confirmed'    => 'Las contraseñas no coinciden.',
            'recaptcha_token.required' => 'Completa la verificación reCAPTCHA.',
        ]);

        Log::debug('Registro: Iniciando validación de reCAPTCHA', [
            'email' => $validated['email'],
            'ip'    => $request->ip(),
        ]);

        $recaptchaResponse = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => config('services.recaptcha.secret'),
            'response' => $validated['recaptcha_token'],
            'remoteip' => $request->ip(),
        ]);

        if (!$recaptchaResponse->json('success')) {
            Log::warning('Registro: reCAPTCHA inválido', [
                'email' => $validated['email'],
                'ip'    => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'recaptcha_token' => ['Por favor completa el reCAPTCHA correctamente.'],
            ]);
        }

        RateLimiter::hit($rateLimitKey, 60);

        // --- Creación de la cuenta guest ---
        Log::debug('Registro: Creando cuenta guest', ['email' => $validated['email']]);

        $user = User::create([
            'name'             => $validated['name'],
            'email'            => $validated['email'],
            'password'         => Hash::make($validated['password']),
            'role'             => 'guest',
            'is_primary_admin' => false,
        ]);

        // --- Logs de auditoría ---
        Log::channel('audit')->info('Registro de cuenta guest exitoso', [
            'que'    => 'Nuevo usuario registrado con rol guest',
            'quien'  => $user->email,
            'cuando' => now()->toDateTimeString(),
            'donde'  => $request->ip(),
            'user_id' => $user->id,
        ]);

        Log::info('Registro: Cuenta guest creada exitosamente', [
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        return response()->json([
            'message' => 'Cuenta creada exitosamente. Ya puedes iniciar sesión.',
        ], 201);
    }
}
