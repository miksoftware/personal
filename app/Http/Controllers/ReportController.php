<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

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
        $data = $this->getReportData($client);
        return view('reports.show', $data);
    }

    /**
     * Download PDF version of the account statement.
     */
    public function downloadPDF(Client $client)
    {
        $data = $this->getReportData($client);
        $pdf = Pdf::loadView('reports.pdf', $data);
        
        $filename = 'Estado_Cuenta_' . str_replace(' ', '_', $client->name) . '_' . date('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Shared logic to gather and calculate report data.
     */
    private function getReportData(Client $client): array
    {
        $developments = $client->developments()->orderBy('created_at', 'asc')->get();
        $payments     = $client->payments()->with('development')->orderBy('payment_date', 'asc')->get();
        $loans        = \App\Models\Loan::where('client_id', $client->id)->orderBy('loan_date', 'asc')->get();
        $credits      = \App\Models\Credit::where('client_id', $client->id)
            ->with(['payments' => fn($q) => $q->orderBy('payment_date', 'desc')])
            ->withSum('payments', 'amount')
            ->orderBy('credit_date', 'asc')
            ->get();

        $totalDebt   = (float) $developments->sum('amount') + (float) $loans->where('type', 'entregado')->sum('amount');
        $totalPaid   = (float) $payments->sum('amount') + (float) $loans->where('type', 'recibido')->sum('amount');
        $balance     = $totalDebt - $totalPaid;
        $progressPct = $totalDebt > 0 ? min(100, round(($totalPaid / $totalDebt) * 100, 1)) : 0;

        // FIFO Distribution Logic
        $remainingGlobalPaid = (float) $payments->whereNull('development_id')->sum('amount') + (float) $loans->where('type', 'recibido')->sum('amount');
        
        // Map specific payments first
        $paidByDev = $payments->whereNotNull('development_id')->groupBy('development_id');
        
        $developments->each(function ($dev) use ($paidByDev, &$remainingGlobalPaid) {
            $specificPaid = (float) $paidByDev->get($dev->id, collect())->sum('amount');
            $dev->paid_toward = $specificPaid;
            
            $stillPending = $dev->amount - $dev->paid_toward;
            if ($stillPending > 0 && $remainingGlobalPaid > 0) {
                $applied = min($stillPending, $remainingGlobalPaid);
                $dev->paid_toward += $applied;
                $remainingGlobalPaid -= $applied;
            }
            
            $dev->dev_balance = (float) $dev->amount - $dev->paid_toward;
            
            if ($dev->dev_balance <= 0 && $dev->type === 'mejora') {
                $dev->status = 'pagado';
            }
        });

        // Subtotals
        $proyectosTotal = (float) $developments->where('type', 'proyecto')->sum('amount');
        $mejorasTotal   = (float) $developments->where('type', 'mejora')->sum('amount');
        $byMethod = $payments->groupBy('method')->map(fn($grp) => $grp->sum('amount'));

        // Breakdown by status
        $completedDevs   = $developments->filter(fn($d) => in_array($d->status, ['completado', 'pagado']));
        $inProgressDevs  = $developments->filter(fn($d) => $d->status === 'pendiente' && $d->dev_balance > 0);
        $completedAmount = (float) $completedDevs->sum('amount');
        $completedCount  = $completedDevs->count();
        $inProgressAmount= (float) $inProgressDevs->sum('amount');
        $inProgressCount = $inProgressDevs->count();

        // Specific totals for the developments table
        $devsTotalValue   = (float) $developments->sum('amount');
        $devsTotalPaid    = (float) $developments->sum('paid_toward');
        $devsTotalPending = (float) $developments->sum('dev_balance');

        return [
            'client' => $client,
            'developments' => $developments->sortByDesc('created_at'),
            'payments' => $payments->sortByDesc('payment_date'),
            'loans' => $loans->sortByDesc('loan_date'),
            'credits' => $credits->sortByDesc('credit_date'),
            'totalDebt' => $totalDebt,
            'totalPaid' => $totalPaid,
            'balance' => $balance,
            'progressPct' => $progressPct,
            'proyectosTotal' => $proyectosTotal,
            'mejorasTotal' => $mejorasTotal,
            'byMethod' => $byMethod,
            'completedAmount' => $completedAmount,
            'completedCount' => $completedCount,
            'inProgressAmount' => $inProgressAmount,
            'inProgressCount' => $inProgressCount,
            'devsTotalValue' => $devsTotalValue,
            'devsTotalPaid' => $devsTotalPaid,
            'devsTotalPending' => $devsTotalPending
        ];
    }
}
