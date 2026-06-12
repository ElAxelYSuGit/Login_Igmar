<?php

namespace App\Actions\Fortify;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Trait de reglas de validación de contraseña.
 *
 * Define las reglas de validación que se aplican a las contraseñas
 * en todo el sistema. Utiliza Password::default() que se configura
 * en AppServiceProvider con: mínimo 8 caracteres, mayúsculas y
 * minúsculas, números, símbolos y verificación en breaches.
 *
 * @package App\Actions\Fortify
 * @standard PHPDoc (PSR-5)
 */
trait PasswordValidationRules
{
    /**
     * Obtiene las reglas de validación para contraseñas.
     *
     * Las reglas incluyen: required, string, las reglas por defecto
     * de Password (min 8 + mixedCase + numbers + symbols + uncompromised),
     * y confirmación (password_confirmation).
     *
     * @return array<int, Rule|array<mixed>|string> Array de reglas de validación.
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', Password::default(), 'confirmed'];
    }
}
