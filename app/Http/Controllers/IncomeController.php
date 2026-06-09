<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Income;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class IncomeController extends Controller
{
    /**
     * List all incomes with search and summary cards.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search', '');

        $incomes = Income::with(['bankAccount'])
            ->when($search, function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%")
                      ->orWhereHas('bankAccount', function ($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
            })
            ->orderBy('income_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $bankAccounts = BankAccount::where('is_active', true)->orderBy('name')->get();

        return view('incomes.index', compact('incomes', 'search', 'bankAccounts'));
    }

    /**
     * Store a new income and update bank balance.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'description'     => ['required', 'string', 'max:255'],
            'category'        => ['nullable', 'string', 'max:255'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'income_date'     => ['required', 'date'],
            'reference'       => ['nullable', 'string', 'max:255'],
            'notes'           => ['nullable', 'string'],
        ], [
            'description.required' => 'La descripción es obligatoria.',
            'amount.required'      => 'El monto es obligatorio.',
            'amount.min'           => 'El monto debe ser mayor a cero.',
            'bank_account_id.required' => 'Debes seleccionar la cuenta bancaria de destino.',
            'income_date.required' => 'La fecha es obligatoria.',
        ]);

        DB::transaction(function () use ($validated) {
            $income = Income::create($validated);

            // Incrementar el saldo de la cuenta
            $account = BankAccount::find($income->bank_account_id);
            $account->increment('current_balance', $income->amount);
        });

        return redirect()->route('incomes.index')
            ->with('status', '¡Ingreso registrado exitosamente!');
    }

    /**
     * Update an existing income (handles balance adjustment).
     */
    public function update(Request $request, Income $income): RedirectResponse
    {
        $validated = $request->validate([
            'description'     => ['required', 'string', 'max:255'],
            'category'        => ['nullable', 'string', 'max:255'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'income_date'     => ['required', 'date'],
            'reference'       => ['nullable', 'string', 'max:255'],
            'notes'           => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $income) {
            // Revertir el saldo anterior (restar lo que se había sumado)
            $oldAccount = BankAccount::find($income->bank_account_id);
            if ($oldAccount) {
                $oldAccount->decrement('current_balance', $income->amount);
            }

            // Actualizar el ingreso
            $income->update($validated);

            // Aplicar el nuevo saldo (sumar el nuevo monto)
            $newAccount = BankAccount::find($income->bank_account_id);
            if ($newAccount) {
                $newAccount->increment('current_balance', $income->amount);
            }
        });

        return redirect()->route('incomes.index')
            ->with('status', '¡Ingreso actualizado correctamente!');
    }

    /**
     * Delete an income and revert bank balance.
     */
    public function destroy(Income $income): RedirectResponse
    {
        DB::transaction(function () use ($income) {
            // Revertir el saldo si existía cuenta bancaria
            if ($income->bank_account_id) {
                $account = BankAccount::find($income->bank_account_id);
                if ($account) {
                    $account->decrement('current_balance', $income->amount);
                }
            }

            $income->delete();
        });

        return redirect()->route('incomes.index')
            ->with('status', 'Ingreso eliminado correctamente y saldo revertido.');
    }
}