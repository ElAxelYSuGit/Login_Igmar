<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder de usuarios por defecto del sistema.
 *
 * Crea los usuarios iniciales con los roles predefinidos:
 * - Admin primario (Axel): rol admin, is_primary_admin=true, PIN 0000.
 * - Admin secundario (Abraham): rol admin, sin PIN (pendiente de verificación).
 * - Usuario operativo: rol user, acceso 2FA.
 * - Invitado: rol guest, acceso 1FA.
 *
 * Las contraseñas cumplen con los requisitos de seguridad:
 * min 8 chars, mayúsculas, minúsculas, números y símbolos.
 *
 * @package Database\Seeders
 * @standard PHPDoc (PSR-5)
 */
class UserSeeder extends Seeder
{
    /**
     * Ejecuta el seeder y crea los usuarios por defecto.
     *
     * @return void
     */
    public function run(): void
    {
        User::create([
            'name' => 'Axel',
            'email' => 'axel.y.ya.0@gmail.com',
            'password' => Hash::make('AdminPrimario#2026'),
            'role' => 'admin',
            'is_primary_admin' => true,
            'admin_pin_hash' => Hash::make('0000'),
            'admin_verified_once_at' => now(),
        ]);

        User::create([
            'name' => 'Abraham',
            'email' => 'theryde05@gmail.com',
            'password' => Hash::make('Admin#2026'),
            'role' => 'admin',
            'is_primary_admin' => false,
            'admin_pin_hash' => null,
            'admin_verified_once_at' => null,
        ]);

        User::create([
            'name' => 'Usuario Normal',
            'email' => 'ortomediq@gmail.com',
            'password' => Hash::make('Usuario#2026'),
            'role' => 'user',
            'is_primary_admin' => false,
        ]);

        User::create([
            'name' => 'Invitado',
            'email' => 'axelyspoti2@gmail.com',
            'password' => Hash::make('Invitado#2026'),
            'role' => 'guest',
            'is_primary_admin' => false,
        ]);
    }
}