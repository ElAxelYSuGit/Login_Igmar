<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Cambiamos 'login' por 'view.login' para que apunte a tu interfaz visual
        return $request->expectsJson() ? null : route('view.login');
    }
}