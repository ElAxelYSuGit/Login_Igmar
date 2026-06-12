<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de verificación de administrador primario.
 *
 * Restringe el acceso exclusivamente al administrador primario
 * (is_primary_admin = true). Se usa para proteger rutas sensibles
 * como el panel de aprobación de identidades (3FA).
 *
 * Uso en rutas: ->middleware('primary.admin')
 *
 * @package App\Http\Middleware
 * @standard PHPDoc (PSR-5)
 */
class EnsurePrimaryAdmin
{
    /**
     * Verifica que el usuario sea el administrador primario.
     *
     * @param  Request  $request  La petición HTTP entrante.
     * @param  Closure  $next     El siguiente middleware en la cadena.
     * @return Response           La respuesta HTTP si es admin primario.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *         Error 403 si el usuario no es el admin primario.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isPrimaryAdmin()) {
            abort(403, 'Solo el administrador principal puede entrar aquí.');
        }

        return $next($request);
    }
}