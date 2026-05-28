<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanController extends Controller
{
    /**
     * Display a listing of loans.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search', '');
        $filter = $request->input('filter', 'all'); // all, pendiente, devuelto, canjeado

        $loans = Loan::with('client')
            ->when($search, function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%")
                      ->orWhereHas('client', fn($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->when($filter !== 'all', fn($q) => $q->where('status', $filter))
            ->orderBy('loan_date', 'desc')
            ->paginate(15);

        $clients = Client::orderBy('name')->get();

        return view('loans.index', compact('loans', 'clients', 'search', 'filter'));
    }

    /**
     * Store a newly created loan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id'   => ['required', 'exists:clients,id'],
            'type'        => ['required', 'in:recibido,entregado'],
            'description' => ['required', 'string', 'max:255'],
            'amount'      => ['required', 'numeric', 'min:0'],
            'loan_date'   => ['required', 'date'],
            'notes'       => ['nullable', 'string'],
        ], [
            'client_id.required'   => 'El cliente es obligatorio.',
            'description.required' => 'La descripción es obligatoria.',
            'amount.required'      => 'El monto es obligatorio.',
            'loan_date.required'   => 'La fecha es obligatoria.',
        ]);

        Loan::create($validated);

        return redirect()->route('loans.index')
            ->with('status', '¡Préstamo registrado exitosamente!');
    }

    /**
     * Update the specified loan.
     */
    public function update(Request $request, Loan $loan): RedirectResponse
    {
        $validated = $request->validate([
            'client_id'   => ['required', 'exists:clients,id'],
            'type'        => ['required', 'in:recibido,entregado'],
            'description' => ['required', 'string', 'max:255'],
            'amount'      => ['required', 'numeric', 'min:0'],
            'loan_date'   => ['required', 'date'],
            'status'      => ['required', 'in:pendiente,devuelto,canjeado'],
            'notes'       => ['nullable', 'string'],
        ], [
            'client_id.required'   => 'El cliente es obligatorio.',
            'description.required' => 'La descripción es obligatoria.',
            'amount.required'      => 'El monto es obligatorio.',
            'loan_date.required'   => 'La fecha es obligatoria.',
        ]);

        $loan->update($validated);

        return redirect()->route('loans.index')
            ->with('status', '¡Préstamo actualizado correctamente!');
    }

    /**
     * Remove the specified loan.
     */
    public function destroy(Loan $loan): RedirectResponse
    {
        $loan->delete();

        return redirect()->route('loans.index')
            ->with('status', 'Préstamo eliminado correctamente.');
    }
}
