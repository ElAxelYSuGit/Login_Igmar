@extends('layouts.app')

@section('content')
<div class="text-center mb-6">
    <h2 class="text-xl font-semibold text-gray-700">Verificación de 2 Pasos</h2>
    <p class="text-sm text-gray-500 mt-2">Hemos enviado un código de 6 dígitos a tu correo.</p>
</div>

<form id="twoFactorForm" class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 text-center mb-2">Código OTP</label>
        <input type="text" id="otp" maxlength="6" class="mt-1 block w-full px-4 py-3 text-center text-2xl tracking-widest border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="••••••" required>
    </div>

    <button type="submit" id="btnSubmit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition font-medium">
        Verificar Código
    </button>
</form>

<script>
document.getElementById('twoFactorForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const otp = document.getElementById('otp').value;
    const btnSubmit = document.getElementById('btnSubmit');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    btnSubmit.disabled = true;
    btnSubmit.innerText = 'Validando...';

    try {
        const response = await fetch('/auth/two-factor', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ code: otp }) 
        });

        const data = await response.json();

        if (response.ok) {
            
            // ¡AQUÍ ESTÁ EL CAMBIO! Buscamos exactamente 'identity_code'
            if (data.identity_code) { 
                
                let codigoMostrar = data.identity_code;

                Swal.fire({
                    title: 'Verificación de Identidad (3FA)',
                    html: `
                        <p class="mb-4 text-gray-600">Ve físicamente con el Administrador Primario y muéstrale este código de 6 dígitos:</p>
                        <h1 class="text-5xl font-mono font-bold tracking-widest text-blue-600 bg-blue-100 p-4 rounded-lg shadow-inner">${codigoMostrar}</h1>
                        <p class="mt-6 text-sm text-red-500 font-semibold">⚠️ No cierres esta ventana.</p>
                        <p class="text-sm text-gray-500 mt-1">Cuando el Admin apruebe tu solicitud y te entregue tu PIN definitivo, haz clic en Continuar.</p>
                    `,
                    icon: 'info',
                    showCancelButton: false,
                    confirmButtonText: 'Ya tengo mi PIN ->',
                    confirmButtonColor: '#1f2937',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Lo mandamos a la pantalla del PIN (corrigiendo el redirect de tu controlador)
                        window.location.href = '/auth/admin-pin';
                    }
                });

            } else {
                // Flujo normal para Usuarios o Admins que ya tienen PIN
                window.location.href = data.redirect || data.next_step;
            }

        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Código inválido o expirado.' });
        }
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Oops...', text: 'Error de conexión.' });
    } finally {
        btnSubmit.disabled = false;
        btnSubmit.innerText = 'Verificar Código';
    }
});
</script>
@endsection