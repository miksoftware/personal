@extends('layouts.app')

@section('title', 'Estado de Cuenta — ' . $client->name . ' - MIK Software Control')
@section('page_title', '')
@section('page_subtitle', '')

@section('content')

{{-- ── Client Header ────────────────────────────────────── --}}
<div class="stmt-client-header">
    <div style="display:flex; align-items:center; gap:16px; flex:1;">
        <div class="stmt-client-avatar">{{ strtoupper(substr($client->name, 0, 2)) }}</div>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:.5px; margin-bottom:3px;">
                Estado de Cuenta
            </div>
            <h2 style="font-size:22px; font-weight:800; margin:0 0 4px;">{{ $client->name }}</h2>
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <span class="badge-type {{ $client->type }}" style="font-size:10px; padding:2px 8px;">
                    {{ $client->type_label }}
                </span>
                <span style="font-size:11px; color:rgba(255,255,255,0.35);">
                    {{ $client->model_label }}
                </span>
                @if($client->phone)
                    <span style="font-size:11px; color:rgba(255,255,255,0.35);">
                        <i class="bi bi-telephone"></i> {{ $client->phone }}
                    </span>
                @endif
            </div>
        </div>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;" class="stmt-header-actions">
        <a href="{{ route('reports.index') }}" class="btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <button onclick="window.print()" class="btn-primary-action no-print">
            <i class="bi bi-printer"></i> Imprimir / PDF
        </button>
    </div>
</div>

{{-- ── Summary Cards ────────────────────────────────────── --}}
<div class="stmt-stat-cards">

    <div class="stmt-stat debt">
        <div class="stmt-stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
        <div>
            <div class="stmt-stat-label">Total Deuda Acumulada</div>
            <div class="stmt-stat-value">${{ number_format($totalDebt, 2, '.', ',') }}</div>
            <div class="stmt-stat-sub">
                Proyectos (${{ number_format($proyectosTotal, 0, '.', ',') }})
                + Mejoras (${{ number_format($mejorasTotal, 0, '.', ',') }})
            </div>
        </div>
    </div>

    <div class="stmt-stat paid">
        <div class="stmt-stat-icon"><i class="bi bi-cash-coin"></i></div>
        <div>
            <div class="stmt-stat-label">Total Abonos Realizados</div>
            <div class="stmt-stat-value">${{ number_format($totalPaid, 2, '.', ',') }}</div>
            <div class="stmt-stat-sub">
                {{ $payments->count() }} pago{{ $payments->count() !== 1 ? 's' : '' }} registrado{{ $payments->count() !== 1 ? 's' : '' }}
            </div>
        </div>
    </div>

    <div class="stmt-stat {{ $balance <= 0 ? 'balance-clear' : 'balance' }}">
        <div class="stmt-stat-icon">
            <i class="bi bi-{{ $balance <= 0 ? 'check-circle' : 'hourglass-split' }}"></i>
        </div>
        <div>
            <div class="stmt-stat-label">Saldo Pendiente a la Fecha</div>
            <div class="stmt-stat-value">${{ number_format(abs($balance), 2, '.', ',') }}</div>
            <div class="stmt-stat-sub">
                @if($balance < 0)
                    Crédito a favor del cliente
                @elseif($balance == 0)
                    Cuenta al día
                @else
                    Corte {{ now()->format('d/m/Y') }}
                @endif
            </div>
        </div>
    </div>

</div>

{{-- ── Status Breakdown ────────────────────────────────────── --}}
<div class="stmt-breakdown-row">

    <div class="stmt-breakdown-card completed">
        <div class="stmt-breakdown-icon">
            <i class="bi bi-patch-check-fill"></i>
        </div>
        <div class="stmt-breakdown-body">
            <div class="stmt-breakdown-label">Completados / Entregados</div>
            <div class="stmt-breakdown-value">${{ number_format($completedAmount, 2, '.', ',') }}</div>
            <div class="stmt-breakdown-sub">
                {{ $completedCount }} desarrollo{{ $completedCount !== 1 ? 's' : '' }} finalizado{{ $completedCount !== 1 ? 's' : '' }}
                @if($completedAmount > 0 && $totalDebt > 0)
                    · {{ round(($completedAmount / $totalDebt) * 100, 1) }}% del total
                @endif
            </div>
        </div>
    </div>

    <div class="stmt-breakdown-card inprogress">
        <div class="stmt-breakdown-icon">
            <i class="bi bi-hourglass-split"></i>
        </div>
        <div class="stmt-breakdown-body">
            <div class="stmt-breakdown-label">En Proceso / Pendiente de Pago</div>
            <div class="stmt-breakdown-value">${{ number_format($inProgressAmount, 2, '.', ',') }}</div>
            <div class="stmt-breakdown-sub">
                {{ $inProgressCount }} desarrollo{{ $inProgressCount !== 1 ? 's' : '' }} en curso
                @if($inProgressAmount > 0 && $totalDebt > 0)
                    · {{ round(($inProgressAmount / $totalDebt) * 100, 1) }}% del total
                @endif
            </div>
        </div>
    </div>

    <div class="stmt-breakdown-card proyecto">
        <div class="stmt-breakdown-icon">
            <i class="bi bi-kanban"></i>
        </div>
        <div class="stmt-breakdown-body">
            <div class="stmt-breakdown-label">Proyectos a Medida</div>
            <div class="stmt-breakdown-value">${{ number_format($proyectosTotal, 2, '.', ',') }}</div>
            <div class="stmt-breakdown-sub">
                {{ $developments->where('type','proyecto')->count() }} proyecto{{ $developments->where('type','proyecto')->count() !== 1 ? 's' : '' }}
            </div>
        </div>
    </div>

    <div class="stmt-breakdown-card mejora">
        <div class="stmt-breakdown-icon">
            <i class="bi bi-tools"></i>
        </div>
        <div class="stmt-breakdown-body">
            <div class="stmt-breakdown-label">Mejoras</div>
            <div class="stmt-breakdown-value">${{ number_format($mejorasTotal, 2, '.', ',') }}</div>
            <div class="stmt-breakdown-sub">
                {{ $developments->where('type','mejora')->count() }} mejora{{ $developments->where('type','mejora')->count() !== 1 ? 's' : '' }}
            </div>
        </div>
    </div>

</div>

{{-- ── Progress Bar ─────────────────────────────────────── --}}
@if($totalDebt > 0)
<div class="stmt-progress-card no-print">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <div>
            <span style="font-size:14px; font-weight:700; color:var(--white);">
                {{ $progressPct }}% pagado
            </span>
            <span style="font-size:12px; color:rgba(255,255,255,0.4); margin-left:10px;">
                Faltan ${{ number_format(max(0, $balance), 0, '.', ',') }} para cubrir la deuda total
            </span>
        </div>
        <div style="display:flex; gap:16px; font-size:12px;">
            @foreach(['efectivo' => ['#48c78e', 'Efectivo'], 'nequi' => ['#b39ddb', 'Nequi'], 'bancolombia' => ['#ffd54f', 'Bancolombia']] as $m => [$color, $label])
                @if(isset($byMethod[$m]) && $byMethod[$m] > 0)
                    <span>
                        <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:{{ $color }}; margin-right:4px;"></span>
                        <span style="color:rgba(255,255,255,0.5);">{{ $label }}:</span>
                        <span style="color:{{ $color }}; font-weight:600;"> ${{ number_format($byMethod[$m], 0, '.', ',') }}</span>
                    </span>
                @endif
            @endforeach
        </div>
    </div>
    <div class="stmt-progress-track">
        <div class="stmt-progress-fill" style="width:{{ $progressPct }}%;"></div>
    </div>
</div>
@endif

{{-- ── Developments Table ───────────────────────────────── --}}
<div class="stmt-section">
    <div class="stmt-section-header">
        <h3><i class="bi bi-list-task" style="color:var(--salmon);"></i> Detalle de Requerimientos (Deuda)</h3>
        <span style="font-size:12px; color:rgba(255,255,255,0.4);">{{ $developments->count() }} registros</span>
    </div>

    @if($developments->isEmpty())
        <div style="padding:40px; text-align:center; color:rgba(255,255,255,0.3);">
            <i class="bi bi-inbox" style="font-size:30px;"></i>
            <p style="margin-top:8px; font-size:13px;">Sin desarrollos registrados</p>
        </div>
    @else
        <table class="custom-table stmt-table-devs">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Concepto</th>
                    <th>Estado</th>
                    <th style="text-align:right;">Valor</th>
                    <th style="text-align:right;">Abonado</th>
                    <th style="text-align:right;">Pendiente</th>
                </tr>
            </thead>
            <tbody>
                @foreach($developments as $dev)
                    @php
                        $devPending = $dev->amount - $dev->paid_toward;
                        $pendColor  = $devPending <= 0 ? '#48c78e' : '#ff9090';
                    @endphp
                    <tr>
                        <td>
                            <span class="badge-type {{ $dev->type }}" style="font-size:10px;">
                                {{ $dev->type_label }}
                            </span>
                        </td>
                        <td>
                            <div style="font-weight:600; font-size:13px;">{{ $dev->title }}</div>
                            @if($dev->description)
                                <div style="font-size:11px; color:rgba(255,255,255,0.35); margin-top:2px;">
                                    {{ Str::limit($dev->description, 60) }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="badge-status {{ $dev->status }}">
                                {{ $dev->status_label }}
                            </span>
                        </td>
                        <td style="text-align:right; font-weight:700; color:var(--white);">
                            ${{ number_format($dev->amount, 2, '.', ',') }}
                        </td>
                        <td style="text-align:right; color:#48c78e; font-weight:600;">
                            {{ $dev->paid_toward > 0 ? '$'.number_format($dev->paid_toward, 2, '.', ',') : '—' }}
                        </td>
                        <td style="text-align:right; font-weight:700; color:{{ $pendColor }};">
                            ${{ number_format(max(0, $devPending), 2, '.', ',') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="border-top:2px solid rgba(255,255,255,0.1);">
                    <td colspan="3" style="font-weight:700; color:rgba(255,255,255,0.7); font-size:12px; text-transform:uppercase; letter-spacing:.4px;">
                        TOTALES
                    </td>
                    <td style="text-align:right; font-weight:800; color:var(--white); font-size:15px;">
                        ${{ number_format($totalDebt, 2, '.', ',') }}
                    </td>
                    <td style="text-align:right; font-weight:800; color:#48c78e; font-size:15px;">
                        ${{ number_format($totalPaid, 2, '.', ',') }}
                    </td>
                    <td style="text-align:right; font-weight:800; font-size:15px; color:{{ $balance <= 0 ? '#48c78e' : '#ffd54f' }};">
                        ${{ number_format(max(0, $balance), 2, '.', ',') }}
                    </td>
                </tr>
            </tfoot>
        </table>
    @endif
</div>

{{-- ── Payment History ──────────────────────────────────── --}}
<div class="stmt-section">
    <div class="stmt-section-header">
        <h3><i class="bi bi-cash-stack" style="color:#48c78e;"></i> Historial de Pagos y Abonos</h3>
        <span style="font-size:12px; color:rgba(255,255,255,0.4);">
            Total: <strong style="color:#48c78e;">${{ number_format($totalPaid, 2, '.', ',') }}</strong>
        </span>
    </div>

    @if($payments->isEmpty())
        <div style="padding:40px; text-align:center; color:rgba(255,255,255,0.3);">
            <i class="bi bi-receipt" style="font-size:30px;"></i>
            <p style="margin-top:8px; font-size:13px;">Sin pagos registrados</p>
        </div>
    @else
        <table class="custom-table stmt-table-pays">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Destino / Concepto</th>
                    <th>Método</th>
                    <th>Referencia</th>
                    <th>Notas</th>
                    <th style="text-align:right;">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $pay)
                    <tr>
                        <td style="color:var(--silver-light); font-size:13px; white-space:nowrap;">
                            {{ \Carbon\Carbon::parse($pay->payment_date)->format('d/m/Y') }}
                        </td>
                        <td>
                            @if($pay->development)
                                <span style="font-weight:600; font-size:13px; display:block;">
                                    {{ $pay->development->title }}
                                </span>
                                <span class="badge-type {{ $pay->development->type }}" style="font-size:9px; padding:1px 6px;">
                                    {{ $pay->development->type_label }}
                                </span>
                            @else
                                <span style="font-weight:600; color:rgba(255,255,255,0.5);">Cuenta Global</span>
                                <span style="font-size:11px; color:rgba(255,255,255,0.3); display:block;">Abono general</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge-method {{ $pay->method }}">{{ $pay->method_label }}</span>
                        </td>
                        <td style="font-size:12px; color:var(--silver-light);">
                            {{ $pay->reference ?: '—' }}
                        </td>
                        <td style="font-size:12px; color:var(--silver-light); max-width:200px;">
                            {{ $pay->notes ?: '—' }}
                        </td>
                        <td style="text-align:right;">
                            <span class="payment-amount">${{ number_format($pay->amount, 2, '.', ',') }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="border-top:2px solid rgba(255,255,255,0.1);">
                    <td colspan="5" style="font-weight:700; color:rgba(255,255,255,0.7); font-size:12px; text-transform:uppercase; letter-spacing:.4px;">
                        TOTAL RECAUDADO
                    </td>
                    <td style="text-align:right; font-weight:800; color:#48c78e; font-size:15px;">
                        ${{ number_format($totalPaid, 2, '.', ',') }}
                    </td>
                </tr>
            </tfoot>
        </table>
    @endif
</div>

{{-- ── Print-only footer ────────────────────────────────── --}}
<div class="print-only" style="margin-top:30px; text-align:center; font-size:11px; color:rgba(255,255,255,0.4); border-top:1px solid rgba(255,255,255,0.1); padding-top:12px;">
    MIK Software Control — Estado de cuenta generado el {{ now()->format('d/m/Y H:i') }}
</div>

@endsection
