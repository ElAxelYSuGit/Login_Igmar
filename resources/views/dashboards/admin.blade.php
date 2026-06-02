@extends('layouts.app')

@section('content')
<div class="text-center">
    <div class="w-20 h-20 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm border-2 border-blue-200">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
        </svg>
    </div>
    
    <h2 class="text-3xl font-bold text-gray-800 mb-2">¡Bienvenido, Administrador!</h2>
    <p class="text-gray-500 mb-8">Has superado los 3 factores de autenticación. El sistema operativo de Almacenes Quintero está listo.</p>
    
    <div class="p-5 bg-gray-50 border border-gray-200 rounded-lg text-left mb-8 shadow-inner">
        <h3 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Estado de la Sesión</h3>
        <ul class="text-sm text-gray-600 space-y-2">
            <li class="flex items-center">
                <span class="text-green-500 mr-2">✓</span> 
                Nivel de acceso: <strong class="ml-1 text-gray-800">Total (3FA)</strong>
            </li>
            <li class="flex items-center">
                <span class="text-green-500 mr-2">✓</span> 
                Conexión segura establecida.
            </li>
        </ul>
    </div>

    <button onclick="logout()" class="w-full bg-red-600 text-white py-3 px-4 rounded-md hover:bg-red-700 transition font-medium shadow-md">
        Cerrar Sesión Segura
    </button>
</div>

<script>
// Función para destruir la sesión y regresar al login
async function logout() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    
    try {
        await fetch('/auth/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        // Redirigimos a la raíz una vez que el backend destruye la sesión
        window.location.href = '/';
    } catch (error) {
        console.error("Error al cerrar sesión", error);
    }
}
</script>
@endsection