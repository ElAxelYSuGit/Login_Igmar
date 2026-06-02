@extends('layouts.app')

@section('content')
<div class="text-center">
    <div class="w-20 h-20 bg-green-100 text-green-700 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm border-2 border-green-200">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
        </svg>
    </div>
    
    <h2 class="text-3xl font-bold text-gray-800 mb-2">Panel Operativo</h2>
    <p class="text-gray-500 mb-8">Autenticación de 2 Factores (2FA) completada. Módulo de gestión de inventarios y logística habilitado.</p>
    
    <div class="p-5 bg-gray-50 border border-gray-200 rounded-lg text-left mb-8 shadow-inner">
        <h3 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Estado de la Sesión</h3>
        <ul class="text-sm text-gray-600 space-y-2">
            <li class="flex items-center">
                <span class="text-green-500 mr-2">✓</span> 
                Nivel de acceso: <strong class="ml-1 text-gray-800">Usuario Operativo (2FA)</strong>
            </li>
            <li class="flex items-center">
                <span class="text-green-500 mr-2">✓</span> 
                Permisos de lectura y escritura en lotes concedidos.
            </li>
        </ul>
    </div>

    <button onclick="logout()" class="w-full bg-red-600 text-white py-3 px-4 rounded-md hover:bg-red-700 transition font-medium shadow-md">
        Cerrar Sesión Segura
    </button>
</div>

<script>
async function logout() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    try {
        await fetch('/auth/logout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        });
        window.location.href = '/';
    } catch (error) { console.error("Error al cerrar sesión", error); }
}
</script>
@endsection