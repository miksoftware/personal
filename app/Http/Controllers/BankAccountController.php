<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
     * Store a newly created bank account.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'current_balance' => ['required', 'numeric', 'min:0'],
            'account_number'  => ['nullable', 'string', 'max:255'],
        ]);

        BankAccount::create($validated);

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
            'current_balance' => ['required', 'numeric', 'min:0'],
            'account_number'  => ['nullable', 'string', 'max:255'],
            'is_active'       => ['required', 'boolean'],
        ]);

        $bankAccount->update($validated);

        return redirect()->route('bank-accounts.index')
            ->with('status', '¡Cuenta bancaria actualizada correctamente!');
    }

    /**
     * Remove the specified bank account.
     */
    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        if ($bankAccount->payments()->exists()) {
            return redirect()->back()->withErrors(['error' => 'No se puede eliminar una cuenta que tiene pagos asociados.']);
        }

        $bankAccount->delete();

        return redirect()->route('bank-accounts.index')
            ->with('status', 'Cuenta bancaria eliminada correctamente.');
    }
}
