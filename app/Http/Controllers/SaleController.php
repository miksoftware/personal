<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SaleController extends Controller
{
    /**
     * Display a listing of sales (equipment, products, services, others).
     */
    public function index(Request $request): View
    {
        $search   = $request->input('search', '');
        $category = $request->input('category', 'all');
        $status   = $request->input('status', 'all');

        $sales = Sale::with(['client', 'bankAccount'])
            ->when($search, function ($query) use ($search) {
                $query->where('item_name', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('client', fn($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->when($category !== 'all', fn($q) => $q->where('category', $category))
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->orderBy('sale_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Summary metrics
        $totalSalesMonth = (float) Sale::whereMonth('sale_date', now()->month)
            ->whereYear('sale_date', now()->year)
            ->sum('total_amount');

        $totalSalesYear = (float) Sale::whereYear('sale_date', now()->year)
            ->sum('total_amount');

        $pendingSalesAmount = (float) Sale::where('status', 'pendiente')
            ->sum('total_amount');

        $paidSalesCount = Sale::where('status', 'pagado')->count();

        $clients      = Client::orderBy('name')->get();
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('name')->get();

        return view('sales.index', compact(
            'sales',
            'clients',
            'bankAccounts',
            'search',
            'category',
            'status',
            'totalSalesMonth',
            'totalSalesYear',
            'pendingSalesAmount',
            'paidSalesCount'
        ));
    }

    /**
     * Store a newly created sale in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'client_id'       => ['required', Rule::exists('clients', 'id')->where('user_id', $userId)],
            'category'        => ['required', 'string', 'in:equipo,accesorio,servicio,otro'],
            'item_name'       => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'serial_number'   => ['nullable', 'string', 'max:255'],
            'warranty'        => ['nullable', 'string', 'max:255'],
            'quantity'        => ['required', 'integer', 'min:1'],
            'unit_price'      => ['required', 'numeric', 'min:0'],
            'total_amount'    => ['nullable', 'numeric', 'min:0'],
            'sale_date'       => ['required', 'date'],
            'status'          => ['required', 'string', 'in:pendiente,pagado'],
            'bank_account_id' => [
                Rule::requiredIf(fn() => $request->input('status') === 'pagado'),
                'nullable',
                Rule::exists('bank_accounts', 'id')->where('user_id', $userId)
            ],
            'payment_method'  => [
                Rule::requiredIf(fn() => $request->input('status') === 'pagado'),
                'nullable',
                'string',
                'in:efectivo,nequi,bancolombia,transferencia'
            ],
            'notes'           => ['nullable', 'string'],
        ], [
            'client_id.required'       => 'El cliente es obligatorio.',
            'item_name.required'       => 'El nombre del equipo o producto es obligatorio.',
            'category.required'        => 'La categoría es obligatoria.',
            'unit_price.required'      => 'El precio unitario es obligatorio.',
            'sale_date.required'       => 'La fecha de la venta es obligatoria.',
            'bank_account_id.required' => 'Debes seleccionar la cuenta bancaria donde ingresa el dinero.',
            'payment_method.required'  => 'El método de pago es obligatorio cuando la venta está pagada.',
        ]);

        if (empty($validated['total_amount']) || (float) $validated['total_amount'] <= 0) {
            $validated['total_amount'] = (int) $validated['quantity'] * (float) $validated['unit_price'];
        }

        DB::transaction(function () use ($validated) {
            if ($validated['status'] === 'pagado') {
                $validated['paid_at'] = now();
            } else {
                $validated['bank_account_id'] = null;
                $validated['payment_method']  = null;
                $validated['paid_at']         = null;
            }

            $sale = Sale::create($validated);

            // Si se pagó de contado, generar registro en payments e incrementar saldo de la cuenta
            if ($sale->status === 'pagado' && $sale->bank_account_id) {
                Payment::create([
                    'client_id'       => $sale->client_id,
                    'bank_account_id' => $sale->bank_account_id,
                    'sale_id'         => $sale->id,
                    'amount'          => $sale->total_amount,
                    'method'          => $sale->payment_method,
                    'payment_date'    => $sale->sale_date,
                    'reference'       => 'Venta: ' . $sale->item_name . ($sale->serial_number ? " (S/N: {$sale->serial_number})" : ''),
                    'notes'           => 'Pago registrado automáticamente por venta de equipo / producto.',
                ]);

                $account = BankAccount::find($sale->bank_account_id);
                if ($account) {
                    $account->increment('current_balance', $sale->total_amount);
                }
            }
        });

        return redirect()->route('sales.index')
            ->with('status', '¡Venta registrada exitosamente!');
    }

    /**
     * Update the specified sale in storage.
     */
    public function update(Request $request, Sale $sale): RedirectResponse
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'client_id'     => ['required', Rule::exists('clients', 'id')->where('user_id', $userId)],
            'category'      => ['required', 'string', 'in:equipo,accesorio,servicio,otro'],
            'item_name'     => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'warranty'      => ['nullable', 'string', 'max:255'],
            'quantity'      => ['required', 'integer', 'min:1'],
            'unit_price'    => ['required', 'numeric', 'min:0'],
            'total_amount'  => ['nullable', 'numeric', 'min:0'],
            'sale_date'     => ['required', 'date'],
            'notes'         => ['nullable', 'string'],
        ], [
            'client_id.required'  => 'El cliente es obligatorio.',
            'item_name.required'  => 'El nombre del equipo o producto es obligatorio.',
            'category.required'   => 'La categoría es obligatoria.',
            'unit_price.required' => 'El precio unitario es obligatorio.',
            'sale_date.required'  => 'La fecha de la venta es obligatoria.',
        ]);

        if (empty($validated['total_amount']) || (float) $validated['total_amount'] <= 0) {
            $validated['total_amount'] = (int) $validated['quantity'] * (float) $validated['unit_price'];
        }

        $sale->update($validated);

        return redirect()->route('sales.index')
            ->with('status', '¡Venta actualizada correctamente!');
    }

    /**
     * Remove the specified sale from storage.
     */
    public function destroy(Sale $sale): RedirectResponse
    {
        DB::transaction(function () use ($sale) {
            // Revertir abonos/pagos asociados si existían
            $payments = Payment::where('sale_id', $sale->id)->get();
            foreach ($payments as $payment) {
                if ($payment->bank_account_id) {
                    $account = BankAccount::find($payment->bank_account_id);
                    if ($account) {
                        $account->decrement('current_balance', $payment->amount);
                    }
                }
                $payment->delete();
            }

            $sale->delete();
        });

        return redirect()->route('sales.index')
            ->with('status', 'Venta eliminada y saldos actualizados correctamente.');
    }

    /**
     * Mark a pending sale as paid and register bank movement.
     */
    public function markAsPaid(Request $request, Sale $sale): RedirectResponse
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'bank_account_id' => ['required', Rule::exists('bank_accounts', 'id')->where('user_id', $userId)],
            'payment_method'  => ['required', 'string', 'in:efectivo,nequi,bancolombia,transferencia'],
            'payment_date'    => ['required', 'date'],
            'reference'       => ['nullable', 'string', 'max:255'],
            'notes'           => ['nullable', 'string'],
        ], [
            'bank_account_id.required' => 'Debes seleccionar la cuenta bancaria de destino.',
            'payment_method.required'  => 'El método de pago es obligatorio.',
            'payment_date.required'    => 'La fecha de pago es obligatoria.',
        ]);

        DB::transaction(function () use ($sale, $validated) {
            $sale->update([
                'status'          => 'pagado',
                'paid_at'         => now(),
                'bank_account_id' => $validated['bank_account_id'],
                'payment_method'  => $validated['payment_method'],
            ]);

            Payment::create([
                'client_id'       => $sale->client_id,
                'bank_account_id' => $validated['bank_account_id'],
                'sale_id'         => $sale->id,
                'amount'          => $sale->total_amount,
                'method'          => $validated['payment_method'],
                'payment_date'    => $validated['payment_date'],
                'reference'       => $validated['reference'] ?: ('Cobro venta: ' . $sale->item_name),
                'notes'           => $validated['notes'] ?: 'Cobro registrado para venta pendiente.',
            ]);

            $account = BankAccount::find($validated['bank_account_id']);
            if ($account) {
                $account->increment('current_balance', $sale->total_amount);
            }
        });

        return redirect()->route('sales.index')
            ->with('status', '¡Pago de venta registrado y saldo acreditado con éxito!');
    }
}
