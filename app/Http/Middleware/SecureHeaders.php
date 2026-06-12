<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de cabeceras HTTP de seguridad.
 *
 * Inyecta cabeceras de seguridad en todas las respuestas
 * para prevenir ataques comunes como clickjacking (X-Frame-Options),
 * MIME sniffing (X-Content-Type-Options), XSS (X-XSS-Protection),
 * y fuga de referrer (Referrer-Policy). También deshabilita el
 * cache para evitar almacenamiento de datos sensibles.
 *
 * @package App\Http\Middleware
 * @standard PHPDoc (PSR-5)
 */
class SecureHeaders
{
    /**
     * Cabeceras de seguridad a inyectar en la respuesta.
     *
     * @var array<string, string>
     */
    protected array $headers = [
        'X-Frame-Options'        => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection'      => '1; mode=block',
        'Referrer-Policy'        => 'strict-origin-when-cross-origin',
        'Cache-Control'          => 'no-store, no-cache, must-revalidate',
        'Pragma'                 => 'no-cache',
    ];

    /**
     * Procesa la request e inyecta cabeceras de seguridad en la respuesta.
     *
     * @param  Request  $request  La petición HTTP entrante.
     * @param  Closure  $next     El siguiente middleware en la cadena.
     * @return Response           La respuesta con cabeceras de seguridad añadidas.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach ($this->headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
