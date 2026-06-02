@extends('layouts.app')

@section('content')
<div class="text-center">
    <div class="w-20 h-20 bg-gray-100 text-gray-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm border-2 border-gray-200">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        </svg>
    </div>
    
    <h2 class="text-3xl font-bold text-gray-800 mb-2">Portal de Invitados</h2>
    <p class="text-gray-500 mb-8">Autenticación básica (1FA). Acceso en modo de solo lectura.</p>
    
    <div class="p-5 bg-gray-50 border border-gray-200 rounded-lg text-left mb-8 shadow-inner">
        <h3 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Estado de la Sesión</h3>
        <ul class="text-sm text-gray-600 space-y-2">
            <li class="flex items-center">
                <span class="text-blue-500 mr-2">ℹ</span> 
                Nivel de acceso: <strong class="ml-1 text-gray-800">Limitado (1FA)</strong>
            </li>
            <li class="flex items-center text-red-600 font-medium">
                <span class="mr-2">✕</span> 
                No cuentas con permisos operativos.
            </li>
        </ul>
    </div>

    <button onclick="logout()" class="w-full bg-gray-800 text-white py-3 px-4 rounded-md hover:bg-gray-900 transition font-medium shadow-md">
        Cerrar Sesión
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