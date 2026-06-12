<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

/**
 * Proveedor de servicios de la aplicación.
 *
 * Configura servicios globales incluyendo las reglas por defecto
 * de validación de contraseñas según el requisito 2 de seguridad.
 *
 * @package App\Providers
 * @standard PHPDoc (PSR-5)
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra servicios en el contenedor de la aplicación.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Inicializa servicios de la aplicación.
     *
     * Configura las reglas globales de validación de contraseña:
     * - Mínimo 8 caracteres
     * - Al menos una mayúscula y una minúscula (mixedCase)
     * - Al menos un número
     * - Al menos un símbolo especial
     * - Verificación contra base de datos de contraseñas comprometidas
     *
     * @return void
     */
    public function boot(): void
    {
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });
    }
}