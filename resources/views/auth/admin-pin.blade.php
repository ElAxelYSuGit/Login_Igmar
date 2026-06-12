@extends('layouts.app')

@section('content')
<div class="text-center mb-6">
    <h2 class="text-xl font-semibold text-gray-700">Acceso Administrativo (3FA)</h2>
    <p class="text-sm text-gray-500 mt-2">Ingresa tu PIN de seguridad administrativo.</p>
</div>

<form id="adminPinForm" class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 text-center mb-2">PIN de Acceso</label>
        <input type="password" id="pin" maxlength="4" class="mt-1 block w-full px-4 py-3 text-center text-2xl tracking-widest border border-gray-300 rounded-md focus:ring-gray-800 focus:border-gray-800" placeholder="••••">
    </div>

    <button type="submit" id="btnSubmit" class="w-full bg-gray-800 text-white py-2 px-4 rounded-md hover:bg-gray-900 transition font-medium">
        Autorizar Acceso
    </button>
</form>

<script>
document.getElementById('adminPinForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const pin = document.getElementById('pin').value.trim();
    const btnSubmit = document.getElementById('btnSubmit');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Validación en frontend (reemplaza el 'required' HTML)
    if (!pin || pin.length !== 4 || !/^\d{4}$/.test(pin)) {
        Swal.fire({ icon: 'warning', title: 'PIN inválido', text: 'Ingresa un PIN de exactamente 4 dígitos.' });
        return;
    }

    btnSubmit.disabled = true;
    btnSubmit.innerText = 'Autorizando...';

    try {
        const response = await fetch('/auth/admin-pin', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ pin: pin })
        });

        const data = await response.json();

        if (response.ok) {
            window.location.href = data.redirect || '/admin/dashboard';
        } else {
            Swal.fire({ icon: 'error', title: 'Acceso Denegado', text: data.message || 'PIN incorrecto.' });
        }
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Oops...', text: 'Error de conexión.' });
    } finally {
        btnSubmit.disabled = false;
        btnSubmit.innerText = 'Autorizar Acceso';
    }
});
</script>
@endsection