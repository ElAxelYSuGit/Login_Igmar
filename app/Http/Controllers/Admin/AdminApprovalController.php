<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAccessRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Controlador de aprobación de identidad por el admin primario.
 *
 * Gestiona las solicitudes de verificación de identidad presencial
 * como parte del tercer factor de autenticación (3FA) para
 * administradores nuevos. El admin primario recibe el código de
 * identidad en persona y puede aprobar o rechazar la solicitud.
 *
 * @package App\Http\Controllers\Admin
 * @standard PHPDoc (PSR-5)
 */
class AdminApprovalController extends Controller
{
    /**
     * Muestra la lista de solicitudes de identidad pendientes.
     *
     * Retorna las solicitudes que están en estado 'pending' y no
     * han expirado. Si la petición espera JSON (polling AJAX),
     * retorna datos JSON. Si es carga de navegador, renderiza la vista.
     *
     * @param  Request  $request  La petición HTTP (detecta si espera JSON).
     * @return \Illuminate\View\View|JsonResponse  Vista Blade o JSON
     *                                             con las solicitudes.
     */
    public function index(Request $request)
    {
        $requests = AdminAccessRequest::with('user')
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest()
            ->get()
            ->map(function ($request) {
                return [
                    'id'         => $request->id,
                    'user_name'  => $request->user->name,
                    'user_email' => $request->user->email,
                    'role'       => $request->user->role,
                    'expires_at' => $request->expires_at,
                ];
            });

        Log::debug('AdminApproval: Consultando solicitudes pendientes', [
            'total_pendientes' => $requests->count(),
        ]);

        // Si el polling pide JSON, retornamos solo los datos
        if ($request->wantsJson()) {
            return response()->json([
                'pending_requests' => $requests,
            ]);
        }

        // Si es carga normal de navegador, renderizamos la vista
        return view('admin.identity-requests', [
            'pending_requests' => $requests,
        ]);
    }

    /**
     * Procesa la decisión del admin primario sobre una solicitud.
     *
     * Busca la solicitud pendiente cuyo código de identidad coincida
     * con el código ingresado. Si se aprueba, genera un PIN de 4
     * dígitos para el admin solicitante. Si se rechaza, registra el
     * motivo en la auditoría.
     *
     * @param  Request  $request  Debe contener: code (6 dígitos),
     *                            decision ('approved'|'rejected'),
     *                            notes (opcional, texto de auditoría).
     * @return JsonResponse       Respuesta con el resultado de la decisión.
     *                            Si se aprueba, incluye el PIN generado.
     */
    public function decide(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'     => ['required', 'digits:6'],
            'decision' => ['required', 'in:approved,rejected'],
            'notes'    => ['nullable', 'string', 'max:500'],
        ], [
            'code.required'     => 'El código de identidad es obligatorio.',
            'code.digits'       => 'El código debe ser de exactamente 6 dígitos.',
            'decision.required' => 'Debes seleccionar una decisión.',
            'decision.in'       => 'La decisión debe ser aprobar o rechazar.',
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
            Log::channel('audit')->warning('Código de identidad no válido', [
                'que'    => 'Intento de decisión con código no válido o expirado',
                'quien'  => $request->user()->email,
                'cuando' => now()->toDateTimeString(),
                'donde'  => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Código no válido o solicitud expirada.',
            ], 404);
        }

        if ($validated['decision'] === 'rejected') {
            $accessRequest->update([
                'status'         => 'rejected',
                'reviewed_by'    => $request->user()->id,
                'decision_notes' => $validated['notes'] ?? null,
                'reviewed_at'    => now(),
            ]);

            Log::channel('audit')->info('Solicitud de identidad rechazada', [
                'que'           => 'Admin primario rechazó solicitud de identidad',
                'quien'         => $request->user()->email,
                'cuando'        => now()->toDateTimeString(),
                'donde'         => $request->ip(),
                'solicitante'   => $accessRequest->user->email,
                'notas'         => $validated['notes'] ?? 'Sin notas',
                'request_id'    => $accessRequest->id,
            ]);

            Log::info('AdminApproval: Solicitud rechazada', [
                'request_id'  => $accessRequest->id,
                'reviewed_by' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Solicitud rechazada.',
                'status'  => 'rejected',
            ]);
        }

        // --- Aprobación: generar PIN de 4 dígitos ---
        $newPin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $accessRequest->user->update([
            'admin_pin_hash'        => Hash::make($newPin),
            'admin_verified_once_at' => now(),
        ]);

        $accessRequest->update([
            'status'         => 'approved',
            'reviewed_by'    => $request->user()->id,
            'decision_notes' => $validated['notes'] ?? null,
            'reviewed_at'    => now(),
        ]);

        Log::channel('audit')->info('Solicitud de identidad aprobada', [
            'que'         => 'Admin primario aprobó solicitud y generó PIN',
            'quien'       => $request->user()->email,
            'cuando'      => now()->toDateTimeString(),
            'donde'       => $request->ip(),
            'solicitante' => $accessRequest->user->email,
            'notas'       => $validated['notes'] ?? 'Sin notas',
            'request_id'  => $accessRequest->id,
        ]);

        Log::info('AdminApproval: Solicitud aprobada, PIN generado', [
            'request_id'  => $accessRequest->id,
            'reviewed_by' => $request->user()->id,
            'for_user_id' => $accessRequest->user->id,
        ]);

        return response()->json([
            'message' => 'Solicitud aprobada. Entrega este PIN al administrador solicitante.',
            'status'  => 'approved',
            'pin'     => $newPin,
        ]);
    }
}