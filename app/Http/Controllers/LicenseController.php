<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\License;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LicenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $perPage = $request->input('per_page', 10);

        $licenses = License::with('client')
            ->when($search, function ($query, $search) {
                return $query->where('url', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->orderBy('is_free', 'asc')
            ->orderBy('next_billing_date', 'asc')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)->appends($request->query());

        // Fetch all clients for the modal select dropdown
        $clients = Client::orderBy('name')->get();

        $creditsByClient = Credit::withSum('payments', 'amount')
            ->where('status', 'activo')
            ->whereNotNull('client_id')
            ->orderBy('credit_date', 'desc')
            ->get()
            ->filter(fn ($credit) => $credit->balance > 0)
            ->groupBy('client_id')
            ->map(fn ($credits) => $credits->map(fn ($credit) => [
                'id' => $credit->id,
                'description' => $credit->description,
                'creditor_name' => $credit->creditor_name,
                'balance' => round($credit->balance, 2),
            ])->values())
            ->toArray();

        return view('licenses.index', compact('licenses', 'clients', 'search', 'creditsByClient'));
    }

    /**
     * Helper to get remote admin token with fallback.
     */
    private function getRemoteToken(License $license): string
    {
        return !empty($license->block_token) ? $license->block_token : 'adminmikpos123';
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $isSuperAdmin = Auth::user()?->isSuperAdmin();

        $rules = [
            'client_id' => ['required', Rule::exists('clients', 'id')->where('user_id', Auth::id())],
            'url' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:activa,suspendida,vencida'],
            'billing_cycle' => ['required', 'string', 'in:mensual,trimestral,semestral,anual'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'setup_fee' => ['required', 'numeric', 'min:0'],
            'next_billing_date' => ['required', 'date'],
            'next_setup_billing_date' => ['nullable', 'date'],
        ];

        if ($isSuperAdmin) {
            $rules['block_token'] = ['nullable', 'string', 'max:255'];
        }

        $validated = $request->validate($rules, [
            'client_id.required' => 'El cliente es obligatorio.',
            'client_id.exists' => 'El cliente seleccionado no es válido.',
            'url.required' => 'La URL es obligatoria.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado no es válido.',
            'billing_cycle.required' => 'El ciclo de facturación es obligatorio.',
            'billing_cycle.in' => 'El ciclo de facturación no es válido.',
            'monthly_fee.required' => 'La tarifa mensual es obligatoria.',
            'monthly_fee.numeric' => 'La tarifa mensual debe ser un valor numérico.',
            'setup_fee.required' => 'El valor de instalación es obligatorio.',
            'setup_fee.numeric' => 'El valor de instalación debe ser un valor numérico.',
            'next_billing_date.required' => 'La fecha de próxima facturación es obligatoria.',
            'next_setup_billing_date.date' => 'La fecha de próxima facturación anual no es válida.',
        ]);

        if ($isSuperAdmin) {
            $validated['block_token'] = $request->filled('block_token') ? $request->input('block_token') : 'adminmikpos123';
        } else {
            $validated['block_token'] = null;
        }

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
        $isSuperAdmin = Auth::user()?->isSuperAdmin();

        $rules = [
            'client_id' => ['required', Rule::exists('clients', 'id')->where('user_id', Auth::id())],
            'url' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:activa,suspendida,vencida'],
            'billing_cycle' => ['required', 'string', 'in:mensual,trimestral,semestral,anual'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'setup_fee' => ['required', 'numeric', 'min:0'],
            'next_billing_date' => ['required', 'date'],
            'next_setup_billing_date' => ['nullable', 'date'],
        ];

        if ($isSuperAdmin) {
            $rules['block_token'] = ['nullable', 'string', 'max:255'];
        }

        $validated = $request->validate($rules, [
            'client_id.required' => 'El cliente es obligatorio.',
            'client_id.exists' => 'El cliente seleccionado no es válido.',
            'url.required' => 'La URL es obligatoria.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado no es válido.',
            'billing_cycle.required' => 'El ciclo de facturación es obligatorio.',
            'billing_cycle.in' => 'El ciclo de facturación no es válido.',
            'monthly_fee.required' => 'La tarifa mensual es obligatoria.',
            'monthly_fee.numeric' => 'La tarifa mensual debe ser un valor numérico.',
            'setup_fee.required' => 'El valor de instalación es obligatorio.',
            'setup_fee.numeric' => 'El valor de instalación debe ser un valor numérico.',
            'next_billing_date.required' => 'La fecha de próxima facturación es obligatoria.',
            'next_setup_billing_date.date' => 'La fecha de próxima facturación anual no es válida.',
        ]);

        if ($isSuperAdmin) {
            $validated['block_token'] = $request->input('block_token');
        } else {
            unset($validated['block_token']);
        }

        // If the client changed, recalculate the free status for the new client.
        if ($license->client_id != $validated['client_id']) {
            $newClientCount = License::where('client_id', $validated['client_id'])->count();
            $newPosition = $newClientCount + 1;
            $license->is_free = ($newPosition % 5 === 0);
            if ($license->is_free) {
                $validated['monthly_fee'] = 0.00;
            }
        } else {
            if ($license->is_free) {
                $validated['monthly_fee'] = 0.00;
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
     * Exclusivo para Superadministrador (softwaremik).
     */
    public function systemStatus(License $license): JsonResponse
    {
        abort_unless(Auth::user()?->isSuperAdmin(), 403, 'Acceso denegado. Solo el Superadministrador puede consultar el estado remoto.');

        $token = $this->getRemoteToken($license);

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-System-Token' => $token,
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get(rtrim($license->url, '/') . '/api/system/status', [
                    'token' => $token,
                ]);

            $body = $response->json() ?? ['success' => false, 'message' => 'Respuesta no válida del sistema remoto.'];
            return response()->json($body, $response->status());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo conectar con el sistema remoto. Verifica que la URL sea accesible.'], 503);
        }
    }

    /**
     * Proxy: Toggle (enable/disable) the remote system via the license's block_token.
     * Exclusivo para Superadministrador (softwaremik).
     */
    public function systemToggle(Request $request, License $license): JsonResponse
    {
        abort_unless(Auth::user()?->isSuperAdmin(), 403, 'Acceso denegado. Solo el Superadministrador puede controlar el estado del sistema.');

        $validated = $request->validate([
            'action' => ['nullable', 'string', 'in:enable,disable'],
        ]);

        $token = $this->getRemoteToken($license);

        try {
            $payload = ['token' => $token];
            if (!empty($validated['action'])) {
                $payload['action'] = $validated['action'];
            }

            $response = Http::timeout(8)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-System-Token' => $token,
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->post(rtrim($license->url, '/') . '/api/system/toggle', $payload);

            $toggleBody = $response->json() ?? ['success' => false, 'message' => 'Respuesta no válida del sistema remoto.'];
            return response()->json($toggleBody, $response->status());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo conectar con el sistema remoto. Verifica que la URL sea accesible.'], 503);
        }
    }

    /**
     * Proxy: Get remote system modules.
     * Exclusivo para Superadministrador (softwaremik).
     */
    public function systemModules(License $license): JsonResponse
    {
        abort_unless(Auth::user()?->isSuperAdmin(), 403, 'Acceso denegado. Solo el Superadministrador puede consultar los módulos remotos.');

        $token = $this->getRemoteToken($license);

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-System-Token' => $token,
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get(rtrim($license->url, '/') . '/api/system/modules', [
                    'token' => $token,
                ]);

            $body = $response->json() ?? ['success' => false, 'message' => 'Respuesta no válida del sistema remoto.'];
            return response()->json($body, $response->status());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo conectar con el sistema remoto para consultar los módulos.'], 503);
        }
    }

    /**
     * Proxy: Toggle a remote system module (e.g. accounting).
     * Exclusivo para Superadministrador (softwaremik).
     */
    public function systemModuleToggle(Request $request, License $license): JsonResponse
    {
        abort_unless(Auth::user()?->isSuperAdmin(), 403, 'Acceso denegado. Solo el Superadministrador puede activar o desactivar módulos.');

        $validated = $request->validate([
            'module' => ['required', 'string'],
            'action' => ['nullable', 'string', 'in:enable,disable'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        $token = $this->getRemoteToken($license);

        try {
            $payload = [
                'token'  => $token,
                'module' => $validated['module'],
            ];
            if (isset($validated['action'])) {
                $payload['action'] = $validated['action'];
            }
            if (isset($validated['enabled'])) {
                $payload['enabled'] = $validated['enabled'];
            }

            $response = Http::timeout(8)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-System-Token' => $token,
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->post(rtrim($license->url, '/') . '/api/system/modules/toggle', $payload);

            $body = $response->json() ?? ['success' => false, 'message' => 'Respuesta no válida del sistema remoto.'];
            return response()->json($body, $response->status());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo conectar con el sistema remoto para actualizar el módulo.'], 503);
        }
    }
}
