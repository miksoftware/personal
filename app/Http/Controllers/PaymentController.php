<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Development;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search', '');

        $payments = Payment::with(['client', 'development'])
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

        return view('payments.index', compact('payments', 'clients', 'developments', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id'      => ['required', 'exists:clients,id'],
            'development_id' => ['nullable', 'exists:developments,id'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'method'         => ['required', 'in:efectivo,nequi,bancolombia'],
            'payment_date'   => ['required', 'date'],
            'reference'      => ['nullable', 'string', 'max:255'],
            'notes'          => ['nullable', 'string'],
        ], [
            'client_id.required'   => 'El cliente es obligatorio.',
            'client_id.exists'     => 'El cliente seleccionado no es válido.',
            'amount.required'      => 'El monto es obligatorio.',
            'amount.min'           => 'El monto debe ser mayor a cero.',
            'method.required'      => 'El método de pago es obligatorio.',
            'method.in'            => 'Método de pago no válido.',
            'payment_date.required'=> 'La fecha del pago es obligatoria.',
        ]);

        Payment::create($validated);

        return redirect()->route('payments.index')
            ->with('status', '¡Pago registrado exitosamente!');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $payment->delete();

        return redirect()->route('payments.index')
            ->with('status', 'Pago eliminado correctamente.');
    }
}
