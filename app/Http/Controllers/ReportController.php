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
            ->withCount(['developments', 'payments'])
            ->orderBy('name')
            ->get()
            ->map(function ($client) {
                $client->total_debt    = (float) ($client->developments_sum_amount ?? 0);
                $client->total_paid    = (float) ($client->payments_sum_amount ?? 0);
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
        $developments = $client->developments()->orderBy('type')->orderBy('title')->get();
        $payments     = $client->payments()->with('development')->orderBy('payment_date', 'desc')->get();

        $totalDebt   = (float) $developments->sum('amount');
        $totalPaid   = (float) $payments->sum('amount');
        $balance     = $totalDebt - $totalPaid;
        $progressPct = $totalDebt > 0 ? min(100, round(($totalPaid / $totalDebt) * 100, 1)) : 0;

        // Per-development paid amounts
        $paidByDev = $payments->whereNotNull('development_id')->groupBy('development_id');
        $developments->each(function ($dev) use ($paidByDev) {
            $dev->paid_toward = (float) $paidByDev->get($dev->id, collect())->sum('amount');
            $dev->dev_balance = (float) $dev->amount - $dev->paid_toward;
        });

        // Subtotals by development type
        $proyectosTotal = (float) $developments->where('type', 'proyecto')->sum('amount');
        $mejorasTotal   = (float) $developments->where('type', 'mejora')->sum('amount');

        // Payments grouped by method
        $byMethod = $payments->groupBy('method')->map(fn($grp) => $grp->sum('amount'));

        // Breakdown by status
        $completedDevs   = $developments->filter(fn($d) => in_array($d->status, ['completado', 'pagado']));
        $inProgressDevs  = $developments->filter(fn($d) => $d->status === 'pendiente');
        $completedAmount = (float) $completedDevs->sum('amount');
        $completedCount  = $completedDevs->count();
        $inProgressAmount= (float) $inProgressDevs->sum('amount');
        $inProgressCount = $inProgressDevs->count();

        return view('reports.show', compact(
            'client', 'developments', 'payments',
            'totalDebt', 'totalPaid', 'balance', 'progressPct',
            'proyectosTotal', 'mejorasTotal', 'byMethod',
            'completedAmount', 'completedCount', 'inProgressAmount', 'inProgressCount'
        ));
    }
}
