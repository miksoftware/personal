<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankAccountAdjustment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class BankAccountController extends Controller
{
    /**
     * Display a listing of the bank accounts.
     */
    public function index(): View
    {
        $accounts = BankAccount::orderBy('name')->get();
        return view('bank_accounts.index', compact('accounts'));
    }

    /**
     * Display a movement report for the selected bank account.
     */
    public function show(BankAccount $bankAccount): View
    {
        $bankAccount->load([
            'payments.client',
            'payments.development',
            'payments.license',
            'incomes',
            'expenses',
            'adjustments',
        ]);

        $movements = collect();

        foreach ($bankAccount->payments as $payment) {
            $movements->push([
                'date' => $payment->payment_date,
                'type' => 'ingreso_cliente',
                'label' => 'Pago recibido',
                'detail' => $payment->client?->name ?: 'Cliente',
                'reference' => $payment->destination_label,
                'notes' => $payment->notes,
                'amount' => (float) $payment->amount,
                'direction' => 'in',
            ]);
        }

        foreach ($bankAccount->incomes as $income) {
            $movements->push([
                'date' => $income->income_date,
                'type' => 'ingreso_manual',
                'label' => 'Ingreso manual',
                'detail' => $income->description,
                'reference' => $income->category,
                'notes' => $income->notes,
                'amount' => (float) $income->amount,
                'direction' => 'in',
            ]);
        }

        foreach ($bankAccount->expenses as $expense) {
            $movements->push([
                'date' => $expense->expense_date,
                'type' => 'gasto',
                'label' => 'Gasto / Compra',
                'detail' => $expense->description,
                'reference' => $expense->category,
                'notes' => $expense->notes,
                'amount' => (float) $expense->amount,
                'direction' => 'out',
            ]);
        }

        foreach ($bankAccount->adjustments as $adjustment) {
            $movements->push([
                'date' => $adjustment->adjustment_date,
                'type' => 'ajuste',
                'label' => $adjustment->amount >= 0 ? 'Ajuste positivo' : 'Ajuste negativo',
                'detail' => $adjustment->reason,
                'reference' => 'De $' . number_format($adjustment->balance_before, 2) . ' a $' . number_format($adjustment->balance_after, 2),
                'notes' => $adjustment->notes,
                'amount' => (float) abs($adjustment->amount),
                'direction' => $adjustment->amount >= 0 ? 'in' : 'out',
            ]);
        }

        $movements = $movements
            ->sortBy([
                ['date', 'desc'],
                ['label', 'asc'],
            ])
            ->values();

        $totalIncomes  = (float) $movements->where('direction', 'in')->sum('amount');
        $totalExpenses = (float) $movements->where('direction', 'out')->sum('amount');
        $explainedBalance = $totalIncomes - $totalExpenses;
        $manualAdjustment = (float) $bankAccount->current_balance - $explainedBalance;

        return view('bank_accounts.show', compact(
            'bankAccount',
            'movements',
            'totalIncomes',
            'totalExpenses',
            'explainedBalance',
            'manualAdjustment'
        ));
    }

    /**
     * Store a newly created bank account.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'current_balance' => ['required', 'numeric', 'min:0'],
            'account_number'  => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated) {
            $bankAccount = BankAccount::create($validated);

            if ((float) $validated['current_balance'] > 0) {
                $bankAccount->adjustments()->create([
                    'balance_before' => 0,
                    'balance_after' => (float) $validated['current_balance'],
                    'amount' => (float) $validated['current_balance'],
                    'adjustment_date' => now()->toDateString(),
                    'reason' => 'Saldo inicial de la cuenta',
                    'notes' => 'Movimiento generado automaticamente al crear la cuenta.',
                ]);
            }
        });

        return redirect()->route('bank-accounts.index')
            ->with('status', '¡Cuenta bancaria creada exitosamente!');
    }

    /**
     * Update the specified bank account.
     */
    public function update(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'account_number'  => ['nullable', 'string', 'max:255'],
            'is_active'       => ['required', 'boolean'],
        ]);

        $bankAccount->update($validated);

        return redirect()->route('bank-accounts.index')
            ->with('status', '¡Cuenta bancaria actualizada correctamente!');
    }

    /**
     * Store a balance adjustment movement based on the real current balance.
     */
    public function storeAdjustment(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $validated = $request->validate([
            'current_balance' => ['required', 'numeric', 'min:0'],
            'adjustment_date' => ['required', 'date'],
            'notes'           => ['nullable', 'string'],
        ], [
            'current_balance.required' => 'El saldo actual real es obligatorio.',
            'adjustment_date.required' => 'La fecha del ajuste es obligatoria.',
        ]);

        $newBalance = (float) $validated['current_balance'];
        $oldBalance = (float) $bankAccount->current_balance;
        $difference = round($newBalance - $oldBalance, 2);

        if ($difference === 0.0) {
            return redirect()->route('bank-accounts.show', $bankAccount)
                ->withErrors(['error' => 'El saldo ingresado es igual al saldo actual. No se genero ningun ajuste.']);
        }

        DB::transaction(function () use ($bankAccount, $validated, $oldBalance, $newBalance, $difference) {
            $bankAccount->update([
                'current_balance' => $newBalance,
            ]);

            $bankAccount->adjustments()->create([
                'balance_before' => $oldBalance,
                'balance_after' => $newBalance,
                'amount' => $difference,
                'adjustment_date' => $validated['adjustment_date'],
                'reason' => $difference > 0 ? 'Ajuste manual positivo de saldo' : 'Ajuste manual negativo de saldo',
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return redirect()->route('bank-accounts.show', $bankAccount)
            ->with('status', 'Ajuste de saldo registrado correctamente.');
    }

    /**
     * Remove the specified bank account.
     */
    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        if (
            $bankAccount->payments()->exists() ||
            $bankAccount->incomes()->exists() ||
            $bankAccount->expenses()->exists() ||
            $bankAccount->adjustments()->exists()
        ) {
            return redirect()->back()->withErrors(['error' => 'No se puede eliminar una cuenta que tiene movimientos asociados.']);
        }

        $bankAccount->delete();

        return redirect()->route('bank-accounts.index')
            ->with('status', 'Cuenta bancaria eliminada correctamente.');
    }
}
