@extends('layouts.app')

@section('title', 'Dashboard - MIK Software Control')

@section('page_title', 'Dashboard')
@section('page_subtitle', 'Bienvenido al sistema de gestión de MIKSOFTWARE.')

@section('content')

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
        <div class="metric-value">2</div>
    </div>

    <!-- Card 2: Licencias Activas -->
    <div class="metric-card">
        <div class="metric-card-header">
            <span class="metric-title">Licencias Activas</span>
            <div class="metric-icon-box salmon">
                <i class="bi bi-key-fill"></i>
            </div>
        </div>
        <div class="metric-value">11</div>
    </div>

    <!-- Card 3: Ingresos del Mes -->
    <div class="metric-card">
        <div class="metric-card-header">
            <span class="metric-title">Ingresos del Mes</span>
            <div class="metric-icon-box green">
                <i class="bi bi-wallet2"></i>
            </div>
        </div>
        <div class="metric-value">$0.00</div>
    </div>

    <!-- Card 4: Saldo Pendiente -->
    <div class="metric-card">
        <div class="metric-card-header">
            <span class="metric-title">Saldo Pendiente</span>
            <div class="metric-icon-box red">
                <i class="bi bi-clock-fill"></i>
            </div>
        </div>
        <div class="metric-value highlighted">$7,490,000.00</div>
    </div>
</div>

<!-- Dashboard Lists Columns Layout -->
<div class="dashboard-columns">
    <!-- Left Column: Clientes Recientes -->
    <div class="column-card">
        <div class="column-header">
            <h2 class="column-title">Clientes Recientes</h2>
            <a href="#" class="column-link" onclick="alert('Ver todos los clientes.')">Ver Todos</a>
        </div>
        
        <div class="list-items">
            <!-- Client 1 -->
            <div class="client-row" onclick="alert('Detalle de Juan David Rodriguez')">
                <div class="client-info">
                    <div class="client-avatar">J</div>
                    <div>
                        <div class="client-name">Juan David Rodriguez</div>
                        <div class="client-type">Revendedor</div>
                    </div>
                </div>
                <div class="client-action-icon">
                    <i class="bi bi-chevron-right"></i>
                </div>
            </div>

            <!-- Client 2 -->
            <div class="client-row" onclick="alert('Detalle de Anesco')">
                <div class="client-info">
                    <div class="client-avatar">A</div>
                    <div>
                        <div class="client-name">Anesco</div>
                        <div class="client-type">Cliente Final</div>
                    </div>
                </div>
                <div class="client-action-icon">
                    <i class="bi bi-chevron-right"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Últimos Pagos -->
    <div class="column-card">
        <div class="column-header">
            <h2 class="column-title">Últimos Pagos</h2>
            <a href="#" class="column-link" onclick="alert('Ver todos los pagos.')">Ver Todos</a>
        </div>

        <div class="list-items">
            <!-- Payment 1 -->
            <div class="payment-row">
                <div class="payment-info">
                    <div class="payment-icon">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="payment-details">
                        <span class="payment-amount">$250,000.00</span>
                        <span class="payment-meta">Juan David Rodriguez • 25 Apr</span>
                    </div>
                </div>
                <span class="status-badge completed">Completado</span>
            </div>

            <!-- Payment 2 -->
            <div class="payment-row">
                <div class="payment-info">
                    <div class="payment-icon">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="payment-details">
                        <span class="payment-amount">$150,000.00</span>
                        <span class="payment-meta">Juan David Rodriguez • 21 Apr</span>
                    </div>
                </div>
                <span class="status-badge completed">Completado</span>
            </div>

            <!-- Payment 3 -->
            <div class="payment-row">
                <div class="payment-info">
                    <div class="payment-icon">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="payment-details">
                        <span class="payment-amount">$300,000.00</span>
                        <span class="payment-meta">Juan David Rodriguez • 17 Apr</span>
                    </div>
                </div>
                <span class="status-badge completed">Completado</span>
            </div>

            <!-- Payment 4 -->
            <div class="payment-row">
                <div class="payment-info">
                    <div class="payment-icon">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="payment-details">
                        <span class="payment-amount">$100,000.00</span>
                        <span class="payment-meta">Juan David Rodriguez • 15 Apr</span>
                    </div>
                </div>
                <span class="status-badge completed">Completado</span>
            </div>

            <!-- Payment 5 -->
            <div class="payment-row">
                <div class="payment-info">
                    <div class="payment-icon">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="payment-details">
                        <span class="payment-amount">$1,500,000.00</span>
                        <span class="payment-meta">Agesco • 07 Apr</span>
                    </div>
                </div>
                <span class="status-badge completed">Completado</span>
            </div>
        </div>
    </div>
</div>

@endsection
