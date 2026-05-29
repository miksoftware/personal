<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Credit;
use App\Models\CreditPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreditController extends Controller
{
    /**
     * List all credits with search and summary cards.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search', '');

        $credits = Credit::with(['client'])
            ->withSum('payments', 'amount')
            ->when($search, function ($query) use ($search) {
                $query->where('creditor_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderByRaw("CASE WHEN status = 'activo' THEN 0 WHEN status = 'pagado' THEN 1 ELSE 2 END")
            ->orderBy('credit_date', 'desc')
            ->paginate(15);

        $clients = Client::orderBy('name')->get();

        return view('credits.index', compact('credits', 'search', 'clients'));
    }

    /**
     * Show a single credit with its payment history.
     */
    public function show(Credit $credit): View
    {
        $credit->load(['client', 'payments' => fn($q) => $q->orderBy('payment_date', 'desc')->orderBy('created_at', 'desc')]);
        
        $recentClientPayments = collect();
        $recentClientLicenses = collect();
        $pendingDevelopments  = collect();
        $pendingLoans         = collect();

        if ($credit->client_id) {
            // Get all used concepts in ANY credit of this client to avoid duplicates
            $usedConcepts = \App\Models\CreditPayment::whereHas('credit', fn($q) => $q->where('client_id', $credit->client_id))
                ->pluck('concept')
                ->toArray();

            $recentClientPayments = \App\Models\Payment::where('client_id', $credit->client_id)
                ->orderBy('payment_date', 'desc')
                ->limit(10)
                ->get()
                ->filter(function($p) use ($usedConcepts) {
                    $concept = 'Pago mensualidad / ' . ($p->notes ?: 'Servicio');
                    return !in_array($concept, $usedConcepts);
                });
            
            $recentClientLicenses = \App\Models\License::where('client_id', $credit->client_id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->filter(function($l) use ($usedConcepts, $credit) {
                    // Verificamos si ya existe un PAGO FÍSICO registrado para este ciclo/licencia
                    // que NO sea un canje (es decir, que no esté en usedConcepts ya como abono de crédito)
                    
                    $monthlyConcept = 'Canje Mensualidad: ' . $l->url;
                    $setupConcept   = 'Canje Instalación: ' . $l->url;

                    // Si ya se registró un pago físico de mensualidad para esta licencia en el mes actual, lo quitamos de sugerencias
                    $hasMonthlyPayment = \App\Models\Payment::where('license_id', $l->id)
                        ->where('license_payment_type', 'mensualidad')
                        ->whereMonth('payment_date', now()->month)
                        ->whereYear('payment_date', now()->year)
                        ->exists();

                    // Si ya se registró un pago físico de instalación, lo quitamos
                    $hasSetupPayment = \App\Models\Payment::where('license_id', $l->id)
                        ->where('license_payment_type', 'instalacion')
                        ->exists();

                    // Guardamos estos estados en el objeto para usarlos en la vista
                    $l->already_paid_monthly = $hasMonthlyPayment;
                    $l->already_paid_setup = $hasSetupPayment;

                    // Solo sugerimos si NO se ha pagado físicamente Y NO se ha canjeado antes
                    return (!$hasMonthlyPayment && !in_array($monthlyConcept, $usedConcepts)) || 
                           (!$hasSetupPayment && !in_array($setupConcept, $usedConcepts));
                });

            // Loans I gave to the client (Yo presté) that are still pending
            $pendingLoans = \App\Models\Loan::where('client_id', $credit->client_id)
                ->where('type', 'entregado')
                ->where('status', 'pendiente')
                ->orderBy('loan_date', 'desc')
                ->get()
                ->filter(function($l) use ($usedConcepts) {
                    $concept = 'Canje Préstamo: ' . $l->description;
                    return !in_array($concept, $usedConcepts);
                });

            // Find developments with pending balance using FIFO logic
            $allDevs = \App\Models\Development::where('client_id', $credit->client_id)
                ->orderBy('created_at', 'asc')
                ->get();
            $allPayments = \App\Models\Payment::where('client_id', $credit->client_id)->get();
            $globalPaid = (float) $allPayments->whereNull('development_id')->sum('amount');

            foreach ($allDevs as $dev) {
                // Check if this development was already exchanged for a credit
                $concept = 'Canje Desarrollo: ' . $dev->title;
                if (in_array($concept, $usedConcepts)) continue;

                $specificPaid = (float) $allPayments->where('development_id', $dev->id)->sum('amount');
                $devBalance = $dev->amount - $specificPaid;
                
                if ($devBalance > 0 && $globalPaid > 0) {
                    $applied = min($devBalance, $globalPaid);
                    $devBalance -= $applied;
                    $globalPaid -= $applied;
                }

                if ($devBalance > 0) {
                    $dev->pending_amount = $devBalance;
                    $pendingDevelopments->push($dev);
                }
            }
        }

        return view('credits.show', compact('credit', 'recentClientPayments', 'recentClientLicenses', 'pendingDevelopments', 'pendingLoans', 'usedConcepts'));
    }

    /**
     * Store a new credit.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id'          => ['nullable', 'exists:clients,id'],
            'type'               => ['required', 'in:proveedor,personal'],
            'creditor_name'      => ['required', 'string', 'max:255'],
            'description'        => ['required', 'string', 'max:255'],
            'total_amount'       => ['required', 'numeric', 'min:0.01'],
            'installment_value'  => ['nullable', 'numeric', 'min:0'],
            'total_installments' => ['nullable', 'integer', 'min:1'],
            'credit_date'        => ['required', 'date'],
            'notes'              => ['nullable', 'string'],
        ], [
            'creditor_name.required' => 'El nombre del acreedor es obligatorio.',
            'description.required'   => 'La descripción es obligatoria.',
            'total_amount.required'  => 'El monto total es obligatorio.',
            'total_amount.min'       => 'El monto debe ser mayor a cero.',
            'credit_date.required'   => 'La fecha del crédito es obligatoria.',
            'client_id.exists'       => 'El cliente seleccionado no es válido.',
        ]);

        Credit::create($validated);

        return redirect()->route('credits.index')
            ->with('status', '¡Crédito registrado exitosamente!');
    }

    /**
     * Update an existing credit.
     */
    public function update(Request $request, Credit $credit): RedirectResponse
    {
        $validated = $request->validate([
            'client_id'          => ['nullable', 'exists:clients,id'],
            'type'               => ['required', 'in:proveedor,personal'],
            'creditor_name'      => ['required', 'string', 'max:255'],
            'description'        => ['required', 'string', 'max:255'],
            'total_amount'       => ['required', 'numeric', 'min:0.01'],
            'installment_value'  => ['nullable', 'numeric', 'min:0'],
            'total_installments' => ['nullable', 'integer', 'min:1'],
            'credit_date'        => ['required', 'date'],
            'status'             => ['required', 'in:activo,pagado,cancelado'],
            'notes'              => ['nullable', 'string'],
        ], [
            'creditor_name.required' => 'El nombre del acreedor es obligatorio.',
            'description.required'   => 'La descripción es obligatoria.',
            'total_amount.required'  => 'El monto total es obligatorio.',
            'total_amount.min'       => 'El monto debe ser mayor a cero.',
            'credit_date.required'   => 'La fecha del crédito es obligatoria.',
            'status.required'        => 'El estado es obligatorio.',
            'client_id.exists'       => 'El cliente seleccionado no es válido.',
        ]);

        $credit->update($validated);

        return redirect()->route('credits.index')
            ->with('status', '¡Crédito actualizado correctamente!');
    }

    /**
     * Delete a credit (and its payments via cascade).
     */
    public function destroy(Credit $credit): RedirectResponse
    {
        $credit->delete();

        return redirect()->route('credits.index')
            ->with('status', 'Crédito eliminado correctamente.');
    }

    /**
     * Store a payment (abono) for a specific credit.
     */
    public function storePayment(Request $request, Credit $credit): RedirectResponse
    {
        $validated = $request->validate([
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'concept'      => ['required', 'string', 'max:255'],
            'method'       => ['required', 'in:efectivo,nequi,bancolombia,canje'],
            'payment_date' => ['required', 'date'],
            'reference'    => ['nullable', 'string', 'max:255'],
            'notes'        => ['nullable', 'string'],
        ], [
            'amount.required'       => 'El monto del abono es obligatorio.',
            'amount.min'            => 'El monto debe ser mayor a cero.',
            'concept.required'      => 'El concepto del abono es obligatorio.',
            'method.required'       => 'El método de pago es obligatorio.',
            'payment_date.required' => 'La fecha del abono es obligatoria.',
        ]);

        $credit->payments()->create($validated);

        // Auto-mark as "pagado" if balance reached zero
        $credit->refresh();
        if ($credit->balance <= 0 && $credit->status === 'activo') {
            $credit->update(['status' => 'pagado']);
        }

        return redirect()->route('credits.show', $credit)
            ->with('status', '¡Abono registrado exitosamente!');
    }

    /**
     * Delete a payment (abono) from a credit.
     */
    public function destroyPayment(Credit $credit, CreditPayment $creditPayment): RedirectResponse
    {
        $creditPayment->delete();

        // Re-check status: if credit was "pagado" but now has balance, set back to "activo"
        $credit->refresh();
        if ($credit->balance > 0 && $credit->status === 'pagado') {
            $credit->update(['status' => 'activo']);
        }

        return redirect()->route('credits.show', $credit)
            ->with('status', 'Abono eliminado correctamente.');
    }
}
