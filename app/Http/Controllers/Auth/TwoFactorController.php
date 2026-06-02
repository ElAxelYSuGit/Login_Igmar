<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminAccessRequest;
use App\Models\MfaChallenge;
use App\Models\User;
use App\Notifications\AdminThirdFactorRequested;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $userId = $request->session()->get('pending_user_id');
        $mfaId = $request->session()->get('pending_mfa_id');

        if (!$userId || !$mfaId) {
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
            return response()->json([
                'message' => 'El código expiró o ya no es válido.',
            ], 409);
        }

        if ($challenge->attempts >= 5) {
            return response()->json([
                'message' => 'Demasiados intentos para este código.',
            ], 429);
        }

        if (!Hash::check($validated['code'], $challenge->code_hash)) {
            $challenge->increment('attempts');

            throw ValidationException::withMessages([
                'code' => ['Código incorrecto.'],
            ]);
        }

        $challenge->update([
            'verified_at' => now(),
            'consumed_at' => now(),
        ]);

        if ($user->isUser()) {
            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->forget([
                'pending_user_id',
                'pending_mfa_id',
                'pending_role',
                'auth_stage',
            ]);

            return response()->json([
                'message' => 'Usuario autenticado correctamente.',
                'role' => 'user',
                'redirect' => '/user/dashboard',
            ]);
        }

        if ($user->isAdmin()) {
            if ($user->requiresAdminIdentityVerification()) {
                $identityCode = (string) random_int(100000, 999999);

                $adminRequest = AdminAccessRequest::create([
                    'user_id' => $user->id,
                    'mfa_challenge_id' => $challenge->id,
                    'request_code_hash' => Hash::make($identityCode),
                    'status' => 'pending',
                    'expires_at' => now()->addMinutes(15),
                ]);

                $primaryAdmin = User::where('role', 'admin')
                    ->where('is_primary_admin', true)
                    ->first();

                if ($primaryAdmin) {
                    $primaryAdmin->notify(new AdminThirdFactorRequested($user, 15));
                }

                $request->session()->put([
                    'pending_admin_request_id' => $adminRequest->id,
                    'auth_stage' => 'admin_identity_pending',
                ]);

                return response()->json([
                    'message' => 'Segundo factor correcto. Muestra este código al administrador principal.',
                    'identity_code' => $identityCode,
                    'next_step' => '/admin/identity-requests',
                ]);
            }

            $request->session()->put([
                'auth_stage' => 'admin_pin_pending',
            ]);

            return response()->json([
                'message' => 'Segundo factor correcto. Ingresa tu PIN.',
                'next_step' => '/auth/admin-pin',
            ]);
        }

        return response()->json([
            'message' => 'Rol no soportado para este flujo.',
        ], 403);
        
    }
}