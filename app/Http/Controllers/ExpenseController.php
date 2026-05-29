<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    /**
     * List all expenses with search and summary cards.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search', '');

        $expenses = Expense::with(['bankAccount'])
            ->when($search, function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%")
                      ->orWhere('method', 'like', "%{$search}%");
            })
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $bankAccounts = BankAccount::where('is_active', true)->orderBy('name')->get();

        return view('expenses.index', compact('expenses', 'search', 'bankAccounts'));
    }

    /**
     * Store a new expense and update bank balance if applicable.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'description'     => ['required', 'string', 'max:255'],
            'category'        => ['nullable', 'string', 'max:255'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'method'          => ['required', 'string', 'max:255'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'expense_date'    => ['required', 'date'],
            'reference'       => ['nullable', 'string', 'max:255'],
            'notes'           => ['nullable', 'string'],
        ], [
            'description.required' => 'La descripción es obligatoria.',
            'amount.required'      => 'El monto es obligatorio.',
            'amount.min'           => 'El monto debe ser mayor a cero.',
            'method.required'      => 'El medio de pago es obligatorio.',
            'expense_date.required' => 'La fecha es obligatoria.',
        ]);

        DB::transaction(function () use ($validated) {
            $expense = Expense::create($validated);

            // Si se seleccionó una cuenta bancaria, descontar el saldo
            if ($expense->bank_account_id) {
                $account = BankAccount::find($expense->bank_account_id);
                $account->decrement('current_balance', $expense->amount);
            }
        });

        return redirect()->route('expenses.index')
            ->with('status', '¡Gasto registrado exitosamente!');
    }

    /**
     * Update an existing expense (handles balance adjustment).
     */
    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $validated = $request->validate([
            'description'     => ['required', 'string', 'max:255'],
            'category'        => ['nullable', 'string', 'max:255'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'method'          => ['required', 'string', 'max:255'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'expense_date'    => ['required', 'date'],
            'reference'       => ['nullable', 'string', 'max:255'],
            'notes'           => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $expense) {
            // Revertir el saldo anterior si existía cuenta bancaria
            if ($expense->bank_account_id) {
                $oldAccount = BankAccount::find($expense->bank_account_id);
                if ($oldAccount) {
                    $oldAccount->increment('current_balance', $expense->amount);
                }
            }

            // Actualizar el gasto
            $expense->update($validated);

            // Aplicar el nuevo saldo si existe cuenta bancaria
            if ($expense->bank_account_id) {
                $newAccount = BankAccount::find($expense->bank_account_id);
                if ($newAccount) {
                    $newAccount->decrement('current_balance', $expense->amount);
                }
            }
        });

        return redirect()->route('expenses.index')
            ->with('status', '¡Gasto actualizado correctamente!');
    }

    /**
     * Delete an expense and revert bank balance.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        DB::transaction(function () use ($expense) {
            // Revertir el saldo si existía cuenta bancaria
            if ($expense->bank_account_id) {
                $account = BankAccount::find($expense->bank_account_id);
                if ($account) {
                    $account->increment('current_balance', $expense->amount);
                }
            }

            $expense->delete();
        });

        return redirect()->route('expenses.index')
            ->with('status', 'Gasto eliminado correctamente y saldo revertido.');
    }
}
