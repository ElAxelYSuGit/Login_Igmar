<?php

use App\Http\Controllers\Auth\AdminAccessController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Admin\AdminApprovalController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->name('custom.login');
Route::post('/auth/logout', [AuthController::class, 'logout'])->name('custom.logout');

Route::post('/auth/two-factor', [TwoFactorController::class, 'verify'])->name('custom.two-factor');
Route::post('/auth/admin-pin', [AdminAccessController::class, 'verifyPin'])->name('custom.admin-pin');

Route::middleware(['auth', 'primary.admin'])->group(function () {
    Route::get('/admin/identity-requests', [AdminApprovalController::class, 'index'])->name('admin.identity.index');
    Route::post('/admin/identity-requests/decide', [AdminApprovalController::class, 'decide'])->name('admin.identity.decide');
});

Route::middleware(['auth', 'role:guest'])->get('/guest/dashboard', function () {
    return response()->json(['message' => 'Bienvenido invitado.']);
});

Route::middleware(['auth', 'role:user'])->get('/user/dashboard', function () {
    return response()->json(['message' => 'Bienvenido usuario.']);
});

Route::middleware(['auth', 'role:admin'])->get('/admin/dashboard', function () {
    return response()->json(['message' => 'Bienvenido administrador.']);
});

// Vistas del Frontend
Route::get('/', function () {
    return view('auth.login');
})->name('view.login');

Route::get('/auth/two-factor', function () {
    return view('auth.two-factor');
})->name('view.two-factor');

Route::get('/auth/admin-pin', function () {
    return view('auth.admin-pin');
})->name('view.admin-pin');


Route::middleware(['auth', 'primary.admin'])->group(function () {
    Route::get('/admin/identity-requests', [AdminApprovalController::class, 'index'])->name('admin.identity.index');
    Route::post('/admin/identity-requests/decide', [AdminApprovalController::class, 'decide'])->name('admin.identity.decide');
});

// --------------------------------------------------------
// 4. RUTAS DE LOS DASHBOARDS (Vistas finales post-login)
// --------------------------------------------------------
Route::middleware(['auth', 'role:guest'])->get('/guest/dashboard', function () {
    return view('dashboards.guest'); // Antes retornaba JSON
});

Route::middleware(['auth', 'role:user'])->get('/user/dashboard', function () {
    return view('dashboards.user'); // Antes retornaba JSON
});

Route::middleware(['auth', 'role:admin'])->get('/admin/dashboard', function () {
    return view('dashboards.admin'); // Antes retornaba JSON
});