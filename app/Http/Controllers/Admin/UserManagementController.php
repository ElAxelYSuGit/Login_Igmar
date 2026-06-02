<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            // Exigimos contraseñas seguras por defecto
            'password' => ['required', Password::defaults()], 
            'role' => ['required', 'in:guest,user,admin'],
        ]);

        // Creamos al usuario. Nadie creado por esta vía será "Primary Admin"
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_primary_admin' => false,
            // Inicializamos los campos del 3FA en nulo para los nuevos Admins Regulares
            'admin_pin_hash' => null,
            'admin_verified_once_at' => null,
        ]);

        return response()->json([
            'message' => 'El usuario ' . $user->name . ' fue registrado exitosamente con acceso de ' . strtoupper($user->role) . '.',
        ]);
    }
}