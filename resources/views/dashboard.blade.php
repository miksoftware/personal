@extends('layouts.app')

@section('title', 'Dashboard - MIK Software Control')

@section('page_title', 'Dashboard')
@section('page_subtitle', 'Bienvenido al sistema de gestión de MIKSOFTWARE.')

@section('content')

@if(session('status'))
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; padding: 14px 18px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; color: #10b981;">
        <i class="bi bi-check-circle-fill" style="font-size: 20px;"></i>
        <span style="font-size: 14px; font-weight: 500;">{{ session('status') }}</span>
    </div>
@endif

<!-- Metric Cards Grid -->
<div class="metrics-grid">
    <!-- Card 1: Clientes Totales -->
    <div class="metric-card">
        <div class="metric-card-header">
            <span class="metric-title">Clientes Totales</span>
            <div class="metric-icon-box purple">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
        <div class="metric-value">{{ number_format($totalClients) }}</div>
    </div>

    <!-- Card 2: Licencias Activas -->
    <div class="metric-card">
        <div class="metric-card-header">
            <span class="metric-title">Licencias Activas</span>
            <div class="metric-icon-box salmon">
                <i class="bi bi-key-fill"></i>
            </div>
        </div>
        <div class="metric-value">{{ number_format($activeLicenses) }}</div>
    </div>

    <!-- Card 3: Ingresos del Mes -->
    <div class="metric-card">
        <div class="metric-card-header">
            <span class="metric-title">Ingresos del Mes</span>
            <div class="metric-icon-box green">
                <i class="bi bi-wallet2"></i>
            </div>
        </div>
        <div class="metric-value">${{ number_format($monthlyIncome, 2) }}</div>
    </div>

    <!-- Card 4: Saldo Pendiente -->
    <div class="metric-card">
        <div class="metric-card-header">
            <span class="metric-title">Saldo Pendiente</span>
            <div class="metric-icon-box red">
                <i class="bi bi-clock-fill"></i>
            </div>
        </div>
        <div class="metric-value highlighted">${{ number_format($pendingBalance, 2) }}</div>
    </div>
</div>

<!-- Dashboard Lists Columns Layout -->
<div class="dashboard-columns">
    <!-- Left Column: Clientes Recientes -->
    <div class="column-card">
        <div class="column-header">
            <h2 class="column-title">Clientes Recientes</h2>
            <a href="{{ route('clients.index') }}" class="column-link">Ver Todos</a>
        </div>
        
        <div class="list-items">
            @forelse($recentClients as $client)
                <a href="{{ route('reports.show', $client) }}" class="client-row" style="text-decoration: none; color: inherit;">
                    <div class="client-info">
                        <div class="client-avatar">{{ strtoupper(substr($client->name, 0, 1)) }}</div>
                        <div>
                            <div class="client-name">{{ $client->name }}</div>
                            <div class="client-type">{{ $client->model_label }}</div>
                        </div>
                    </div>
                    <div class="client-action-icon">
                        <i class="bi bi-chevron-right"></i>
                    </div>
                </a>
            @empty
                <div style="padding: 28px 16px; text-align: center; color: #8E9BAE;">
                    <i class="bi bi-people" style="font-size: 32px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                    <p style="margin: 0; font-size: 14px;">Aún no tienes clientes registrados.</p>
                    <a href="{{ route('clients.index') }}" style="display: inline-block; margin-top: 10px; font-size: 13px; color: #9333ea; text-decoration: none; font-weight: 500;">
                        + Crear tu primer cliente
                    </a>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Right Column: Últimos Pagos -->
    <div class="column-card">
        <div class="column-header">
            <h2 class="column-title">Últimos Pagos</h2>
            <a href="{{ route('payments.index') }}" class="column-link">Ver Todos</a>
        </div>

        <div class="list-items">
            @forelse($recentPayments as $payment)
                <div class="payment-row">
                    <div class="payment-info">
                        <div class="payment-icon">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="payment-details">
                            <span class="payment-amount">${{ number_format($payment->amount, 2) }}</span>
                            <span class="payment-meta">{{ $payment->client?->name ?: 'Cliente' }} • {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M') }}</span>
                        </div>
                    </div>
                    <span class="status-badge completed">{{ $payment->method_label }}</span>
                </div>
            @empty
                <div style="padding: 28px 16px; text-align: center; color: #8E9BAE;">
                    <i class="bi bi-receipt" style="font-size: 32px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                    <p style="margin: 0; font-size: 14px;">No hay pagos registrados aún.</p>
                    <a href="{{ route('payments.index') }}" style="display: inline-block; margin-top: 10px; font-size: 13px; color: #10b981; text-decoration: none; font-weight: 500;">
                        + Registrar un pago
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</div>

@endsection
