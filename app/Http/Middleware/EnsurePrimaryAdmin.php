<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePrimaryAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isPrimaryAdmin()) {
            abort(403, 'Solo el administrador principal puede entrar aquí.');
        }

        return $next($request);
    }
}