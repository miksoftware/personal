<?php

namespace App\Http\Controllers;

use App\Models\Development;
use App\Models\Client;
use App\Models\License;
use Illuminate\Http\Request;

class DevelopmentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $filter = $request->input('filter', 'all'); // 'all' | 'mejora' | 'proyecto' | 'soporte'

        $developments = Development::with(['client', 'license', 'parent'])
            ->when($search, function ($query) use ($search) {
                return $query->where('title', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->when($filter !== 'all', fn($q) => $q->where('type', $filter))
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Apply FIFO logic to each client's developments in the current page
        $clientIds = $developments->pluck('client_id')->unique();
        foreach ($clientIds as $clientId) {
            $clientDevs = Development::where('client_id', $clientId)->orderBy('created_at', 'asc')->get();
            $clientPayments = \App\Models\Payment::where('client_id', $clientId)->get();
            $globalPaid = (float) $clientPayments->whereNull('development_id')->sum('amount');

            foreach ($clientDevs as $dev) {
                $specificPaid = (float) $clientPayments->where('development_id', $dev->id)->sum('amount');
                $devBalance = $dev->amount - $specificPaid;
                
                if ($devBalance > 0 && $globalPaid > 0) {
                    $applied = min($devBalance, $globalPaid);
                    $devBalance -= $applied;
                    $globalPaid -= $applied;
                }

                if ($devBalance <= 0) {
                    // Find this dev in the paginated collection and mark it
                    $match = $developments->where('id', $dev->id)->first();
                    if ($match) {
                        $match->is_dynamically_paid = true;
                    }
                }
            }
        }

        $clients      = Client::orderBy('name')->get();
        $licenses     = License::with('client')->orderBy('url')->get();
        $allDevelopments = Development::where('type', '!=', 'soporte')
            ->with('client')->orderBy('title')->get(); // for parent selector

        return view('developments.index', compact(
            'developments', 'clients', 'licenses', 'allDevelopments', 'search', 'filter'
        ));
    }

    public function store(Request $request)
    {
        $type = $request->input('type', 'mejora');

        if ($type === 'soporte') {
            $validated = $request->validate([
                'type'             => ['required', 'in:mejora,proyecto,soporte'],
                'client_id'        => ['required', 'exists:clients,id'],
                'parent_id'        => ['nullable', 'exists:developments,id'],
                'title'            => ['required', 'string', 'max:255'],
                'description'      => ['nullable', 'string'],
                'monthly_fee'      => ['required', 'numeric', 'min:0'],
                'contract_months'  => ['required', 'integer', 'min:1', 'max:120'],
                'billing_cycle'    => ['required', 'in:mensual,bimestral,trimestral,semestral,anual'],
                'status'           => ['required', 'in:pendiente,completado'],
                'started_at'       => ['nullable', 'date'],
                'estimated_end_at' => ['nullable', 'date'],
            ], ['client_id.required' => 'El cliente es obligatorio.', 'title.required' => 'El título es obligatorio.']);

            $validated['amount'] = $validated['monthly_fee'] * $validated['contract_months'];
            Development::create($validated);

            return redirect()->route('developments.index')
                ->with('status', '¡Contrato de soporte registrado exitosamente!');
        }

        $statusRule = $type === 'proyecto' ? 'in:pendiente,completado' : 'in:pendiente,pagado';

        $validated = $request->validate([
            'type'             => ['required', 'in:mejora,proyecto,soporte'],
            'client_id'        => ['required', 'exists:clients,id'],
            'parent_id'        => ['nullable', 'exists:developments,id'],
            'license_id'       => ['nullable', 'exists:licenses,id'],
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'amount'           => ['required', 'numeric', 'min:0'],
            'status'           => ['required', 'string', $statusRule],
            'delivered_at'     => ['nullable', 'date'],
            'paid_at'          => ['nullable', 'date'],
            'started_at'       => ['nullable', 'date'],
            'estimated_end_at' => ['nullable', 'date'],
        ], [
            'client_id.required' => 'El cliente es obligatorio.',
            'title.required'     => 'El título es obligatorio.',
            'amount.required'    => 'El monto es obligatorio.',
            'status.in'          => 'El estado seleccionado no es válido.',
        ]);

        if ($type === 'mejora' && $validated['status'] === 'pendiente') {
            $validated['paid_at'] = null;
        }

        Development::create($validated);

        $label = $type === 'proyecto' ? 'Proyecto' : 'Mejora';

        return redirect()->route('developments.index')
            ->with('status', "¡{$label} registrado exitosamente!");
    }

    public function update(Request $request, Development $development)
    {
        $type = $request->input('type', $development->type);

        if ($type === 'soporte') {
            $validated = $request->validate([
                'type'             => ['required', 'in:mejora,proyecto,soporte'],
                'client_id'        => ['required', 'exists:clients,id'],
                'parent_id'        => ['nullable', 'exists:developments,id'],
                'title'            => ['required', 'string', 'max:255'],
                'description'      => ['nullable', 'string'],
                'monthly_fee'      => ['required', 'numeric', 'min:0'],
                'contract_months'  => ['required', 'integer', 'min:1', 'max:120'],
                'billing_cycle'    => ['required', 'in:mensual,bimestral,trimestral,semestral,anual'],
                'status'           => ['required', 'in:pendiente,completado'],
                'started_at'       => ['nullable', 'date'],
                'estimated_end_at' => ['nullable', 'date'],
            ], ['client_id.required' => 'El cliente es obligatorio.', 'title.required' => 'El título es obligatorio.']);

            $validated['amount'] = $validated['monthly_fee'] * $validated['contract_months'];
            $development->update($validated);

            return redirect()->route('developments.index')
                ->with('status', '¡Contrato de soporte actualizado correctamente!');
        }

        $statusRule = $type === 'proyecto' ? 'in:pendiente,completado' : 'in:pendiente,pagado';

        $validated = $request->validate([
            'type'             => ['required', 'in:mejora,proyecto,soporte'],
            'client_id'        => ['required', 'exists:clients,id'],
            'parent_id'        => ['nullable', 'exists:developments,id'],
            'license_id'       => ['nullable', 'exists:licenses,id'],
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'amount'           => ['required', 'numeric', 'min:0'],
            'status'           => ['required', 'string', $statusRule],
            'delivered_at'     => ['nullable', 'date'],
            'paid_at'          => ['nullable', 'date'],
            'started_at'       => ['nullable', 'date'],
            'estimated_end_at' => ['nullable', 'date'],
        ], [
            'client_id.required' => 'El cliente es obligatorio.',
            'title.required'     => 'El título es obligatorio.',
            'amount.required'    => 'El monto es obligatorio.',
            'status.in'          => 'El estado seleccionado no es válido.',
        ]);

        if ($type === 'mejora' && $validated['status'] === 'pendiente') {
            $validated['paid_at'] = null;
        }

        $development->update($validated);

        $label = $type === 'proyecto' ? 'Proyecto' : 'Mejora';

        return redirect()->route('developments.index')
            ->with('status', "¡{$label} actualizado correctamente!");
    }

    public function destroy(Development $development)
    {
        $development->delete();

        return redirect()->route('developments.index')
            ->with('status', '¡Registro eliminado con éxito!');
    }
}
