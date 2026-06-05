<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MfaChallenge;
use App\Models\User;
use App\Notifications\EmailOtpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
   public function login(Request $request): JsonResponse
{
    $validated = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
        'recaptcha_token' => ['required', 'string'],
    ]);

    $recaptchaResponse = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
        'secret' => config('services.recaptcha.secret'),
        'response' => $validated['recaptcha_token'],
        'remoteip' => $request->ip()
    ]);

    if (!$recaptchaResponse->json('success')) {
        throw ValidationException::withMessages([
            'email' => ['Por favor completa el reCAPTCHA correctamente.'],
        ]);
    }

    $key = strtolower($validated['email']) . '|' . $request->ip();

    if (RateLimiter::tooManyAttempts($key, 5)) {
        $seconds = RateLimiter::availableIn($key);

        return response()->json([
            'message' => "Demasiados intentos. Intenta de nuevo en {$seconds} segundos.",
        ], 429);
    }

    $user = User::where('email', $validated['email'])->first();

    if (!$user || !Hash::check($validated['password'], $user->password)) {
        RateLimiter::hit($key, 60);

        throw ValidationException::withMessages([
            'email' => ['Las credenciales no son correctas.'],
        ]);
    }

    RateLimiter::clear($key);

    // LIMPIAR cualquier sesión pendiente previa
    $request->session()->forget([
        'pending_user_id',
        'pending_mfa_id',
        'pending_role',
        'pending_admin_request_id',
        'auth_stage',
    ]);

    // INVITADO: acceso directo
    if ($user->isGuest()) {
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Acceso de invitado concedido.',
            'role' => 'guest',
            'redirect' => '/guest/dashboard',
        ]);
    }

    // USUARIO y ADMIN NORMAL: envían OTP por email
    $otp = (string) random_int(100000, 999999);

    $challenge = MfaChallenge::create([
        'user_id' => $user->id,
        'type' => 'email_otp',
        'code_hash' => Hash::make($otp),
        'attempts' => 0,
        'expires_at' => now()->addMinutes(10),
    ]);

    $user->notify(new EmailOtpNotification($otp, 10));

    $request->session()->put([
        'pending_user_id' => $user->id,
        'pending_mfa_id' => $challenge->id,
        'pending_role' => $user->role,
        'auth_stage' => 'email_otp_pending',
    ]);

    return response()->json([
        'message' => 'Se envió el código al correo.',
        'next_step' => '/auth/two-factor',
        'role' => $user->role,
    ]);
}
    public function logout(Request $request): JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}