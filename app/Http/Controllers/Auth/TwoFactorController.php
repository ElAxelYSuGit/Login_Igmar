<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminAccessRequest;
use App\Models\MfaChallenge;
use App\Models\User;
use App\Notifications\AdminThirdFactorRequested;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Controlador de verificación de segundo factor (2FA) por email OTP.
 *
 * Valida el código OTP de 6 dígitos enviado al correo del usuario.
 * Según el rol, redirige al dashboard (user) o al siguiente factor
 * de autenticación (admin → identidad presencial + PIN).
 *
 * @package App\Http\Controllers\Auth
 * @standard PHPDoc (PSR-5)
 */
class TwoFactorController extends Controller
{
    /**
     * Verifica el código OTP del segundo factor de autenticación.
     *
     * Flujo:
     * 1. Recupera la sesión pendiente (pending_user_id, pending_mfa_id).
     * 2. Valida que el challenge MFA exista, no esté expirado ni consumido.
     * 3. Compara el código OTP ingresado contra el hash almacenado.
     * 4. Si es usuario: autentica y redirige al dashboard.
     * 5. Si es admin sin verificación previa: genera código de identidad
     *    y notifica al admin primario (3FA presencial).
     * 6. Si es admin ya verificado: redirige al formulario de PIN.
     *
     * @param  Request  $request  Debe contener: code (6 dígitos).
     * @return JsonResponse       Respuesta con redirect, next_step o
     *                            identity_code según el flujo.
     *
     * @throws ValidationException Si el código OTP es incorrecto.
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'El código OTP es obligatorio.',
            'code.digits'   => 'El código debe ser de exactamente 6 dígitos.',
        ]);

        $userId = $request->session()->get('pending_user_id');
        $mfaId = $request->session()->get('pending_mfa_id');

        if (!$userId || !$mfaId) {
            Log::warning('2FA: Intento de verificación sin sesión pendiente', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'No existe una autenticación pendiente.',
            ], 409);
        }

        $user = User::findOrFail($userId);

        $challenge = MfaChallenge::where('id', $mfaId)
            ->where('user_id', $user->id)
            ->where('type', 'email_otp')
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$challenge) {
            Log::channel('audit')->warning('Código OTP expirado o inválido', [
                'que'     => 'Intento de 2FA con código expirado',
                'quien'   => $user->email,
                'cuando'  => now()->toDateTimeString(),
                'donde'   => $request->ip(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'El código expiró o ya no es válido.',
            ], 409);
        }

        if ($challenge->attempts >= 5) {
            Log::channel('audit')->warning('Demasiados intentos de OTP', [
                'que'          => 'Se superó el máximo de intentos de OTP (5)',
                'quien'        => $user->email,
                'cuando'       => now()->toDateTimeString(),
                'donde'        => $request->ip(),
                'challenge_id' => $challenge->id,
            ]);

            return response()->json([
                'message' => 'Demasiados intentos para este código.',
            ], 429);
        }

        if (!Hash::check($validated['code'], $challenge->code_hash)) {
            $challenge->increment('attempts');

            Log::channel('audit')->warning('Código OTP incorrecto', [
                'que'      => 'Código OTP ingresado incorrectamente',
                'quien'    => $user->email,
                'cuando'   => now()->toDateTimeString(),
                'donde'    => $request->ip(),
                'intento'  => $challenge->attempts,
            ]);

            throw ValidationException::withMessages([
                'code' => ['Código incorrecto.'],
            ]);
        }

        // --- OTP verificado correctamente ---
        $challenge->update([
            'verified_at' => now(),
            'consumed_at' => now(),
        ]);

        Log::info('2FA: Código OTP verificado correctamente', [
            'user_id'      => $user->id,
            'challenge_id' => $challenge->id,
        ]);

        // --- USUARIO: autenticación completa (2FA) ---
        if ($user->isUser()) {
            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->forget([
                'pending_user_id',
                'pending_mfa_id',
                'pending_role',
                'auth_stage',
            ]);

            Log::channel('audit')->info('Login exitoso (user, 2FA)', [
                'que'     => 'Usuario autenticado con 2FA completo',
                'quien'   => $user->email,
                'cuando'  => now()->toDateTimeString(),
                'donde'   => $request->ip(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message'  => 'Usuario autenticado correctamente.',
                'role'     => 'user',
                'redirect' => '/user/dashboard',
            ]);
        }

        // --- ADMIN: tercer factor requerido (3FA) ---
        if ($user->isAdmin()) {
            // Admin sin verificación previa → identidad presencial
            if ($user->requiresAdminIdentityVerification()) {
                $identityCode = (string) random_int(100000, 999999);

                $adminRequest = AdminAccessRequest::create([
                    'user_id'           => $user->id,
                    'mfa_challenge_id'  => $challenge->id,
                    'request_code_hash' => Hash::make($identityCode),
                    'status'            => 'pending',
                    'expires_at'        => now()->addMinutes(15),
                ]);

                $primaryAdmin = User::where('role', 'admin')
                    ->where('is_primary_admin', true)
                    ->first();

                if ($primaryAdmin) {
                    $primaryAdmin->notify(new AdminThirdFactorRequested($user, 15));
                }

                $request->session()->put([
                    'pending_admin_request_id' => $adminRequest->id,
                    'auth_stage'               => 'admin_identity_pending',
                ]);

                Log::channel('audit')->info('Solicitud de identidad 3FA creada', [
                    'que'        => 'Admin requiere verificación de identidad presencial',
                    'quien'      => $user->email,
                    'cuando'     => now()->toDateTimeString(),
                    'donde'      => $request->ip(),
                    'request_id' => $adminRequest->id,
                ]);

                return response()->json([
                    'message'       => 'Segundo factor correcto. Muestra este código al administrador principal.',
                    'identity_code' => $identityCode,
                    'next_step'     => '/admin/identity-requests',
                ]);
            }

            // Admin ya verificado → solicitar PIN
            $request->session()->put([
                'auth_stage' => 'admin_pin_pending',
            ]);

            Log::info('2FA: Admin redirigido a PIN', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message'   => 'Segundo factor correcto. Ingresa tu PIN.',
                'next_step' => '/auth/admin-pin',
            ]);
        }

        Log::error('2FA: Rol no soportado para el flujo de autenticación', [
            'user_id' => $user->id,
            'role'    => $user->role,
        ]);

        return response()->json([
            'message' => 'Rol no soportado para este flujo.',
        ], 403);
    }
}