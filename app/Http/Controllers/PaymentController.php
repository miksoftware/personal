<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Development;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Models\BankAccount;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search', '');

        $payments = Payment::with(['client', 'development', 'bankAccount'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('client', fn($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('development', fn($q) => $q->where('title', 'like', "%{$search}%"))
                    ->orWhere('reference', 'like', "%{$search}%");
            })
            ->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $clients      = Client::orderBy('name')->get();
        $developments = Development::with('client')->orderBy('title')->get();
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('name')->get();

        return view('payments.index', compact('payments', 'clients', 'developments', 'bankAccounts', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id'            => ['required', 'exists:clients,id'],
            'bank_account_id'      => ['required', 'exists:bank_accounts,id'],
            'development_id'       => ['nullable', 'exists:developments,id'],
            'license_id'           => ['nullable', 'exists:licenses,id'],
            'license_payment_type' => ['nullable', 'string', 'in:mensualidad,instalacion'],
            'amount'               => ['required', 'numeric', 'min:0.01'],
            'method'               => ['required', 'string'],
            'payment_date'         => ['required', 'date'],
            'reference'            => ['nullable', 'string', 'max:255'],
            'notes'                => ['nullable', 'string'],
        ], [
            'client_id.required'       => 'El cliente es obligatorio.',
            'bank_account_id.required' => 'La cuenta de destino es obligatoria.',
            'amount.required'          => 'El monto es obligatorio.',
            'amount.min'               => 'El monto debe ser mayor a cero.',
            'payment_date.required'    => 'La fecha del pago es obligatoria.',
        ]);

        DB::transaction(function () use ($validated) {
            // Obtener la cuenta bancaria
            $account = BankAccount::findOrFail($validated['bank_account_id']);
            
            // Si el método no se envió explícitamente (o para normalizar), usamos el nombre de la cuenta
            if (!isset($validated['method']) || empty($validated['method'])) {
                $validated['method'] = strtolower($account->name);
            }

            // Crear el pago
            $payment = Payment::create($validated);

            // Actualizar el saldo de la cuenta
            $account->increment('current_balance', $validated['amount']);
        });

        return redirect()->route('payments.index')
            ->with('status', '¡Pago registrado y saldo actualizado exitosamente!');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        DB::transaction(function () use ($payment) {
            // Si el pago tenía una cuenta asociada, restamos el saldo
            if ($payment->bank_account_id) {
                $account = BankAccount::find($payment->bank_account_id);
                if ($account) {
                    $account->decrement('current_balance', $payment->amount);
                }
            }
            $payment->delete();
        });

        return redirect()->route('payments.index')
            ->with('status', 'Pago eliminado y saldo actualizado.');
    }
}
