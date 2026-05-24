@extends('layouts.app')

@section('title', 'Estado de Cuentas - MIK Software Control')
@section('page_title', 'Estado de Cuentas')
@section('page_subtitle', 'Resumen financiero por cliente — deuda, abonos y saldo pendiente.')

@section('content')

{{-- ── Global Summary ──────────────────────────────────── --}}
<div class="rpt-global-stats">
    <div class="rpt-global-stat">
        <i class="bi bi-graph-up-arrow" style="color:#ff7070;"></i>
        <div>
            <div class="rpt-gs-label">Deuda Total Global</div>
            <div class="rpt-gs-value" style="color:#ff7070;">${{ number_format($globalDebt, 0, '.', ',') }}</div>
        </div>
    </div>
    <div class="rpt-global-divider"></div>
    <div class="rpt-global-stat">
        <i class="bi bi-cash-coin" style="color:#48c78e;"></i>
        <div>
            <div class="rpt-gs-label">Total Recaudado</div>
            <div class="rpt-gs-value" style="color:#48c78e;">${{ number_format($globalPaid, 0, '.', ',') }}</div>
        </div>
    </div>
    <div class="rpt-global-divider"></div>
    <div class="rpt-global-stat">
        <i class="bi bi-hourglass-split" style="color:#ffd54f;"></i>
        <div>
            <div class="rpt-gs-label">Saldo Pendiente Global</div>
            <div class="rpt-gs-value" style="color:{{ $globalBalance > 0 ? '#ffd54f' : '#48c78e' }};">
                ${{ number_format($globalBalance, 0, '.', ',') }}
            </div>
        </div>
    </div>
</div>

{{-- ── Client Cards Grid ────────────────────────────────── --}}
@if($clients->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon"><i class="bi bi-people"></i></div>
        <h3 class="empty-state-title">Sin clientes</h3>
        <p class="empty-state-desc">No hay clientes registrados aún.</p>
    </div>
@else
    <div class="rpt-client-grid">
        @foreach($clients as $client)
            @php
                $pct = $client->progress_pct;
                $balColor = $client->balance <= 0 ? '#48c78e' : ($pct >= 70 ? '#ffd54f' : '#ff7070');
                $barColor = $pct >= 100 ? '#48c78e' : ($pct >= 60 ? '#42a5f5' : ($pct >= 30 ? '#ffd54f' : '#ff7070'));
            @endphp
            <div class="rpt-client-card">
                {{-- Header --}}
                <div class="rpt-card-header">
                    <div class="rpt-client-avatar">
                        {{ strtoupper(substr($client->name, 0, 2)) }}
                    </div>
                    <div class="rpt-client-info">
                        <div class="rpt-client-name">{{ $client->name }}</div>
                        <div style="display:flex; gap:6px; margin-top:3px;">
                            <span class="badge-type {{ $client->type }}" style="font-size:9px; padding:2px 7px;">
                                {{ $client->type_label }}
                            </span>
                            <span style="font-size:10px; color:rgba(255,255,255,0.35);">
                                {{ $client->developments_count }} desarrollos
                            </span>
                        </div>
                    </div>
                    @if($client->balance <= 0)
                        <span class="rpt-paid-badge"><i class="bi bi-check2-circle"></i> Al día</span>
                    @endif
                </div>

                {{-- Mini Stats --}}
                <div class="rpt-mini-stats">
                    <div class="rpt-mini-stat">
                        <div class="rpt-ms-label">Deuda Total</div>
                        <div class="rpt-ms-value" style="color:#ff9090;">
                            ${{ number_format($client->total_debt, 0, '.', ',') }}
                        </div>
                    </div>
                    <div class="rpt-mini-stat">
                        <div class="rpt-ms-label">Total Abonado</div>
                        <div class="rpt-ms-value" style="color:#48c78e;">
                            ${{ number_format($client->total_paid, 0, '.', ',') }}
                        </div>
                    </div>
                    <div class="rpt-mini-stat">
                        <div class="rpt-ms-label">Saldo</div>
                        <div class="rpt-ms-value" style="color:{{ $balColor }}; font-size:15px;">
                            ${{ number_format(abs($client->balance), 0, '.', ',') }}
                        </div>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div style="margin-bottom:14px;">
                    <div style="display:flex; justify-content:space-between; font-size:11px; color:rgba(255,255,255,0.4); margin-bottom:5px;">
                        <span>{{ $pct }}% pagado</span>
                        <span>{{ $client->payments_count }} pago{{ $client->payments_count !== 1 ? 's' : '' }}</span>
                    </div>
                    <div class="rpt-progress-track">
                        <div class="rpt-progress-fill" style="width:{{ $pct }}%; background:{{ $barColor }};"></div>
                    </div>
                </div>

                {{-- Action --}}
                <a href="{{ route('reports.show', $client) }}" class="rpt-ver-btn">
                    <i class="bi bi-file-earmark-text"></i>
                    Ver Estado de Cuenta
                </a>
            </div>
        @endforeach
    </div>
@endif

@endsection
