@extends('layouts.app')

@section('title', 'Movimientos de Cuenta - MIK Software Control')
@section('page_title', 'Movimientos de Cuenta')
@section('page_subtitle', $bankAccount->name . ' - Auditoria de saldo y movimientos')

@section('content')

@if(session('status'))
    <div class="alert-banner-success" style="margin-bottom:25px;">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('status') }}</span>
    </div>
@endif

@if($errors->any())
    <div class="alert-banner" style="margin-bottom:25px;">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<div style="margin-bottom:20px;">
    <a href="{{ route('bank-accounts.index') }}" class="btn-secondary" style="display:inline-flex; align-items:center; gap:6px; text-decoration:none; padding:8px 16px;">
        <i class="bi bi-arrow-left"></i> Volver a Cuentas
    </a>
</div>

<div class="client-table-card" style="margin-bottom:20px;">
    <div style="display:flex; justify-content:space-between; gap:16px; align-items:center; flex-wrap:wrap;">
        <div>
            <h3 style="margin:0 0 6px; color:var(--white); font-size:20px;">{{ $bankAccount->name }}</h3>
            <div style="color:var(--silver-light); font-size:13px;">
                Numero de cuenta: {{ $bankAccount->account_number ?: 'No registrado' }}
            </div>
        </div>
        <div>
            <span class="badge-status {{ $bankAccount->is_active ? 'activa' : 'suspendida' }}">
                {{ $bankAccount->is_active ? 'Activa' : 'Inactiva' }}
            </span>
        </div>
    </div>
</div>

<div style="display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
    <div style="flex:1; min-width:180px; padding:14px 18px; background:rgba(72,199,142,0.07); border:1px solid rgba(72,199,142,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-arrow-down-circle" style="font-size:22px; color:#48c78e;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px;">Entradas Explicadas</div>
            <div style="font-size:18px; font-weight:700; color:#48c78e;">${{ number_format($totalIncomes, 2) }}</div>
        </div>
    </div>
    <div style="flex:1; min-width:180px; padding:14px 18px; background:rgba(239,83,80,0.07); border:1px solid rgba(239,83,80,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-arrow-up-circle" style="font-size:22px; color:#ef5350;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px;">Salidas Explicadas</div>
            <div style="font-size:18px; font-weight:700; color:#ef5350;">${{ number_format($totalExpenses, 2) }}</div>
        </div>
    </div>
    <div style="flex:1; min-width:180px; padding:14px 18px; background:rgba(66,165,245,0.07); border:1px solid rgba(66,165,245,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-calculator" style="font-size:22px; color:#42a5f5;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px;">Saldo Explicado</div>
            <div style="font-size:18px; font-weight:700; color:#42a5f5;">${{ number_format($explainedBalance, 2) }}</div>
        </div>
    </div>
    <div style="flex:1; min-width:180px; padding:14px 18px; background:rgba(255,193,7,0.07); border:1px solid rgba(255,193,7,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-sliders" style="font-size:22px; color:#ffd54f;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px;">Diferencia No Trazada</div>
            <div style="font-size:18px; font-weight:700; color:#ffd54f;">${{ number_format($manualAdjustment, 2) }}</div>
        </div>
    </div>
    <div style="flex:1; min-width:180px; padding:14px 18px; background:rgba(72,199,142,0.07); border:1px solid rgba(72,199,142,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-wallet2" style="font-size:22px; color:#48c78e;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px;">Saldo Actual</div>
            <div style="font-size:18px; font-weight:700; color:#48c78e;">${{ number_format($bankAccount->current_balance, 2) }}</div>
        </div>
    </div>
</div>

<div class="client-table-card" style="margin-bottom:20px;">
    <div style="padding:18px 20px; border-bottom:1px solid rgba(255,255,255,0.06);">
        <h3 style="margin:0 0 8px; font-size:16px; color:var(--white);">
            <i class="bi bi-info-circle" style="color:#42a5f5; margin-right:6px;"></i>Como leer este reporte
        </h3>
        <p style="margin:0; font-size:13px; color:var(--silver-light); line-height:1.6;">
            Este reporte suma todas las entradas registradas en <strong>Pagos y Abonos</strong> e <strong>Ingresos</strong>,
            y resta todas las salidas registradas en <strong>Gastos / Compras</strong>. Los <strong>Ajustes</strong> ya quedan registrados como movimiento.
            Si aun ves una diferencia, aparece como <strong>Diferencia No Trazada</strong>, lo que normalmente indica movimientos antiguos o ediciones directas previas.
        </p>
    </div>
</div>

<div class="client-table-card">
    <div class="filter-bar">
        <h3 style="margin:0; font-size:16px; color:var(--white);">
            <i class="bi bi-clock-history" style="margin-right:6px; color:var(--salmon);"></i>Historial de Movimientos
        </h3>
        <span style="font-size:12px; color:rgba(255,255,255,0.4);">{{ $movements->count() }} movimientos explicados</span>
    </div>

    <div class="table-responsive">
        @if($movements->isNotEmpty())
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Detalle</th>
                        <th>Referencia</th>
                        <th>Notas</th>
                        <th style="text-align:right;">Entrada</th>
                        <th style="text-align:right;">Salida</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movements as $movement)
                        <tr>
                            <td style="color:var(--silver-light); font-size:13px; white-space:nowrap;">
                                {{ \Carbon\Carbon::parse($movement['date'])->format('d/m/Y') }}
                            </td>
                            <td>
                                <span style="font-size:12px; color:{{ $movement['direction'] === 'in' ? '#48c78e' : '#ef5350' }}; font-weight:700;">
                                    {{ $movement['label'] }}
                                </span>
                            </td>
                            <td style="font-size:13px; color:var(--white);">
                                {{ $movement['detail'] ?: '—' }}
                            </td>
                            <td style="font-size:12px; color:var(--silver-light);">
                                {{ $movement['reference'] ?: '—' }}
                            </td>
                            <td style="font-size:12px; color:rgba(255,255,255,0.45); max-width:260px;">
                                {{ $movement['notes'] ?: '—' }}
                            </td>
                            <td style="text-align:right; font-weight:700; color:#48c78e;">
                                @if($movement['direction'] === 'in')
                                    ${{ number_format($movement['amount'], 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td style="text-align:right; font-weight:700; color:#ef5350;">
                                @if($movement['direction'] === 'out')
                                    ${{ number_format($movement['amount'], 2) }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="border-top:2px solid rgba(255,255,255,0.08);">
                        <td colspan="5" style="font-weight:800; color:var(--white); text-transform:uppercase; letter-spacing:1px;">
                            Totales Explicados
                        </td>
                        <td style="text-align:right; font-weight:800; color:#48c78e;">
                            ${{ number_format($totalIncomes, 2) }}
                        </td>
                        <td style="text-align:right; font-weight:800; color:#ef5350;">
                            ${{ number_format($totalExpenses, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        @else
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-bank"></i></div>
                <h3 class="empty-state-title">No hay movimientos explicados</h3>
                <p class="empty-state-desc">Esta cuenta aun no tiene pagos, ingresos ni gastos vinculados.</p>
            </div>
        @endif
    </div>
</div>

@endsection
