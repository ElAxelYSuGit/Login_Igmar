@extends('layouts.app')

@push('scripts')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endpush

@section('content')
<h2 class="text-xl font-semibold mb-6 text-center text-gray-700">Iniciar Sesión</h2>

<form id="loginForm" class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
        <input type="email" id="email" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Contraseña</label>
        <input type="password" id="password" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
    </div>

    <div class="flex justify-center">
        <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
    </div>



    <button type="submit" id="btnSubmit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition font-medium">
        Ingresar
    </button>
</form>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault(); // Evitamos que la página se recargue
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const recaptcha_token = grecaptcha.getResponse();
    const btnSubmit = document.getElementById('btnSubmit');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    if (!recaptcha_token) {
        Swal.fire({ icon: 'warning', title: 'Verificación requerida', text: 'Por favor, marca la casilla de seguridad reCAPTCHA.' });
        return;
    }

    // Efecto de carga en el botón
    btnSubmit.disabled = true;
    btnSubmit.innerText = 'Verificando...';

    try {
        const response = await fetch('/auth/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ email, password, recaptcha_token })
        });

        const data = await response.json();

        if (response.ok) {
            // Evaluamos la respuesta de tu AuthController
            if (data.redirect) {
                // Si es Invitado o Admin Primario, entran directo
                window.location.href = data.redirect;
            } else if (data.next_step) {
                // Si es Usuario o Admin Regular, los mandamos a la vista del 2FA
                window.location.href = data.next_step;
            }
        } else {
            // Manejo de errores (Credenciales incorrectas o Rate Limiting)
            let errorMessage = 'Error al iniciar sesión.';
            if (data.errors && data.errors.email) {
                errorMessage = data.errors.email[0];
            } else if (data.message) {
                errorMessage = data.message;
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Acceso Denegado',
                text: errorMessage,
            });
        }
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Oops...', text: 'Hubo un problema de conexión con el servidor.' });
    } finally {
        btnSubmit.disabled = false;
        btnSubmit.innerText = 'Ingresar';
    }
});
</script>
@endsection