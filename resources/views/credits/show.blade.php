@extends('layouts.app')

@section('title', 'Crédito: ' . $credit->description . ' - MIK Software Control')
@section('page_title', 'Detalle del Crédito')
@section('page_subtitle', $credit->description . ' — ' . $credit->creditor_name)

@section('content')

{{-- ── Status / Error Alerts ────────────────────────────── --}}
@if(session('status'))
    <div class="alert-banner-success" id="cs-status-alert" style="margin-bottom:25px;">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('status') }}</span>
    </div>
    <script>
        setTimeout(() => {
            const el = document.getElementById('cs-status-alert');
            if (el) el.style.display = 'none';
        }, 7000);
    </script>
@endif

@if($errors->any())
    <div class="alert-banner" id="cs-error-alert" style="margin-bottom:25px;">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

{{-- ── Back Button ──────────────────────────────────────── --}}
<div style="margin-bottom:20px;">
    <a href="{{ route('credits.index') }}" class="btn-secondary" style="display:inline-flex; align-items:center; gap:6px; text-decoration:none; padding:8px 16px;">
        <i class="bi bi-arrow-left"></i> Volver a Créditos
    </a>
</div>

{{-- ── Credit Header Card ───────────────────────────────── --}}
@php
    $paid    = $credit->total_paid;
    $balance = $credit->balance;
    $pct     = $credit->progress_percentage;
@endphp
<div class="credit-detail-header">
    <div class="credit-detail-info">
        <div class="credit-detail-top">
            <div>
                <h2 style="margin:0 0 4px; font-size:20px; font-weight:700; color:var(--white);">{{ $credit->description }}</h2>
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span style="color:{{ $credit->type === 'personal' ? '#42a5f5' : '#ff9800' }}; font-weight:600;">
                        <i class="bi bi-tag-fill" style="margin-right:2px;"></i>{{ $credit->type_label }}
                    </span>
                    <span style="color:rgba(255,255,255,0.3);">•</span>
                    <span style="color:#ff9800; font-weight:600;">
                        <i class="bi bi-person-fill" style="margin-right:2px;"></i>{{ $credit->creditor_name }}
                    </span>
                    @if($credit->client)
                        <span style="color:rgba(255,255,255,0.3);">•</span>
                        <a href="{{ route('clients.index', ['search' => $credit->client->name]) }}" style="color:var(--salmon); font-weight:600; text-decoration:none;">
                            <i class="bi bi-person-circle" style="margin-right:2px;"></i>{{ $credit->client->name }}
                        </a>
                    @endif
                    <span style="color:rgba(255,255,255,0.3);">•</span>
                    <span style="color:var(--silver-light); font-size:13px;">
                        <i class="bi bi-calendar3" style="margin-right:2px;"></i>{{ \Carbon\Carbon::parse($credit->credit_date)->format('d/m/Y') }}
                    </span>
                    <span class="badge-credit-status {{ $credit->status }}">{{ $credit->status_label }}</span>
                </div>

                @if($credit->type === 'personal' && $credit->installment_value > 0)
                    <div style="margin-top:10px; display:flex; align-items:center; gap:15px; flex-wrap:wrap;">
                        <div style="color:#42a5f5; font-size:13px; font-weight:500;">
                            <i class="bi bi-cash-stack" style="margin-right:2px;"></i>Cuota: ${{ number_format($credit->installment_value, 2) }} 
                            @if($credit->total_installments)
                                <span style="color:rgba(255,255,255,0.4); margin-left:4px;">({{ $credit->total_installments }} cuotas en total)</span>
                            @endif
                        </div>
                        <div style="color:#48c78e; font-size:13px; font-weight:500;">
                            <i class="bi bi-check2-square" style="margin-right:2px;"></i>Cuotas pagadas: {{ $credit->installments_paid }} 
                            @if($credit->total_installments)
                                / {{ $credit->total_installments }}
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if($credit->notes)
            <div style="margin-top:12px; padding:10px 14px; background:rgba(255,255,255,0.03); border-radius:8px; border:1px solid rgba(255,255,255,0.06);">
                <span style="font-size:11px; color:rgba(255,255,255,0.35); text-transform:uppercase; letter-spacing:.5px;">Notas</span>
                <p style="margin:4px 0 0; color:var(--silver-light); font-size:13px; line-height:1.5;">{{ $credit->notes }}</p>
            </div>
        @endif
    </div>

    {{-- Big Progress Bar --}}
    <div style="margin-top:18px;">
        <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:8px;">
            <span style="font-size:13px; color:rgba(255,255,255,0.5);">Progreso de pago</span>
            <span style="font-size:15px; font-weight:700; color:{{ $pct >= 100 ? '#48c78e' : '#42a5f5' }};">{{ $pct }}%</span>
        </div>
        <div class="credit-progress-bar" style="height:12px; border-radius:6px;">
            <div class="credit-progress-fill" style="width:{{ $pct }}%; height:100%; border-radius:6px;"></div>
        </div>
    </div>
</div>

{{-- ── Summary Cards ────────────────────────────────────── --}}
<div style="display:flex; gap:12px; margin:20px 0; flex-wrap:wrap;">
    <div style="flex:1; min-width:160px; padding:14px 18px; background:rgba(255,152,0,0.07); border:1px solid rgba(255,152,0,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-tag" style="font-size:22px; color:#ff9800;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Monto Total</div>
            <div style="font-size:18px; font-weight:700; color:#ff9800;">${{ number_format($credit->total_amount, 2) }}</div>
        </div>
    </div>
    <div style="flex:1; min-width:160px; padding:14px 18px; background:rgba(72,199,142,0.07); border:1px solid rgba(72,199,142,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-check2-circle" style="font-size:22px; color:#48c78e;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Total Abonado</div>
            <div style="font-size:18px; font-weight:700; color:#48c78e;">${{ number_format($paid, 2) }}</div>
        </div>
    </div>
    <div style="flex:1; min-width:160px; padding:14px 18px; background:rgba(239,83,80,0.07); border:1px solid rgba(239,83,80,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-hourglass-split" style="font-size:22px; color:#ef5350;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Saldo Pendiente</div>
            <div style="font-size:18px; font-weight:700; color:#ef5350;">${{ number_format($balance, 2) }}</div>
        </div>
    </div>
</div>

{{-- ── Content Grid ─────────────────────────────────────── --}}
<div style="display:grid; grid-template-columns: 1fr 320px; gap:25px; align-items: start;">

    {{-- Left: Payment History --}}
    <div class="client-table-card" style="margin:0;">
        <div class="filter-bar">
            <h3 style="margin:0; font-size:16px; color:var(--white);">
                <i class="bi bi-clock-history" style="margin-right:6px; color:var(--salmon);"></i>Historial de Abonos
            </h3>
            @if($credit->status === 'activo')
                <div style="display:flex; gap:10px;">
                    @if($credit->type === 'personal' && $credit->installment_value > 0)
                        <button class="btn-secondary" style="background:rgba(66,165,245,0.1); border-color:rgba(66,165,245,0.3); color:#42a5f5; font-size:12px; padding:6px 12px;"
                            onclick="fillAbonoForm('{{ $credit->installment_value }}', 'Cuota #{{ $credit->next_installment_number }} - {{ addslashes($credit->description) }}', '{{ date('Y-m-d') }}', 'efectivo')">
                            <i class="bi bi-calendar-check"></i>
                            Pagar Cuota #{{ $credit->next_installment_number }}
                        </button>
                    @endif
                    <button class="btn-primary-action" id="btnOpenCreateAbono">
                        <i class="bi bi-plus-lg"></i>
                        <span>Registrar Abono</span>
                    </button>
                </div>
            @endif
        </div>

        <div class="table-responsive">
            @if($credit->payments->count() > 0)
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto</th>
                            <th>Método</th>
                            <th>Monto</th>
                            <th style="width:80px; text-align:center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($credit->payments as $payment)
                            <tr>
                                <td style="color:var(--silver-light); font-size:13px;">
                                    {{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}
                                </td>
                                <td>
                                    <div style="font-weight:500;">{{ $payment->concept }}</div>
                                    @if($payment->reference)
                                        <div style="font-size:11px; color:rgba(255,255,255,0.3);">Ref: {{ $payment->reference }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span style="font-size:12px; color:var(--silver);">
                                        {{ ucfirst($payment->method === 'canje' ? 'Canje' : $payment->method) }}
                                    </span>
                                </td>
                                <td style="font-weight:600; color:#48c78e;">
                                    ${{ number_format($payment->amount, 2) }}
                                </td>
                                <td style="text-align:center;">
                                    <button type="button" class="btn-action delete" title="Eliminar abono"
                                        onclick="openDeleteAbonoModal({{ $payment->id }}, '{{ number_format($payment->amount, 2) }}', '{{ addslashes($payment->concept) }}')">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state" style="padding:40px 20px;">
                    <div class="empty-state-icon" style="font-size:32px;"><i class="bi bi-cash-stack"></i></div>
                    <p class="empty-state-desc">No se han registrado abonos para este crédito.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Right: Exchange Suggestions (Canje) --}}
     @if($credit->client_id && ((isset($recentClientPayments) && $recentClientPayments->count() > 0) || (isset($recentClientLicenses) && $recentClientLicenses->count() > 0) || (isset($pendingDevelopments) && $pendingDevelopments->count() > 0) || (isset($pendingLoans) && $pendingLoans->count() > 0)))
         <div class="client-table-card" style="margin:0; padding:20px;">
             <h3 style="margin:0 0 15px; font-size:15px; color:var(--white); display:flex; align-items:center; gap:8px;">
                 <i class="bi bi-lightning-charge" style="color:#ff9800;"></i>
                 Sugerencias de Canje
             </h3>
             <p style="font-size:12px; color:var(--silver-light); margin-bottom:15px; line-height:1.4;">
                 Últimos movimientos de <strong>{{ $credit->client->name }}</strong>. Úsalos para abonar rápidamente.
             </p>
 
             <div style="display:flex; flex-direction:column; gap:10px;">
                {{-- Loans Suggestions --}}
                @foreach($pendingLoans as $pl)
                    <div class="exchange-suggestion-item" 
                        style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:10px; cursor:pointer; transition:all .2s;"
                        onclick="fillAbonoForm('{{ $pl->amount }}', 'Canje Préstamo: {{ addslashes($pl->description) }}', '{{ $pl->loan_date }}')">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <span style="font-weight:600; color:#4fc3f7;">${{ number_format($pl->amount, 2) }}</span>
                            <span style="font-size:10px; color:rgba(255,255,255,0.25); text-transform:uppercase;">Préstamo</span>
                        </div>
                        <div style="font-size:11px; color:var(--silver-light);">
                            {{ $pl->description }} (Yo presté)
                        </div>
                    </div>
                @endforeach

                {{-- Pending Developments Suggestions --}}
                @foreach($pendingDevelopments as $pd)
                    <div class="exchange-suggestion-item" 
                        style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:10px; cursor:pointer; transition:all .2s;"
                        onclick="fillAbonoForm('{{ $pd->pending_amount }}', 'Canje Desarrollo: {{ addslashes($pd->title) }}', '{{ date('Y-m-d') }}')">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <span style="font-weight:600; color:#b39ddb;">${{ number_format($pd->pending_amount, 2) }}</span>
                            <span style="font-size:10px; color:rgba(255,255,255,0.25); text-transform:uppercase;">Desarrollo</span>
                        </div>
                        <div style="font-size:11px; color:var(--silver-light);">
                            {{ $pd->title }} (Saldo pendiente)
                        </div>
                    </div>
                @endforeach

                {{-- Payments Suggestions --}}
                @foreach($recentClientPayments as $rp)
                    <div class="exchange-suggestion-item" 
                        style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:10px; cursor:pointer; transition:all .2s;"
                        onclick="fillAbonoForm('{{ $rp->amount }}', 'Pago mensualidad / {{ $rp->notes ? addslashes($rp->notes) : 'Servicio' }}', '{{ $rp->payment_date }}')">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <span style="font-weight:600; color:#48c78e;">${{ number_format($rp->amount, 2) }}</span>
                            <span style="font-size:10px; color:rgba(255,255,255,0.25); text-transform:uppercase;">Pago</span>
                        </div>
                        <div style="font-size:11px; color:var(--silver-light);">
                            {{ \Carbon\Carbon::parse($rp->payment_date)->format('d/m/Y') }} — {{ $rp->notes ?: 'Sin notas' }}
                        </div>
                    </div>
                @endforeach

                {{-- License Suggestions --}}
                @foreach($recentClientLicenses as $rl)
                    {{-- Sugerencia de Instalación --}}
                    @php $setupConcept = 'Canje Instalación: ' . $rl->url; @endphp
                    @if($rl->setup_fee > 0 && !in_array($setupConcept, $usedConcepts) && !($rl->already_paid_setup ?? false))
                        <div class="exchange-suggestion-item" 
                            style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:10px; cursor:pointer; transition:all .2s; margin-bottom:8px;"
                            onclick="fillAbonoForm('{{ $rl->setup_fee }}', '{{ $setupConcept }}', '{{ $rl->created_at->format('Y-m-d') }}')">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                <span style="font-weight:600; color:#ff9800;">${{ number_format($rl->setup_fee, 2) }}</span>
                                <span style="font-size:10px; color:rgba(255,255,255,0.25); text-transform:uppercase;">Instalación</span>
                            </div>
                            <div style="font-size:11px; color:var(--silver-light);">
                                {{ $rl->created_at->format('d/m/Y') }} — {{ $rl->url }}
                            </div>
                        </div>
                    @endif

                    {{-- Sugerencia de Mensualidad --}}
                    @php $monthlyConcept = 'Canje Mensualidad: ' . $rl->url; @endphp
                    @if($rl->monthly_fee > 0 && !in_array($monthlyConcept, $usedConcepts) && !($rl->already_paid_monthly ?? false))
                        <div class="exchange-suggestion-item" 
                            style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:10px; cursor:pointer; transition:all .2s;"
                            onclick="fillAbonoForm('{{ $rl->monthly_fee }}', '{{ $monthlyConcept }}', '{{ $rl->created_at->format('Y-m-d') }}')">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                <span style="font-weight:600; color:#42a5f5;">${{ number_format($rl->monthly_fee, 2) }}</span>
                                <span style="font-size:10px; color:rgba(255,255,255,0.25); text-transform:uppercase;">Mensualidad</span>
                            </div>
                            <div style="font-size:11px; color:var(--silver-light);">
                                {{ $rl->created_at->format('d/m/Y') }} — {{ $rl->url }}
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
            
            <style>
                .exchange-suggestion-item:hover {
                    background: rgba(72,199,142,0.08) !important;
                    border-color: rgba(72,199,142,0.3) !important;
                    transform: translateX(4px);
                }
            </style>
        </div>
    @endif

</div>

{{-- ==============================================================
     MODAL — REGISTRAR ABONO
     ============================================================== --}}
<div class="modal" id="createAbonoModal">
    <div class="modal-backdrop" id="createAbonoBackdrop"></div>
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="bi bi-cash-coin" style="color:#48c78e; margin-right:8px;"></i>Registrar Abono
            </h3>
            <button class="modal-close" id="btnCloseCreateAbono">&times;</button>
        </div>
        <p style="color:var(--silver); font-size:13px; margin: -8px 0 16px; padding: 0 28px;">
            Abono al crédito: <strong style="color:var(--white);">{{ $credit->description }}</strong>
            <br>
            <span style="color:rgba(255,255,255,0.35);">Saldo pendiente: <strong style="color:#ef5350;">${{ number_format($balance, 2) }}</strong></span>
        </p>
        <form action="{{ route('credits.payments.store', $credit) }}" method="POST" autocomplete="off">
            @csrf

            {{-- Concepto --}}
            <div class="form-group">
                <label for="ab_concept" class="form-label">Concepto del Abono *</label>
                <input type="text" name="concept" id="ab_concept" class="form-input"
                    placeholder="Ej. Pago licencia cliente X, Mensualidad mayo, Canje servicio..."
                    value="{{ old('concept') }}" required>
            </div>

            {{-- Monto + Método --}}
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="ab_amount" class="form-label">Monto del Abono ($) *</label>
                    <input type="number" name="amount" id="ab_amount" class="form-input"
                        placeholder="0.00" step="0.01" min="0.01"
                        value="{{ old('amount') }}" required>
                </div>
                <div class="form-group">
                    <label for="ab_method" class="form-label">Método de Pago *</label>
                    <select name="method" id="ab_method" required>
                        <option value="efectivo"    {{ old('method', 'efectivo') === 'efectivo'    ? 'selected' : '' }}>Efectivo</option>
                        <option value="nequi"       {{ old('method') === 'nequi'       ? 'selected' : '' }}>Nequi</option>
                        <option value="bancolombia" {{ old('method') === 'bancolombia' ? 'selected' : '' }}>Bancolombia</option>
                        <option value="canje"       {{ old('method') === 'canje'       ? 'selected' : '' }}>Canje / Intercambio</option>
                    </select>
                </div>
            </div>

            {{-- Fecha --}}
            <div class="form-group">
                <label for="ab_payment_date" class="form-label">Fecha del Abono *</label>
                <input type="date" name="payment_date" id="ab_payment_date" class="form-input"
                    value="{{ old('payment_date', date('Y-m-d')) }}" required>
            </div>

            {{-- Referencia --}}
            <div class="form-group">
                <label for="ab_reference" class="form-label">
                    Referencia / Comprobante <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                </label>
                <input type="text" name="reference" id="ab_reference" class="form-input"
                    placeholder="Ej. Transf. #12345" value="{{ old('reference') }}">
            </div>

            {{-- Notas --}}
            <div class="form-group">
                <label for="ab_notes" class="form-label">
                    Notas <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                </label>
                <textarea name="notes" id="ab_notes" rows="3"
                    placeholder="Observaciones adicionales...">{{ old('notes') }}</textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelCreateAbono">Cancelar</button>
                <button type="submit" class="btn-primary-action">
                    <i class="bi bi-check-circle"></i> Confirmar Abono
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==============================================================
     MODAL — ELIMINAR ABONO
     ============================================================== --}}
<div class="modal" id="deleteAbonoModal">
    <div class="modal-backdrop" id="deleteAbonoBackdrop"></div>
    <div class="modal-content" style="max-width:400px; text-align:center;">
        <div style="font-size:48px; color:#ff5252; margin-bottom:15px;">
            <i class="bi bi-exclamation-circle"></i>
        </div>
        <h3 class="modal-title" style="margin-bottom:10px; display:inline-block;">¿Eliminar Abono?</h3>
        <p style="color:var(--silver-light); font-size:14px; line-height:1.6; margin-bottom:25px;">
            Se eliminará el abono de <strong id="deleteAbonoAmount" style="color:#48c78e;"></strong>
            (<strong id="deleteAbonoConcept" style="color:var(--white);"></strong>).
            Esta acción no se puede deshacer.
        </p>
        <form id="deleteAbonoForm" method="POST">
            @csrf
            @method('DELETE')
            <div style="display:flex; justify-content:center; gap:12px;">
                <button type="button" class="btn-secondary" id="btnCancelDeleteAbono" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn-danger-action" style="flex:1;">Eliminar</button>
            </div>
        </form>
    </div>
</div>

{{-- ── JavaScript ────────────────────────────────────────── --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Create Abono Modal ───────────────────────────────────
    const abonoModal = document.getElementById('createAbonoModal');
    if (abonoModal) {
        const openAbono  = () => abonoModal.classList.add('open');
        const closeAbono = () => abonoModal.classList.remove('open');

        const btnOpen = document.getElementById('btnOpenCreateAbono');
        if (btnOpen) btnOpen.addEventListener('click', openAbono);
        document.getElementById('btnCloseCreateAbono').addEventListener('click', closeAbono);
        document.getElementById('btnCancelCreateAbono').addEventListener('click', closeAbono);
        document.getElementById('createAbonoBackdrop').addEventListener('click', closeAbono);

        // Reopen on validation error
        @if($errors->any())
            openAbono();
        @endif
    }

    // ── Delete Abono Modal ───────────────────────────────────
    const closeDeleteAbono = () => document.getElementById('deleteAbonoModal').classList.remove('open');
    document.getElementById('btnCancelDeleteAbono').addEventListener('click', closeDeleteAbono);
    document.getElementById('deleteAbonoBackdrop').addEventListener('click', closeDeleteAbono);
});

function openDeleteAbonoModal(id, amount, concept) {
    document.getElementById('deleteAbonoAmount').textContent  = '$' + amount;
    document.getElementById('deleteAbonoConcept').textContent = concept;
    document.getElementById('deleteAbonoForm').action         = `/credits/{{ $credit->id }}/payments/${id}`;
    document.getElementById('deleteAbonoModal').classList.add('open');
}

function fillAbonoForm(amount, concept, date, method = 'canje') {
    document.getElementById('ab_amount').value = amount;
    document.getElementById('ab_concept').value = concept;
    document.getElementById('ab_payment_date').value = date;
    document.getElementById('ab_method').value = method;
    document.getElementById('createAbonoModal').classList.add('open');
}
</script>

@endsection
