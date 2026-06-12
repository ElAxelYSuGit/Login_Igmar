<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de sanitización global de datos de entrada.
 *
 * Aplica strip_tags() y trim() a todos los campos de texto
 * del request para prevenir inyección de HTML/XSS.
 * Los campos de contraseña y tokens se excluyen para no
 * alterar caracteres especiales legítimos.
 *
 * @package App\Http\Middleware
 * @standard PHPDoc (PSR-5)
 */
class SanitizeInput
{
    /**
     * Campos que NO deben sanitizarse (contraseñas, tokens).
     *
     * @var array<int, string>
     */
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
        'pin',
        'recaptcha_token',
        '_token',
    ];

    /**
     * Procesa la request sanitizando todos los campos de texto.
     *
     * Itera sobre todos los inputs del request y aplica strip_tags()
     * seguido de trim() a cada valor string que no esté en la lista
     * de exclusión. Los valores no-string se dejan intactos.
     *
     * @param  Request  $request  La petición HTTP entrante.
     * @param  Closure  $next     El siguiente middleware en la cadena.
     * @return Response           La respuesta HTTP resultante.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        array_walk_recursive($input, function (&$value, $key) {
            if (!in_array($key, $this->except, true) && is_string($value)) {
                $value = trim(strip_tags($value));
            }
        });

        $request->merge($input);

        return $next($request);
    }
}
