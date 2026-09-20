<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Development;
use App\Models\Expense;
use App\Models\Income;
use App\Models\License;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dynamic SaaS financial dashboard for the authenticated user.
     */
    public function index(): View
    {
        $totalClients   = Client::count();
        $activeLicenses = License::where('status', 'activa')->count();

        // Ingresos del mes en curso (abonos de clientes + ingresos extras)
        $monthPayments = (float) Payment::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        $monthIncomes = (float) Income::whereMonth('income_date', now()->month)
            ->whereYear('income_date', now()->year)
            ->sum('amount');

        $monthlyIncome = $monthPayments + $monthIncomes;

        // Gastos del mes en curso
        $monthlyExpenses = (float) Expense::whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        // Saldo pendiente global por cobrar (desarrollos + préstamos otorgados + ventas pendientes - abonos de clientes)
        $totalDevs          = (float) Development::sum('amount');
        $totalLoansGiven    = (float) Loan::where('type', 'entregado')->where('status', 'pendiente')->sum('amount');
        $totalSalesPending  = (float) Sale::where('status', 'pendiente')->sum('total_amount');
        $totalPayments      = (float) Payment::sum('amount');
        $pendingBalance     = max(0, ($totalDevs + $totalLoansGiven + $totalSalesPending) - $totalPayments);

        // Clientes recientes
        $recentClients = Client::latest()->take(5)->get();

        // Últimos pagos registrados
        $recentPayments = Payment::with(['client', 'development', 'license', 'sale'])
            ->latest('payment_date')
            ->latest('created_at')
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'totalClients',
            'activeLicenses',
            'monthlyIncome',
            'monthlyExpenses',
            'pendingBalance',
            'recentClients',
            'recentPayments'
        ));
    }
}
