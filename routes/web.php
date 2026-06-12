<?php

use App\Http\Controllers\Auth\AdminAccessController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Admin\AdminApprovalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas (Servicios Abiertos - GUEST)
|--------------------------------------------------------------------------
| Estas rutas son accesibles sin autenticación.
| Incluyen reCAPTCHA y Rate Limiting según requisitos 3 y 10.
*/

// --- Vistas públicas ---
Route::get('/', function () {
    return view('auth.login');
})->name('view.login');

Route::get('/register', function () {
    return view('auth.register');
})->name('view.register');

// --- Endpoints de autenticación (servicios abiertos) ---
Route::post('/auth/login', [AuthController::class, 'login'])->name('custom.login');
Route::post('/auth/register', [RegisterController::class, 'register'])->name('custom.register');
Route::post('/auth/logout', [AuthController::class, 'logout'])->name('custom.logout');

/*
|--------------------------------------------------------------------------
| Rutas de Factores de Autenticación (2FA / 3FA)
|--------------------------------------------------------------------------
| Vistas y endpoints para los múltiples factores de autenticación.
*/

Route::get('/auth/two-factor', function () {
    return view('auth.two-factor');
})->name('view.two-factor');

Route::get('/auth/admin-pin', function () {
    return view('auth.admin-pin');
})->name('view.admin-pin');

Route::post('/auth/two-factor', [TwoFactorController::class, 'verify'])->name('custom.two-factor');
Route::post('/auth/admin-pin', [AdminAccessController::class, 'verifyPin'])->name('custom.admin-pin');

/*
|--------------------------------------------------------------------------
| Rutas del Administrador Primario (3FA - Aprobación de Identidad)
|--------------------------------------------------------------------------
| Solo accesible para el admin primario (is_primary_admin = true).
*/

Route::middleware(['auth', 'primary.admin'])->group(function () {
    Route::get('/admin/identity-requests', [AdminApprovalController::class, 'index'])->name('admin.identity.index');
    Route::post('/admin/identity-requests/decide', [AdminApprovalController::class, 'decide'])->name('admin.identity.decide');
});

/*
|--------------------------------------------------------------------------
| Dashboards por Rol (Componentes Diferentes - Requisito 8)
|--------------------------------------------------------------------------
| Cada rol tiene su propio dashboard con funcionalidades distintas.
| - Guest: Solo lectura (1FA)
| - User: Operativo (2FA)
| - Admin: Control total (3FA)
*/

Route::middleware(['auth', 'role:guest'])->get('/guest/dashboard', function () {
    return view('dashboards.guest');
})->name('dashboard.guest');

Route::middleware(['auth', 'role:user'])->get('/user/dashboard', function () {
    return view('dashboards.user');
})->name('dashboard.user');

Route::middleware(['auth', 'role:admin'])->get('/admin/dashboard', function () {
    return view('dashboards.admin');
})->name('dashboard.admin');