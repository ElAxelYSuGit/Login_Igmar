<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Primario',
            'email' => 'axel.y.ya.0@gmail.com',
            'password' => Hash::make('AdminPrimario#2026'),
            'role' => 'admin',
            'is_primary_admin' => true,
            'admin_pin_hash' => null,
            'admin_verified_once_at' => null,
        ]);

        User::create([
            'name' => 'Admin Regular',
            'email' => 'abrahamghj1@gmail.com',
            'password' => Hash::make('Admin#2026'),
            'role' => 'admin',
            'is_primary_admin' => false,
            'admin_pin_hash' => null,
            'admin_verified_once_at' => null,
        ]);
               User::create([
            'name' => 'Usuario Normal',
            'email' => 'usuario@local.test',
            'password' => Hash::make('Usuario#2026'),
            'role' => 'user',
            'is_primary_admin' => false,
        ]);

        User::create([
            'name' => 'Invitado',
            'email' => 'invitado@local.test',
            'password' => Hash::make('Invitado#2026'),
            'role' => 'guest',
            'is_primary_admin' => false,
        ]);
    }
}