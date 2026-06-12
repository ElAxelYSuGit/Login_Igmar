<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de verificación de rol de usuario.
 *
 * Restringe el acceso a rutas específicas según el rol del usuario
 * autenticado. Acepta uno o más roles como parámetros y aborta
 * con 403 si el usuario no tiene ninguno de los roles permitidos.
 *
 * Uso en rutas: ->middleware('role:guest') o ->middleware('role:admin,user')
 *
 * @package App\Http\Middleware
 * @standard PHPDoc (PSR-5)
 */
class EnsureUserRole
{
    /**
     * Verifica que el usuario autenticado tenga uno de los roles permitidos.
     *
     * @param  Request   $request  La petición HTTP entrante.
     * @param  Closure   $next     El siguiente middleware en la cadena.
     * @param  string    ...$roles Roles permitidos (ej: 'guest', 'user', 'admin').
     * @return Response            La respuesta HTTP si el rol es válido.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *         Error 403 si el usuario no tiene el rol requerido.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles, true)) {
            abort(403, 'No autorizado.');
        }

        return $next($request);
    }
}