@extends('layouts.app')

@php
    $bodyClasses = 'bg-gray-100 min-h-screen p-8';
@endphp

@section('content')
<div class="fixed inset-0 bg-gray-100 p-8 overflow-y-auto">
    <div class="max-w-2xl mx-auto">
        
        <div class="flex justify-between items-center mb-8 bg-white p-6 rounded-lg shadow border-l-4 border-blue-600">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Alta de Nuevo Personal</h1>
                <p class="text-sm text-gray-500">Gestión centralizada de identidades.</p>
            </div>
            <div class="space-x-2">
                <a href="/admin/identity-requests" class="bg-gray-500 text-white px-4 py-2 rounded shadow hover:bg-gray-600 transition text-sm">
                    Volver al Panel
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6 border-b border-gray-200 bg-blue-50">
                <h2 class="text-lg font-semibold text-blue-900">Credenciales de Acceso</h2>
            </div>
            
            <form id="registerUserForm" class="p-6 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                    <input type="text" id="name" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="Ej. Marcelo Sifuentes" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                    <input type="email" id="email" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="correo@almacenesquintero.com" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contraseña Segura</label>
                        <input type="password" id="password" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                        <p class="text-xs text-gray-400 mt-1">Mínimo 8 caracteres, números y símbolos.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nivel de Acceso (Rol)</label>
                        <select id="role" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white">
                            <option value="guest">Invitado (1FA - Solo Lectura)</option>
                            <option value="user">Usuario (2FA - Operativo)</option>
                            <option value="admin">Administrador Regular (3FA - Total)</option>
                        </select>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-200 flex justify-end">
                    <button type="submit" id="btnSubmit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition font-medium shadow-md">
                        Crear Cuenta
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
document.getElementById('registerUserForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btnSubmit = document.getElementById('btnSubmit');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    const data = {
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        password: document.getElementById('password').value,
        role: document.getElementById('role').value
    };

    btnSubmit.disabled = true;
    btnSubmit.innerText = 'Registrando...';

    try {
        const response = await fetch('/admin/users', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (response.ok) {
            Swal.fire({
                icon: 'success',
                title: 'Registro Exitoso',
                text: result.message,
            }).then(() => {
                // Limpiamos el formulario para poder crear otro
                document.getElementById('registerUserForm').reset();
            });
        } else {
            // Manejamos los errores de validación (ej. el correo ya existe o la contraseña es débil)
            let errorMessage = 'Error al registrar usuario.';
            if (result.errors) {
                // Extraemos el primer error de la lista
                errorMessage = Object.values(result.errors)[0][0]; 
            } else if (result.message) {
                errorMessage = result.message;
            }
            
            Swal.fire({ icon: 'error', title: 'Error de Validación', text: errorMessage });
        }
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Oops...', text: 'Error al conectar con el servidor.' });
    } finally {
        btnSubmit.disabled = false;
        btnSubmit.innerText = 'Crear Cuenta';
    }
});
</script>
@endsection