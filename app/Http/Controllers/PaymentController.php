<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Credit;
use App\Models\Development;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Models\BankAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\License;

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
            'payment_target'       => ['nullable', 'string', 'in:bank_account,credit'],
            'bank_account_id'      => [
                Rule::requiredIf(fn () => $request->input('payment_target', 'bank_account') === 'bank_account'),
                'nullable',
                'exists:bank_accounts,id',
            ],
            'credit_id'            => [
                Rule::requiredIf(fn () => $request->input('payment_target') === 'credit'),
                'nullable',
                'exists:credits,id',
            ],
            'development_id'       => ['nullable', 'exists:developments,id'],
            'license_id'           => ['nullable', 'exists:licenses,id'],
            'license_payment_type' => ['nullable', 'string', 'in:mensualidad,instalacion'],
            'amount'               => ['required', 'numeric', 'min:0.01'],
            'method'               => ['required', 'string'],
            'payment_date'         => ['required', 'date'],
            'reference'            => ['nullable', 'string', 'max:255'],
            'notes'                => ['nullable', 'string'],
            'redirect_to'          => ['nullable', 'string', 'in:licenses'],
        ], [
            'client_id.required'       => 'El cliente es obligatorio.',
            'bank_account_id.required' => 'La cuenta de destino es obligatoria.',
            'credit_id.required'       => 'Debes seleccionar el crédito al que deseas abonar.',
            'amount.required'          => 'El monto es obligatorio.',
            'amount.min'               => 'El monto debe ser mayor a cero.',
            'payment_date.required'    => 'La fecha del pago es obligatoria.',
        ]);

        $paymentTarget = $validated['payment_target'] ?? 'bank_account';
        $redirectRoute = ($validated['redirect_to'] ?? null) === 'licenses'
            ? route('licenses.index')
            : route('payments.index');

        if ($paymentTarget === 'credit') {
            DB::transaction(function () use ($validated) {
                $credit = Credit::withSum('payments', 'amount')->findOrFail($validated['credit_id']);
                $license = !empty($validated['license_id'])
                    ? License::find($validated['license_id'])
                    : null;

                if ((int) $credit->client_id !== (int) $validated['client_id']) {
                    throw ValidationException::withMessages([
                        'credit_id' => 'El crédito seleccionado no pertenece al cliente de esta licencia.',
                    ]);
                }

                if ($credit->status !== 'activo') {
                    throw ValidationException::withMessages([
                        'credit_id' => 'Solo puedes abonar pagos a créditos activos.',
                    ]);
                }

                if ($credit->balance <= 0) {
                    throw ValidationException::withMessages([
                        'credit_id' => 'El crédito seleccionado ya no tiene saldo pendiente.',
                    ]);
                }

                $typeLabel = ($validated['license_payment_type'] ?? null) === 'instalacion'
                    ? 'Instalación'
                    : 'Mensualidad';

                $concept = $license
                    ? "Canje {$typeLabel}: {$license->url}"
                    : 'Abono aplicado desde pago de licencia';

                $credit->payments()->create([
                    'amount' => $validated['amount'],
                    'concept' => $concept,
                    'method' => 'canje',
                    'payment_date' => $validated['payment_date'],
                    'reference' => $validated['reference'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                $credit->refresh();
                if ($credit->balance <= 0 && $credit->status === 'activo') {
                    $credit->update(['status' => 'pagado']);
                }

                if ($license && ($validated['license_payment_type'] ?? null) === 'mensualidad') {
                    $this->advanceLicenseBillingDate($license);
                }
            });

            return redirect($redirectRoute)
                ->with('status', '¡Pago de licencia aplicado al crédito correctamente, sin mover saldo en cuentas!');
        }

        DB::transaction(function () use ($validated) {
            $account = BankAccount::findOrFail($validated['bank_account_id']);

            if (!isset($validated['method']) || empty($validated['method'])) {
                $validated['method'] = strtolower($account->name);
            }

            Payment::create($validated);
            $account->increment('current_balance', $validated['amount']);

            if (!empty($validated['license_id']) && ($validated['license_payment_type'] ?? null) === 'mensualidad') {
                $license = License::find($validated['license_id']);
                if ($license) {
                    $this->advanceLicenseBillingDate($license);
                }
            }
        });

        return redirect($redirectRoute)
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

    private function advanceLicenseBillingDate(License $license): void
    {
        $cycle = $license->billing_cycle;
        $currentNextBillingDate = \Carbon\Carbon::parse($license->next_billing_date);
        
        switch ($cycle) {
            case 'trimestral':
                $license->next_billing_date = $currentNextBillingDate->addMonths(3);
                break;
            case 'semestral':
                $license->next_billing_date = $currentNextBillingDate->addMonths(6);
                break;
            case 'anual':
                $license->next_billing_date = $currentNextBillingDate->addYear();
                break;
            case 'mensual':
            default:
                $license->next_billing_date = $currentNextBillingDate->addMonth();
                break;
        }
        $license->save();
    }
}
