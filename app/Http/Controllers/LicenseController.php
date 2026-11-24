<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\License;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LicenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $billingFilter = $request->input('billing_filter', 'all');
        $allowedFilters = ['all', 'due', 'up_to_date', 'anual_total', 'with_contract', 'no_contract', 'free'];
        if (!in_array($billingFilter, $allowedFilters, true)) {
            $billingFilter = 'all';
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 15, 30, 50, 100], true)) {
            $perPage = 10;
        }

        $today = Carbon::now()->startOfDay()->toDateString();

        $licenses = License::with('client')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('url', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($clientQuery) use ($search) {
                            $clientQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($billingFilter !== 'all', function ($query) use ($billingFilter, $today) {
                match ($billingFilter) {
                    'due' => $query->where('is_free', false)->where(function ($q) use ($today) {
                        $q->whereDate('next_billing_date', '<=', $today)
                            ->orWhere(function ($sub) use ($today) {
                                $sub->where('billing_cycle', '!=', 'anual_total')
                                    ->where('setup_fee', '>', 0)
                                    ->whereNotNull('next_setup_billing_date')
                                    ->whereDate('next_setup_billing_date', '<=', $today);
                            });
                    }),
                    'up_to_date' => $query->where('is_free', false)
                        ->whereDate('next_billing_date', '>', $today)
                        ->where(function ($q) use ($today) {
                            $q->where('billing_cycle', 'anual_total')
                                ->orWhere('setup_fee', '<=', 0)
                                ->orWhereNull('next_setup_billing_date')
                                ->orWhereDate('next_setup_billing_date', '>', $today);
                        }),
                    'anual_total' => $query->where('billing_cycle', 'anual_total'),
                    'with_contract' => $query->whereNotNull('contract_path')->where('contract_path', '!=', ''),
                    'no_contract' => $query->where(function ($q) {
                        $q->whereNull('contract_path')->orWhere('contract_path', '');
                    }),
                    'free' => $query->where('is_free', true),
                    default => null,
                };
            })
            ->orderBy('is_free', 'asc')
            ->orderBy('next_billing_date', 'asc')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)->appends($request->query());

        // Summary metrics across all licenses of the authenticated tenant
        $allUserLicenses = License::select([
            'id',
            'status',
            'billing_cycle',
            'monthly_fee',
            'setup_fee',
            'next_billing_date',
            'next_setup_billing_date',
            'is_free',
            'contract_path',
        ])->get();

        $totalLicensesCount = $allUserLicenses->count();
        $activeLicensesCount = $allUserLicenses->where('status', 'activa')->count();
        $contractsCount = $allUserLicenses->filter(fn ($lic) => $lic->hasContract())->count();
        $annualTotalCount = $allUserLicenses->where('billing_cycle', 'anual_total')->count();

        $overdueLicensesCount = 0;
        $totalDueNowAmount = 0.0;
        foreach ($allUserLicenses as $lic) {
            $breakdown = $lic->billing_breakdown;
            if ($breakdown['due_now_amount'] > 0) {
                $overdueLicensesCount++;
                $totalDueNowAmount += $breakdown['due_now_amount'];
            }
        }

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

        // Retrieve any cached module states for the paginated licenses
        $modulesMap = [];
        foreach ($licenses as $lic) {
            $cached = Cache::get("license_modules_{$lic->id}");
            if (!empty($cached)) {
                $modulesMap[$lic->id] = $cached;
            }
        }

        return view('licenses.index', compact(
            'licenses',
            'clients',
            'search',
            'billingFilter',
            'creditsByClient',
            'modulesMap',
            'totalLicensesCount',
            'activeLicensesCount',
            'overdueLicensesCount',
            'totalDueNowAmount',
            'contractsCount',
            'annualTotalCount'
        ));
    }

    /**
     * Helper to get remote admin token with fallback.
     */
    private function getRemoteToken(License $license): string
    {
        return !empty($license->block_token) ? $license->block_token : 'adminmikpos123';
    }

    /**
     * Validate and normalize license input for store/update.
     */
    private function validateLicenseData(Request $request, bool $isSuperAdmin): array
    {
        $isAnnualTotal = $request->input('billing_cycle') === 'anual_total';

        $rules = [
            'client_id' => ['required', Rule::exists('clients', 'id')->where('user_id', Auth::id())],
            'url' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:activa,suspendida,vencida'],
            'billing_cycle' => ['required', 'string', 'in:mensual,trimestral,semestral,anual,anual_total'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'setup_fee' => [$isAnnualTotal ? 'nullable' : 'required', 'numeric', 'min:0'],
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
            'monthly_fee.required' => $isAnnualTotal ? 'El valor total anual es obligatorio.' : 'La tarifa mensual es obligatoria.',
            'monthly_fee.numeric' => $isAnnualTotal ? 'El valor total anual debe ser numérico.' : 'La tarifa mensual debe ser un valor numérico.',
            'setup_fee.required' => 'El valor de anualidad/instalación es obligatorio.',
            'setup_fee.numeric' => 'El valor de anualidad/instalación debe ser un valor numérico.',
            'next_billing_date.required' => 'La fecha de próxima facturación es obligatoria.',
            'next_setup_billing_date.date' => 'La fecha de próxima anualidad no es válida.',
        ]);

        if ($validated['billing_cycle'] === 'anual_total') {
            $validated['setup_fee'] = 0.00;
            $validated['next_setup_billing_date'] = null;
        } else {
            $validated['setup_fee'] = $validated['setup_fee'] ?? 0.00;
            if ((float) $validated['setup_fee'] <= 0) {
                $validated['next_setup_billing_date'] = null;
            }
        }

        return $validated;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $isSuperAdmin = Auth::user()?->isSuperAdmin();
        $validated = $this->validateLicenseData($request, (bool) $isSuperAdmin);

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
        $validated = $this->validateLicenseData($request, (bool) $isSuperAdmin);

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
        if ($license->contract_path && Storage::disk('local')->exists($license->contract_path)) {
            Storage::disk('local')->delete($license->contract_path);
        }

        $license->delete();

        return redirect()->route('licenses.index')
            ->with('status', '¡Licencia eliminada con éxito!');
    }

    /**
     * Upload or replace the PDF contract attached to a license.
     */
    public function uploadContract(Request $request, License $license)
    {
        abort_unless((int) $license->user_id === (int) Auth::id(), 403);

        $validated = $request->validate([
            'contract_pdf' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240'],
        ], [
            'contract_pdf.required' => 'Debes seleccionar un archivo PDF de contrato.',
            'contract_pdf.file' => 'El archivo enviado no es válido.',
            'contract_pdf.mimes' => 'El contrato debe estar en formato PDF (.pdf).',
            'contract_pdf.mimetypes' => 'El archivo seleccionado debe ser un documento PDF válido.',
            'contract_pdf.max' => 'El contrato PDF no puede superar los 10 MB.',
        ]);

        $file = $validated['contract_pdf'];
        $previousPath = $license->contract_path;

        $newPath = Storage::disk('local')->putFile('contracts/user_' . Auth::id(), $file);

        $license->update([
            'contract_path' => $newPath,
            'contract_original_name' => $file->getClientOriginalName(),
            'contract_uploaded_at' => now(),
        ]);

        if ($previousPath && $previousPath !== $newPath && Storage::disk('local')->exists($previousPath)) {
            Storage::disk('local')->delete($previousPath);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Contrato PDF cargado correctamente.',
                'contract_name' => $license->contract_original_name,
                'contract_url' => route('licenses.contract.show', $license),
                'uploaded_at' => $license->contract_uploaded_at?->format('d M Y H:i'),
            ]);
        }

        return redirect()->route('licenses.index')
            ->with('status', '¡Contrato PDF cargado correctamente!');
    }

    /**
     * Stream the PDF contract inline (for modal viewer) or as download.
     */
    public function showContract(Request $request, License $license)
    {
        abort_unless((int) $license->user_id === (int) Auth::id(), 403);

        if (!$license->contract_path || !Storage::disk('local')->exists($license->contract_path)) {
            abort(404, 'No hay contrato PDF disponible para esta licencia.');
        }

        $rawName = $license->contract_original_name ?: "contrato_licencia_{$license->id}.pdf";
        $asciiName = Str::ascii($rawName);
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $asciiName) ?: "contrato_{$license->id}.pdf";
        if (!str_ends_with(strtolower($safeName), '.pdf')) {
            $safeName .= '.pdf';
        }

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response()->file(Storage::disk('local')->path($license->contract_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$safeName}\"",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Remove the attached PDF contract from a license.
     */
    public function destroyContract(Request $request, License $license)
    {
        abort_unless((int) $license->user_id === (int) Auth::id(), 403);

        if ($license->contract_path && Storage::disk('local')->exists($license->contract_path)) {
            Storage::disk('local')->delete($license->contract_path);
        }

        $license->update([
            'contract_path' => null,
            'contract_original_name' => null,
            'contract_uploaded_at' => null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Contrato PDF eliminado correctamente.',
            ]);
        }

        return redirect()->route('licenses.index')
            ->with('status', 'Contrato PDF eliminado correctamente.');
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
            $modules = [];
            $connected = false;

            // 1. Consultar endpoint principal de módulos
            try {
                $response = Http::timeout(6)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'X-System-Token' => $token,
                        'Authorization' => 'Bearer ' . $token,
                    ])
                    ->get(rtrim($license->url, '/') . '/api/system/modules', [
                        'token' => $token,
                    ]);

                if ($response->successful()) {
                    $body = $response->json();
                    if (!empty($body['modules']) && is_array($body['modules'])) {
                        $modules = $body['modules'];
                        $connected = true;
                    }
                }
            } catch (\Exception $e) {
                // Continuar a verificar si el endpoint dedicado de tienda responde
            }

            // 2. Si la tienda no está en los módulos, consultar el endpoint dedicado de tienda (/api/system/shop/status)
            if (!isset($modules['ecommerce']) && !isset($modules['shop']) && !isset($modules['tienda'])) {
                try {
                    $shopRes = Http::timeout(4)
                        ->withHeaders([
                            'Accept' => 'application/json',
                            'X-System-Token' => $token,
                            'Authorization' => 'Bearer ' . $token,
                        ])
                        ->get(rtrim($license->url, '/') . '/api/system/shop/status', [
                            'token' => $token,
                        ]);

                    if ($shopRes->successful()) {
                        $shopData = $shopRes->json();
                        $connected = true;
                        if (isset($shopData['module'])) {
                            $modules['ecommerce'] = $shopData['module'];
                        } elseif (isset($shopData['success'])) {
                            $isShopEnabled = ($shopData['status'] ?? '') === 'enabled' || ($shopData['enabled'] ?? false);
                            $modules['ecommerce'] = [
                                'key' => 'ecommerce',
                                'name' => 'Tienda Virtual',
                                'description' => 'Módulo de tienda en línea / e-commerce (catálogo web público, pedidos virtuales y checkout).',
                                'enabled' => (bool)$isShopEnabled,
                                'status' => $isShopEnabled ? 'enabled' : 'disabled',
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // Silently continue
                }
            }

            if (!$connected && empty($modules)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo conectar con el sistema remoto para consultar los módulos.',
                ], 503);
            }

            // Garantizar claves estándar accounting y ecommerce
            if (!isset($modules['accounting'])) {
                $modules['accounting'] = [
                    'key' => 'accounting',
                    'name' => 'Contabilidad',
                    'description' => 'Plan de cuentas PUC, comprobantes, asientos y reportes contables oficiales.',
                    'enabled' => false,
                    'status' => 'disabled',
                ];
            }
            if (!isset($modules['ecommerce'])) {
                // Si existe shop o tienda, normalizar a ecommerce
                if (isset($modules['shop'])) {
                    $modules['ecommerce'] = $modules['shop'];
                    $modules['ecommerce']['key'] = 'ecommerce';
                } elseif (isset($modules['tienda'])) {
                    $modules['ecommerce'] = $modules['tienda'];
                    $modules['ecommerce']['key'] = 'ecommerce';
                } else {
                    $modules['ecommerce'] = [
                        'key' => 'ecommerce',
                        'name' => 'Tienda Virtual',
                        'description' => 'Módulo de tienda en línea / e-commerce (catálogo web público, pedidos virtuales y checkout).',
                        'enabled' => false,
                        'status' => 'disabled',
                    ];
                }
            }

            // Cachear el estado de los módulos para despliegue instantáneo en la tabla
            Cache::put("license_modules_{$license->id}", $modules, now()->addMinutes(30));

            return response()->json([
                'success' => true,
                'modules' => $modules,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo conectar con el sistema remoto para consultar los módulos.'], 503);
        }
    }

    /**
     * Proxy: Toggle a remote system module (e.g. accounting, ecommerce).
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

        $rawModule = strtolower(trim($validated['module']));
        $isEcommerce = in_array($rawModule, ['ecommerce', 'tienda', 'tiendas', 'shop', 'ecommerce_orders']);
        $moduleKey = $isEcommerce ? 'ecommerce' : $rawModule;

        $token = $this->getRemoteToken($license);

        try {
            $payload = [
                'token'  => $token,
                'module' => $moduleKey,
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

            // Si es tienda/ecommerce y el endpoint genérico falla o no existe, intentar fallback al endpoint dedicado /api/system/shop/toggle
            if (!$response->successful() && $isEcommerce) {
                $shopPayload = ['token' => $token];
                if (isset($validated['action'])) {
                    $shopPayload['action'] = $validated['action'];
                }
                $fallbackResponse = Http::timeout(8)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'X-System-Token' => $token,
                        'Authorization' => 'Bearer ' . $token,
                    ])
                    ->post(rtrim($license->url, '/') . '/api/system/shop/toggle', $shopPayload);

                if ($fallbackResponse->successful()) {
                    $body = $fallbackResponse->json() ?? [];
                    $isEnabled = $body['module']['enabled'] ?? (($body['status'] ?? ($body['module']['status'] ?? '')) === 'enabled');
                    $this->updateCachedModuleStatus($license->id, 'ecommerce', (bool)$isEnabled);
                    return response()->json([
                        'success' => true,
                        'module' => 'ecommerce',
                        'name' => 'Tienda Virtual',
                        'status' => $isEnabled ? 'enabled' : 'disabled',
                        'enabled' => (bool)$isEnabled,
                        'message' => $body['message'] ?? ($isEnabled ? "Módulo 'Tienda Virtual' habilitado correctamente." : "Módulo 'Tienda Virtual' deshabilitado correctamente."),
                    ]);
                }
            }

            $body = $response->json() ?? ['success' => false, 'message' => 'Respuesta no válida del sistema remoto.'];

            if ($response->successful()) {
                $isEnabled = $body['enabled'] ?? (($body['status'] ?? '') === 'enabled');
                $this->updateCachedModuleStatus($license->id, $moduleKey, (bool)$isEnabled);
            }

            return response()->json($body, $response->status());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo conectar con el sistema remoto para actualizar el módulo.'], 503);
        }
    }

    /**
     * Helper to update locally cached module status after toggle.
     */
    private function updateCachedModuleStatus(int $licenseId, string $moduleKey, bool $isEnabled): void
    {
        $cached = Cache::get("license_modules_{$licenseId}");
        if (is_array($cached)) {
            if (isset($cached[$moduleKey])) {
                $cached[$moduleKey]['enabled'] = $isEnabled;
                $cached[$moduleKey]['status'] = $isEnabled ? 'enabled' : 'disabled';
            } else {
                $cached[$moduleKey] = [
                    'key' => $moduleKey,
                    'name' => $moduleKey === 'accounting' ? 'Contabilidad' : 'Tienda Virtual',
                    'enabled' => $isEnabled,
                    'status' => $isEnabled ? 'enabled' : 'disabled',
                ];
            }
            Cache::put("license_modules_{$licenseId}", $cached, now()->addMinutes(30));
        }
    }
}
