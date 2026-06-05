<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAccessController extends Controller
{
    public function verifyPin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pin' => ['required', 'digits:4'],
        ]);

        $userId = $request->session()->get('pending_user_id');

        if (!$userId) {
            return response()->json([
                'message' => 'No existe una autenticación pendiente.',
            ], 409);
        }

        $user = User::findOrFail($userId);

        if (!$user->admin_pin_hash) {
            return response()->json([
                'message' => 'El PIN todavía no ha sido asignado por el administrador principal.',
            ], 409);
        }

        if (!Hash::check($validated['pin'], $user->admin_pin_hash)) {
            throw ValidationException::withMessages([
                'pin' => ['PIN incorrecto.'],
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget([
            'pending_user_id',
            'pending_mfa_id',
            'pending_role',
            'pending_admin_request_id',
            'auth_stage',
        ]);

        return response()->json([
            'message' => 'Administrador autenticado correctamente.',
            'role' => 'admin',
            'redirect' => $user->isPrimaryAdmin() ? '/admin/identity-requests' : '/admin/dashboard',
        ]);
    }
}