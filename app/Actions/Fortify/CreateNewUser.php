<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Acción de Fortify para crear nuevos usuarios.
 *
 * Valida los datos de entrada y crea un nuevo registro de usuario
 * con la contraseña encriptada mediante bcrypt (Hash::make).
 * Utiliza las reglas de validación de contraseña definidas en
 * PasswordValidationRules (min 8, mixedCase, numbers, symbols).
 *
 * @package App\Actions\Fortify
 * @standard PHPDoc (PSR-5)
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Valida y crea un nuevo usuario registrado.
     *
     * Aplica validación estricta de nombre, email único y contraseña
     * segura. La contraseña se almacena hasheada con bcrypt.
     *
     * @param  array<string, string>  $input  Datos del formulario
     *                                        (name, email, password, password_confirmation).
     * @return User                           El usuario recién creado.
     *
     * @throws ValidationException Si los datos no pasan la validación.
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
