<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class LicenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $licenses = License::with('client')
            ->when($search, function ($query, $search) {
                return $query->where('url', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Fetch all clients for the modal select dropdown
        $clients = Client::orderBy('name')->get();

        return view('licenses.index', compact('licenses', 'clients', 'search'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'url' => ['required', 'string', 'max:255'],
            'block_token' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:activa,suspendida,vencida'],
            'billing_cycle' => ['required', 'string', 'in:mensual,trimestral,semestral,anual'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'next_billing_date' => ['required', 'date'],
        ], [
            'client_id.required' => 'El cliente es obligatorio.',
            'client_id.exists' => 'El cliente seleccionado no es válido.',
            'url.required' => 'La URL es obligatoria.',
            'block_token.required' => 'El token para bloqueo es obligatorio.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado no es válido.',
            'billing_cycle.required' => 'El ciclo de facturación es obligatorio.',
            'billing_cycle.in' => 'El ciclo de facturación no es válido.',
            'monthly_fee.required' => 'La tarifa mensual es obligatoria.',
            'monthly_fee.numeric' => 'La tarifa mensual debe ser un valor numérico.',
            'next_billing_date.required' => 'La fecha de próxima facturación es obligatoria.',
        ]);

        // Apply Business Rule: Every 5th license per client is automatically Free
        // (5th, 10th, 15th, 20th... i.e., when the new count is a multiple of 5)
        $existingCount = License::where('client_id', $validated['client_id'])->count();
        $newCount = $existingCount + 1;
        $isFree = ($newCount % 5 === 0);

        if ($isFree) {
            $validated['monthly_fee'] = 0.00; // Force fee to 0
        }

        $license = new License($validated);
        $license->is_free = $isFree;
        $license->save();

        if ($isFree) {
            return redirect()->route('licenses.index')
                ->with('status', "¡Licencia creada con éxito! Regla automática aplicada: la licencia #{$newCount} de este cliente es Gratuita ($0.00).");
        }

        return redirect()->route('licenses.index')
            ->with('status', '¡Licencia creada exitosamente!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, License $license)
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'url' => ['required', 'string', 'max:255'],
            'block_token' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:activa,suspendida,vencida'],
            'billing_cycle' => ['required', 'string', 'in:mensual,trimestral,semestral,anual'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'next_billing_date' => ['required', 'date'],
        ], [
            'client_id.required' => 'El cliente es obligatorio.',
            'client_id.exists' => 'El cliente seleccionado no es válido.',
            'url.required' => 'La URL es obligatoria.',
            'block_token.required' => 'El token para bloqueo es obligatorio.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado no es válido.',
            'billing_cycle.required' => 'El ciclo de facturación es obligatorio.',
            'billing_cycle.in' => 'El ciclo de facturación no es válido.',
            'monthly_fee.required' => 'La tarifa mensual es obligatoria.',
            'monthly_fee.numeric' => 'La tarifa mensual debe ser un valor numérico.',
            'next_billing_date.required' => 'La fecha de próxima facturación es obligatoria.',
        ]);

        // If the client changed, recalculate the free status for the new client.
        // The free rule applies to every multiple-of-5 position (5th, 10th, 15th...)
        if ($license->client_id != $validated['client_id']) {
            // Count existing licenses for the NEW client (excluding current license since it's moving)
            $newClientCount = License::where('client_id', $validated['client_id'])->count();
            $newPosition = $newClientCount + 1;
            $license->is_free = ($newPosition % 5 === 0);
            if ($license->is_free) {
                $validated['monthly_fee'] = 0.00;
            }
        } else {
            // Same client: if the license was automatically marked free, keep it free.
            if ($license->is_free) {
                $validated['monthly_fee'] = 0.00; // Always enforce $0.00 for free licenses
            }
        }

        $license->update($validated);

        return redirect()->route('licenses.index')
            ->with('status', '¡Licencia actualizada correctamente!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(License $license)
    {
        $license->delete();

        return redirect()->route('licenses.index')
            ->with('status', '¡Licencia eliminada con éxito!');
    }

    /**
     * Proxy: Get the remote system status via the license's block_token.
     */
    public function systemStatus(License $license): JsonResponse
    {
        if (empty($license->block_token)) {
            return response()->json(['success' => false, 'message' => 'Esta licencia no tiene token de bloqueo configurado.'], 422);
        }

        try {
            $response = Http::timeout(8)->get(rtrim($license->url, '/') . '/api/system/status', [
                'token' => $license->block_token,
            ]);

            // Pass the remote HTTP status through so the JS can differentiate
            // 200 (ok), 401 (wrong token), etc.
            $body = $response->json() ?? ['success' => false, 'message' => 'Respuesta no válida del sistema remoto.'];
            return response()->json($body, $response->status());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo conectar con el sistema remoto. Verifica que la URL sea accesible.'], 503);
        }
    }

    /**
     * Proxy: Toggle (enable/disable) the remote system via the license's block_token.
     */
    public function systemToggle(Request $request, License $license): JsonResponse
    {
        if (empty($license->block_token)) {
            return response()->json(['success' => false, 'message' => 'Esta licencia no tiene token de bloqueo configurado.'], 422);
        }

        $validated = $request->validate([
            'action' => ['nullable', 'string', 'in:enable,disable'],
        ]);

        try {
            $payload = ['token' => $license->block_token];
            if (!empty($validated['action'])) {
                $payload['action'] = $validated['action'];
            }

            $response = Http::timeout(8)
                ->withHeaders(['Accept' => 'application/json'])
                ->post(rtrim($license->url, '/') . '/api/system/toggle', $payload);

            $toggleBody = $response->json() ?? ['success' => false, 'message' => 'Respuesta no válida del sistema remoto.'];
            return response()->json($toggleBody, $response->status());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo conectar con el sistema remoto. Verifica que la URL sea accesible.'], 503);
        }
    }
}
