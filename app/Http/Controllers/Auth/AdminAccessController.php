<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Controlador de verificación de PIN administrativo (3er factor).
 *
 * Valida el PIN de 4 dígitos asignado por el administrador primario
 * como tercer y último factor de autenticación para admins.
 * El PIN es permanente y se almacena hasheado en la base de datos.
 *
 * @package App\Http\Controllers\Auth
 * @standard PHPDoc (PSR-5)
 */
class AdminAccessController extends Controller
{
    /**
     * Verifica el PIN del administrador como tercer factor de autenticación.
     *
     * Flujo:
     * 1. Recupera el pending_user_id de la sesión.
     * 2. Verifica que el usuario tenga un PIN asignado.
     * 3. Compara el PIN ingresado contra el hash almacenado.
     * 4. Si es correcto: autentica, regenera sesión, limpia datos pendientes.
     * 5. Redirige al dashboard de admin o panel de identity-requests (primario).
     *
     * @param  Request  $request  Debe contener: pin (4 dígitos).
     * @return JsonResponse       Respuesta con redirect al dashboard
     *                            correspondiente del admin.
     *
     * @throws ValidationException Si el PIN es incorrecto.
     */
    public function verifyPin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pin' => ['required', 'digits:4'],
        ], [
            'pin.required' => 'El PIN es obligatorio.',
            'pin.digits'   => 'El PIN debe ser de exactamente 4 dígitos.',
        ]);

        $userId = $request->session()->get('pending_user_id');

        if (!$userId) {
            Log::warning('AdminPIN: Intento de verificación sin sesión pendiente', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'No existe una autenticación pendiente.',
            ], 409);
        }

        $user = User::findOrFail($userId);

        if (!$user->admin_pin_hash) {
            Log::channel('audit')->warning('PIN no asignado aún', [
                'que'     => 'Intento de acceso admin sin PIN asignado',
                'quien'   => $user->email,
                'cuando'  => now()->toDateTimeString(),
                'donde'   => $request->ip(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'El PIN todavía no ha sido asignado por el administrador principal.',
            ], 409);
        }

        if (!Hash::check($validated['pin'], $user->admin_pin_hash)) {
            Log::channel('audit')->warning('PIN incorrecto', [
                'que'     => 'PIN de administrador ingresado incorrectamente',
                'quien'   => $user->email,
                'cuando'  => now()->toDateTimeString(),
                'donde'   => $request->ip(),
                'user_id' => $user->id,
            ]);

            throw ValidationException::withMessages([
                'pin' => ['PIN incorrecto.'],
            ]);
        }

        // --- Autenticación completa (3FA) ---
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget([
            'pending_user_id',
            'pending_mfa_id',
            'pending_role',
            'pending_admin_request_id',
            'auth_stage',
        ]);

        Log::channel('audit')->info('Login exitoso (admin, 3FA)', [
            'que'     => 'Administrador autenticado con 3FA completo',
            'quien'   => $user->email,
            'cuando'  => now()->toDateTimeString(),
            'donde'   => $request->ip(),
            'user_id' => $user->id,
            'is_primary' => $user->isPrimaryAdmin(),
        ]);

        Log::info('AdminPIN: Administrador autenticado correctamente', [
            'user_id'    => $user->id,
            'is_primary' => $user->isPrimaryAdmin(),
        ]);

        return response()->json([
            'message'  => 'Administrador autenticado correctamente.',
            'role'     => 'admin',
            'redirect' => $user->isPrimaryAdmin() ? '/admin/identity-requests' : '/admin/dashboard',
        ]);
    }
}