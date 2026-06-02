@extends('layouts.app')

@php
    $bodyClasses = 'bg-gray-100 min-h-screen p-8';
@endphp

@section('content')
<div class="fixed inset-0 bg-gray-100 p-8 overflow-y-auto">
    <div class="max-w-5xl mx-auto">
        
        <div class="flex justify-between items-center mb-8 bg-white p-6 rounded-lg shadow border-l-4 border-gray-800">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Panel del Administrador Primario</h1>
                <p class="text-sm text-gray-500">Validación de identidades (3FA) en espera.</p>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.users.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition font-medium flex items-center">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Nuevo Usuario
                </a>
                
                <button onclick="logout()" class="bg-red-500 text-white px-4 py-2 rounded shadow hover:bg-red-600 transition font-medium">
                    Cerrar Sesión
                </button>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
            <div class="p-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-700">Solicitudes en Espera</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="py-3 px-4 text-left text-sm font-semibold">ID</th>
                            <th class="py-3 px-4 text-left text-sm font-semibold">Solicitante</th>
                            <th class="py-3 px-4 text-left text-sm font-semibold">Correo</th>
                            <th class="py-3 px-4 text-left text-sm font-semibold">Expiración</th>
                        </tr>
                    </thead>
                    <tbody id="requestsTableBody" class="text-gray-700 divide-y divide-gray-200">
                        @forelse($pending_requests as $request)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-3 px-4 text-sm font-mono">{{ $request->id ?? $request['id'] }}</td>
                                <td class="py-3 px-4 text-sm font-medium">{{ $request->user_name ?? $request['user_name'] }}</td>
                                <td class="py-3 px-4 text-sm">{{ $request->user_email ?? $request['user_email'] }}</td>
                                <td class="py-3 px-4 text-sm text-red-600 font-medium">
                                    {{ \Carbon\Carbon::parse($request->expires_at ?? $request['expires_at'])->format('H:i:s') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-gray-500">No hay solicitudes pendientes de autorización.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-700">Verificar Código Físico</h2>
                <p class="text-sm text-gray-500 mb-4">Ingresa el código que el solicitante te dictó en persona.</p>
                
                <form id="decideForm" class="flex gap-4">
                    <input type="text" id="request_code" class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 tracking-widest text-lg font-mono" placeholder="Ingresar Código de 6 dígitos" required>
                    
                    <select id="decision" class="w-48 px-4 py-2 border border-gray-300 rounded-md bg-white">
                        <option value="approved">Aprobar (Generar PIN)</option>
                        <option value="rejected">Rechazar</option>
                    </select>

                    <button type="submit" class="bg-blue-800 text-white px-6 py-2 rounded-md hover:bg-blue-900 transition font-medium">
                        Ejecutar Decisión
                    </button>
                </form>
            </div>
        </div>
        
    </div>
</div>

<script>
document.getElementById('decideForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const code = document.getElementById('request_code').value;
    const decision = document.getElementById('decision').value;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Pedimos notas de validación por SweetAlert
    const { value: notes } = await Swal.fire({
        title: 'Notas de auditoría',
        input: 'text',
        inputLabel: decision === 'approved' ? '¿Cómo validaste la identidad?' : 'Motivo de rechazo',
        inputPlaceholder: 'Ej. Identificación oficial mostrada...',
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar'
    });

    if (notes !== undefined) {
        try {
            const response = await fetch('/admin/identity-requests/decide', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ 
                    code: code, 
                    decision: decision,
                    notes: notes 
                })
            });

            const data = await response.json();

            if (response.ok) {
                // AQUÍ ESTÁ LA MAGIA: Armamos una alerta dinámica
                let swalOptions = {
                    icon: 'success',
                    title: decision === 'approved' ? '¡Identidad Aprobada!' : 'Solicitud Rechazada',
                    allowOutsideClick: false
                };

                // Si se aprobó y el backend nos mandó el PIN, lo hacemos GIGANTE
                if (decision === 'approved' && data.pin) {
                    swalOptions.html = `
                        <p class="mb-4 text-gray-600">${data.message}</p>
                        <p class="mb-2 font-bold text-gray-800">Dicta este PIN al administrador solicitante:</p>
                        <h1 class="text-5xl font-mono font-bold tracking-widest text-green-700 bg-green-100 p-4 rounded-lg shadow-inner border-2 border-green-300 text-center">${data.pin}</h1>
                        <p class="mt-4 text-sm text-red-500 font-medium text-center">⚠️ Este PIN es permanente.</p>
                    `;
                } else {
                    swalOptions.text = data.message;
                }

                // Disparamos la alerta y cuando le den OK, recargamos la página
                Swal.fire(swalOptions).then(() => {
                    window.location.reload(); 
                });

            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se encontró la solicitud o ya expiró.' });
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Oops...', text: 'Error al conectar con el servidor.' });
        }
    }
});

// Función global de Logout
async function logout() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    await fetch('/auth/logout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    });
    window.location.href = '/';
}
// ==========================================
// SERVICIO DE POLLEO (Refresco Automático)
// ==========================================
async function fetchPendingRequests() {
    try {
        // Hacemos fetch a la misma ruta en la que estamos
        const response = await fetch('/admin/identity-requests', {
            headers: {
                'Accept': 'application/json', // Le exigimos JSON al backend
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.ok) {
            const data = await response.json();
            updateTable(data.pending_requests);
        }
    } catch (error) {
        console.error("Error en el polleo de solicitudes:", error);
    }
}

function updateTable(requests) {
    const tbody = document.getElementById('requestsTableBody');
    tbody.innerHTML = ''; // Limpiamos la tabla actual

    // Si no hay nadie, pintamos el mensaje de vacío
    if (requests.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="py-6 text-center text-gray-500">No hay solicitudes pendientes de autorización.</td></tr>`;
        return;
    }

    // Si hay datos, dibujamos las filas nuevas
    requests.forEach(req => {
        // Formateamos la hora para que se vea bonita (H:i:s)
        const date = new Date(req.expires_at);
        const timeString = date.toLocaleTimeString('es-MX', { hour12: false });

        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 transition';
        tr.innerHTML = `
            <td class="py-3 px-4 text-sm font-mono">${req.id}</td>
            <td class="py-3 px-4 text-sm font-medium">${req.user_name}</td>
            <td class="py-3 px-4 text-sm">${req.user_email}</td>
            <td class="py-3 px-4 text-sm text-red-600 font-medium">${timeString}</td>
        `;
        tbody.appendChild(tr);
    });
}

// Arrancamos el motor: ejecuta la función cada 5000 milisegundos (5 segundos)
setInterval(fetchPendingRequests, 2000);
</script>
@endsection