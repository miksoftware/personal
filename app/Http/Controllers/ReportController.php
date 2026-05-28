<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Index: all clients with financial summary.
     */
    public function index(): View
    {
        $clients = Client::withSum('developments', 'amount')
            ->withSum('payments', 'amount')
            ->withSum(['loans as loans_given_sum' => fn($q) => $q->where('type', 'entregado')], 'amount')
            ->withSum(['loans as loans_received_sum' => fn($q) => $q->where('type', 'recibido')], 'amount')
            ->withCount(['developments', 'payments'])
            ->orderBy('name')
            ->get()
            ->map(function ($client) {
                $client->total_debt    = (float) ($client->developments_sum_amount ?? 0) + (float) ($client->loans_given_sum ?? 0);
                $client->total_paid    = (float) ($client->payments_sum_amount ?? 0) + (float) ($client->loans_received_sum ?? 0);
                $client->balance       = $client->total_debt - $client->total_paid;
                $client->progress_pct  = $client->total_debt > 0
                    ? min(100, round(($client->total_paid / $client->total_debt) * 100, 1))
                    : 0;
                return $client;
            });

        $globalDebt    = $clients->sum('total_debt');
        $globalPaid    = $clients->sum('total_paid');
        $globalBalance = $globalDebt - $globalPaid;

        return view('reports.index', compact(
            'clients', 'globalDebt', 'globalPaid', 'globalBalance'
        ));
    }

    /**
     * Detail: full account statement for one client.
     */
    public function show(Client $client): View
    {
        $developments = $client->developments()->orderBy('created_at', 'asc')->get();
        $payments     = $client->payments()->with('development')->orderBy('payment_date', 'asc')->get();
        $loans        = \App\Models\Loan::where('client_id', $client->id)->orderBy('loan_date', 'asc')->get();
        $credits      = \App\Models\Credit::where('client_id', $client->id)->withSum('payments', 'amount')->orderBy('credit_date', 'asc')->get();

        $totalDebt   = (float) $developments->sum('amount') + (float) $loans->where('type', 'entregado')->sum('amount');
        $totalPaid   = (float) $payments->sum('amount') + (float) $loans->where('type', 'recibido')->sum('amount');
        $balance     = $totalDebt - $totalPaid;
        $progressPct = $totalDebt > 0 ? min(100, round(($totalPaid / $totalDebt) * 100, 1)) : 0;

        // FIFO Distribution Logic
        $remainingGlobalPaid = (float) $payments->whereNull('development_id')->sum('amount') + (float) $loans->where('type', 'recibido')->sum('amount');
        
        // Map specific payments first
        $paidByDev = $payments->whereNotNull('development_id')->groupBy('development_id');
        
        $developments->each(function ($dev) use ($paidByDev, &$remainingGlobalPaid) {
            // Specific payments for this dev
            $specificPaid = (float) $paidByDev->get($dev->id, collect())->sum('amount');
            $dev->paid_toward = $specificPaid;
            
            // Distribute global payments to cover remaining balance of this dev
            $stillPending = $dev->amount - $dev->paid_toward;
            if ($stillPending > 0 && $remainingGlobalPaid > 0) {
                $applied = min($stillPending, $remainingGlobalPaid);
                $dev->paid_toward += $applied;
                $remainingGlobalPaid -= $applied;
            }
            
            $dev->dev_balance = (float) $dev->amount - $dev->paid_toward;
            
            // Update status dynamically for the view if it's fully paid
            if ($dev->dev_balance <= 0 && $dev->type === 'mejora') {
                $dev->status = 'pagado';
            }
        });

        // Subtotals by development type
        $proyectosTotal = (float) $developments->where('type', 'proyecto')->sum('amount');
        $mejorasTotal   = (float) $developments->where('type', 'mejora')->sum('amount');

        // Payments grouped by method
        $byMethod = $payments->groupBy('method')->map(fn($grp) => $grp->sum('amount'));

        // Breakdown by status (using dynamic status from FIFO)
        $completedDevs   = $developments->filter(fn($d) => in_array($d->status, ['completado', 'pagado']));
        $inProgressDevs  = $developments->filter(fn($d) => $d->status === 'pendiente' && $d->dev_balance > 0);
        $completedAmount = (float) $completedDevs->sum('amount');
        $completedCount  = $completedDevs->count();
        $inProgressAmount= (float) $inProgressDevs->sum('amount');
        $inProgressCount = $inProgressDevs->count();

        // Sort back for the view to match user's expected visual
        $developments = $developments->sortByDesc('created_at');
        $payments     = $payments->sortByDesc('payment_date');
        $loans        = $loans->sortByDesc('loan_date');
        $credits      = $credits->sortByDesc('credit_date');

        return view('reports.show', compact(
            'client', 'developments', 'payments', 'loans', 'credits',
            'totalDebt', 'totalPaid', 'balance', 'progressPct',
            'proyectosTotal', 'mejorasTotal', 'byMethod',
            'completedAmount', 'completedCount', 'inProgressAmount', 'inProgressCount'
        ));
    }
}
