@extends('layouts.app')

@push('scripts')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endpush

@section('content')
<h2 class="text-xl font-semibold mb-6 text-center text-gray-700">Crear Cuenta de Invitado</h2>

<form id="registerForm" class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700">Nombre Completo</label>
        <input type="text" id="name" maxlength="255" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="Tu nombre completo">
        <p id="nameError" class="text-red-500 text-xs mt-1 hidden"></p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
        <input type="email" id="email" maxlength="255" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="correo@ejemplo.com">
        <p id="emailError" class="text-red-500 text-xs mt-1 hidden"></p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Contraseña</label>
        <input type="password" id="password" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="Mínimo 8 caracteres, mayúsculas, números y símbolos">
        <p id="passwordError" class="text-red-500 text-xs mt-1 hidden"></p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Confirmar Contraseña</label>
        <input type="password" id="password_confirmation" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="Repite tu contraseña">
        <p id="passwordConfirmError" class="text-red-500 text-xs mt-1 hidden"></p>
    </div>

    <div class="flex justify-center">
        <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
    </div>

    <button type="submit" id="btnSubmit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition font-medium">
        Registrarme
    </button>
</form>

<div class="text-center mt-4">
    <a href="/" class="text-sm text-blue-600 hover:underline">¿Ya tienes cuenta? Inicia sesión</a>
</div>

<script>
/**
 * Limpia todos los mensajes de error visibles en el formulario.
 */
function clearErrors() {
    document.querySelectorAll('[id$="Error"]').forEach(el => {
        el.classList.add('hidden');
        el.textContent = '';
    });
}

/**
 * Muestra un mensaje de error bajo el campo indicado.
 * @param {string} fieldId - El ID del campo de error (ej: 'nameError').
 * @param {string} message - El mensaje de error a mostrar.
 */
function showError(fieldId, message) {
    const el = document.getElementById(fieldId);
    if (el) {
        el.textContent = message;
        el.classList.remove('hidden');
    }
}

/**
 * Valida los campos del formulario de registro en el lado del cliente.
 * Realiza sanitización básica (trim) y verifica que todos los campos
 * cumplan con los requisitos antes de enviar al servidor.
 * @returns {boolean} true si la validación pasa, false si hay errores.
 */
function validateForm() {
    clearErrors();
    let valid = true;

    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirmation').value;

    if (!name) {
        showError('nameError', 'El nombre es obligatorio.');
        valid = false;
    } else if (name.length > 255) {
        showError('nameError', 'El nombre no puede exceder 255 caracteres.');
        valid = false;
    }

    if (!email) {
        showError('emailError', 'El correo electrónico es obligatorio.');
        valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showError('emailError', 'Ingresa un correo electrónico válido.');
        valid = false;
    }

    if (!password) {
        showError('passwordError', 'La contraseña es obligatoria.');
        valid = false;
    } else if (password.length < 8) {
        showError('passwordError', 'La contraseña debe tener al menos 8 caracteres.');
        valid = false;
    } else if (!/[A-Z]/.test(password)) {
        showError('passwordError', 'Debe contener al menos una letra mayúscula.');
        valid = false;
    } else if (!/[a-z]/.test(password)) {
        showError('passwordError', 'Debe contener al menos una letra minúscula.');
        valid = false;
    } else if (!/[0-9]/.test(password)) {
        showError('passwordError', 'Debe contener al menos un número.');
        valid = false;
    } else if (!/[^A-Za-z0-9]/.test(password)) {
        showError('passwordError', 'Debe contener al menos un símbolo especial.');
        valid = false;
    }

    if (password !== passwordConfirm) {
        showError('passwordConfirmError', 'Las contraseñas no coinciden.');
        valid = false;
    }

    return valid;
}

document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (!validateForm()) return;

    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const password_confirmation = document.getElementById('password_confirmation').value;
    const recaptcha_token = grecaptcha.getResponse();
    const btnSubmit = document.getElementById('btnSubmit');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    if (!recaptcha_token) {
        Swal.fire({ icon: 'warning', title: 'Verificación requerida', text: 'Por favor, marca la casilla de seguridad reCAPTCHA.' });
        return;
    }

    btnSubmit.disabled = true;
    btnSubmit.innerText = 'Registrando...';

    try {
        const response = await fetch('/auth/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ name, email, password, password_confirmation, recaptcha_token })
        });

        const data = await response.json();

        if (response.ok) {
            Swal.fire({
                icon: 'success',
                title: '¡Cuenta creada!',
                text: data.message || 'Ya puedes iniciar sesión.',
                confirmButtonText: 'Ir al Login'
            }).then(() => {
                window.location.href = '/';
            });
        } else {
            clearErrors();

            if (data.errors) {
                // Mostrar errores de validación del servidor
                if (data.errors.name) showError('nameError', data.errors.name[0]);
                if (data.errors.email) showError('emailError', data.errors.email[0]);
                if (data.errors.password) showError('passwordError', data.errors.password[0]);
                if (data.errors.recaptcha_token) {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.errors.recaptcha_token[0] });
                }
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo completar el registro.' });
            }
        }
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Oops...', text: 'Hubo un problema de conexión con el servidor.' });
    } finally {
        btnSubmit.disabled = false;
        btnSubmit.innerText = 'Registrarme';
    }
});
</script>
@endsection
