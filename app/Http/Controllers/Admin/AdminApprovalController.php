<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAccessRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
// No olvides que ahora podemos retornar vistas, aunque Laravel lo maneja automático si le quitamos el tipado estricto.

class AdminApprovalController extends Controller
{
    // Le quitamos el tipado ": JsonResponse" para que nos deje retornar una vista de Blade
public function index(Request $request)
    {
        $requests = AdminAccessRequest::with('user')
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest()
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'user_name' => $request->user->name,
                    'user_email' => $request->user->email,
                    'role' => $request->user->role,
                    'expires_at' => $request->expires_at,
                ];
            });

        // MAGIA AQUÍ: Si el polleo pide JSON, le aventamos solo los datos
        if ($request->wantsJson()) {
            return response()->json([
                'pending_requests' => $requests
            ]);
        }

        // Si es una carga normal de navegador, le pintamos la vista
        return view('admin.identity-requests', [
            'pending_requests' => $requests,
        ]);
    }

    // Esta sí se queda con : JsonResponse porque es nuestra API para el botón de Aprobar/Rechazar
    public function decide(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
            'decision' => ['required', 'in:approved,rejected'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $pendingRequests = AdminAccessRequest::with('user')
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        $accessRequest = $pendingRequests->first(function ($item) use ($validated) {
            return Hash::check($validated['code'], $item->request_code_hash);
        });

        if (!$accessRequest) {
            return response()->json([
                'message' => 'Código no válido o solicitud expirada.',
            ], 404);
        }

        if ($validated['decision'] === 'rejected') {
            $accessRequest->update([
                'status' => 'rejected',
                'reviewed_by' => $request->user()->id,
                'decision_notes' => $validated['notes'] ?? null,
                'reviewed_at' => now(),
            ]);

            return response()->json([
                'message' => 'Solicitud rechazada.',
                'status' => 'rejected',
            ]);
        }

        $newPin = (string) random_int(100000, 999999);

        $accessRequest->user->update([
            'admin_pin_hash' => Hash::make($newPin),
            'admin_verified_once_at' => now(),
        ]);

        $accessRequest->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'decision_notes' => $validated['notes'] ?? null,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Solicitud aprobada. Entrega este PIN al administrador solicitante.',
            'status' => 'approved',
            'pin' => $newPin,
        ]);
        
        // Ya eliminamos el "return view(...)" que tenías aquí flotando sin hacer nada.
    }
}