@extends('layouts.app')

@section('title', 'Pagos y Abonos - MIK Software Control')
@section('page_title', 'Pagos y Abonos')
@section('page_subtitle', 'Registro histórico de todos los pagos e ingresos del sistema.')

@section('content')

{{-- ── Status / Error Alerts ────────────────────────────── --}}
@if(session('status'))
    <div class="alert-banner-success" id="pay-status-alert" style="margin-bottom:25px;">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('status') }}</span>
    </div>
    <script>
        setTimeout(() => {
            const el = document.getElementById('pay-status-alert');
            if (el) el.style.display = 'none';
        }, 7000);
    </script>
@endif

@if($errors->any())
    <div class="alert-banner" id="pay-error-alert" style="margin-bottom:25px;">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

{{-- ── Summary Cards ────────────────────────────────────── --}}
@php
    $pageTotal    = $payments->getCollection()->sum('amount');
    $globalTotal  = \App\Models\Payment::sum('amount');
    $monthTotal   = \App\Models\Payment::whereMonth('payment_date', now()->month)
                        ->whereYear('payment_date', now()->year)->sum('amount');
@endphp
<div style="display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
    <div style="flex:1; min-width:160px; padding:14px 18px; background:rgba(72,199,142,0.07); border:1px solid rgba(72,199,142,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-cash-coin" style="font-size:22px; color:#48c78e;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Este Mes</div>
            <div style="font-size:18px; font-weight:700; color:#48c78e;">${{ number_format($monthTotal, 2) }}</div>
        </div>
    </div>
    <div style="flex:1; min-width:160px; padding:14px 18px; background:rgba(66,165,245,0.07); border:1px solid rgba(66,165,245,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-wallet2" style="font-size:22px; color:#42a5f5;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Total Histórico</div>
            <div style="font-size:18px; font-weight:700; color:#42a5f5;">${{ number_format($globalTotal, 2) }}</div>
        </div>
    </div>
</div>

{{-- ── Main Table Card ──────────────────────────────────── --}}
<div class="client-table-card">

    {{-- Filter Bar --}}
    <div class="filter-bar">
        <form action="{{ route('payments.index') }}" method="GET" class="search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="search-input"
                placeholder="Buscar por cliente, proyecto o referencia..."
                value="{{ $search }}" autocomplete="off">
        </form>
        <button class="btn-primary-action" id="btnOpenCreatePayment">
            <i class="bi bi-plus-lg"></i>
            <span>Registrar Pago o Abono</span>
        </button>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        @if($payments->count() > 0)
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Destino / Concepto</th>
                        <th>Referencia</th>
                        <th>Método</th>
                        <th>Monto</th>
                        <th style="width:60px; text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr>
                            <td style="color:var(--silver-light); font-size:13px; white-space:nowrap;">
                                {{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}
                            </td>

                            <td style="font-weight:600;">{{ $payment->client->name }}</td>

                            <td>
                                @if($payment->development)
                                    <span class="payment-dest">{{ $payment->development->title }}</span>
                                    <span class="payment-ref">
                                        <span class="badge-type {{ $payment->development->type }}" style="font-size:10px; padding:2px 7px;">
                                            {{ $payment->development->type_label }}
                                        </span>
                                    </span>
                                @else
                                    <span class="payment-dest">Cuenta Global</span>
                                    <span class="payment-ref">Abono general al cliente</span>
                                @endif
                            </td>

                            <td style="color:var(--silver-light); font-size:13px;">
                                {{ $payment->reference ?: '—' }}
                            </td>

                            <td>
                                <span class="badge-method {{ $payment->method }}">
                                    {{ $payment->method_label }}
                                </span>
                            </td>

                            <td>
                                <span class="payment-amount">${{ number_format($payment->amount, 2) }}</span>
                            </td>

                            <td style="text-align:center;">
                                <div class="actions-cell" style="justify-content:center;">
                                    <button type="button" class="btn-action delete" title="Eliminar pago"
                                        onclick="openDeletePaymentModal({{ $payment->id }}, '{{ number_format($payment->amount, 2) }}', '{{ addslashes($payment->client->name) }}')">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-receipt"></i></div>
                <h3 class="empty-state-title">Sin pagos registrados</h3>
                <p class="empty-state-desc">Registra el primer pago o abono haciendo clic en el botón superior.</p>
            </div>
        @endif
    </div>

    @if($payments->count() > 0)
        <div class="pagination-wrapper">
            <div>Mostrando {{ $payments->firstItem() }} al {{ $payments->lastItem() }} de {{ $payments->total() }} pagos</div>
            <div>{{ $payments->appends(['search' => $search])->links('vendor.pagination.mik') }}</div>
        </div>
    @endif

</div>

{{-- ==============================================================
     MODAL — REGISTRAR PAGO
     ============================================================== --}}
<div class="modal" id="createPaymentModal">
    <div class="modal-backdrop" id="createPaymentBackdrop"></div>
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="bi bi-receipt" style="color:var(--salmon); margin-right:8px;"></i>Registrar Pago o Abono
            </h3>
            <button class="modal-close" id="btnCloseCreatePayment">&times;</button>
        </div>
        <p style="color:var(--silver); font-size:13px; margin: -8px 0 16px; padding: 0 28px;">
            Selecciona un cliente y el destino del pago.
        </p>
        <form action="{{ route('payments.store') }}" method="POST" autocomplete="off">
            @csrf

            {{-- Cliente --}}
            <div class="form-group">
                <label for="pay_client_id" class="form-label">Cliente *</label>
                <select name="client_id" id="pay_client_id" required>
                    <option value="">Selecciona un cliente</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ old('client_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Destino del Abono --}}
            <div class="form-group">
                <label for="pay_development_id" class="form-label">Destino del Abono *</label>
                <select name="development_id" id="pay_development_id">
                    <option value="">Cuenta Global (Recomendado)</option>
                </select>
            </div>

            {{-- Monto + Método --}}
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="pay_amount" class="form-label">Monto del Abono ($) *</label>
                    <input type="number" name="amount" id="pay_amount" class="form-input"
                        placeholder="0.00" step="0.01" min="0.01"
                        value="{{ old('amount') }}" required>
                </div>
                <div class="form-group">
                    <label for="pay_method" class="form-label">Método de Pago *</label>
                    <select name="method" id="pay_method" required>
                        <option value="efectivo"    {{ old('method', 'efectivo') === 'efectivo'    ? 'selected' : '' }}>Efectivo</option>
                        <option value="nequi"       {{ old('method') === 'nequi'       ? 'selected' : '' }}>Nequi</option>
                        <option value="bancolombia" {{ old('method') === 'bancolombia' ? 'selected' : '' }}>Bancolombia</option>
                    </select>
                </div>
            </div>

            {{-- Fecha --}}
            <div class="form-group">
                <label for="pay_payment_date" class="form-label">Fecha del Pago *</label>
                <input type="date" name="payment_date" id="pay_payment_date" class="form-input"
                    value="{{ old('payment_date', date('Y-m-d')) }}" required>
            </div>

            {{-- Referencia --}}
            <div class="form-group">
                <label for="pay_reference" class="form-label">
                    Referencia / Comprobante <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                </label>
                <input type="text" name="reference" id="pay_reference" class="form-input"
                    placeholder="Ej. Transf. #12345"
                    value="{{ old('reference') }}">
            </div>

            {{-- Notas --}}
            <div class="form-group">
                <label for="pay_notes" class="form-label">
                    Notas <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                </label>
                <textarea name="notes" id="pay_notes" rows="3"
                    placeholder="Observaciones adicionales...">{{ old('notes') }}</textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelCreatePayment">Cancelar</button>
                <button type="submit" class="btn-primary-action">
                    <i class="bi bi-check-circle"></i> Confirmar Pago
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==============================================================
     MODAL — ELIMINAR PAGO
     ============================================================== --}}
<div class="modal" id="deletePaymentModal">
    <div class="modal-backdrop" id="deletePaymentBackdrop"></div>
    <div class="modal-content" style="max-width:400px; text-align:center;">
        <div style="font-size:48px; color:#ff5252; margin-bottom:15px;">
            <i class="bi bi-exclamation-circle"></i>
        </div>
        <h3 class="modal-title" style="margin-bottom:10px; display:inline-block;">¿Eliminar Pago?</h3>
        <p style="color:var(--silver-light); font-size:14px; line-height:1.6; margin-bottom:25px;">
            Se eliminará el pago de <strong id="deletePaymentAmount" style="color:#48c78e;"></strong>
            de <strong id="deletePaymentClient" style="color:var(--white);"></strong>.
            Esta acción no se puede deshacer.
        </p>
        <form id="deletePaymentForm" method="POST">
            @csrf
            @method('DELETE')
            <div style="display:flex; justify-content:center; gap:12px;">
                <button type="button" class="btn-secondary" id="btnCancelDeletePayment" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn-danger-action" style="flex:1;">Eliminar</button>
            </div>
        </form>
    </div>
</div>

{{-- ── JavaScript ────────────────────────────────────────── --}}
@php
    $devForJs = $developments->map(fn($d) => [
        'id'        => $d->id,
        'client_id' => $d->client_id,
        'title'     => $d->title,
        'type'      => $d->type,
    ]);
@endphp
<script>
const allDevelopments = {!! json_encode($devForJs) !!};

function populateDevSelect(clientId, selectedId = null) {
    const sel = document.getElementById('pay_development_id');
    sel.innerHTML = '<option value="">Cuenta Global (Recomendado)</option>';
    if (!clientId) return;
    const filtered = allDevelopments.filter(d => String(d.client_id) === String(clientId));
    if (filtered.length === 0) return;

    const grpMejora   = filtered.filter(d => d.type === 'mejora');
    const grpProyecto = filtered.filter(d => d.type === 'proyecto');

    const addGroup = (label, items) => {
        if (!items.length) return;
        const og = document.createElement('optgroup');
        og.label = label;
        items.forEach(d => {
            const opt = document.createElement('option');
            opt.value       = d.id;
            opt.textContent = d.title;
            if (selectedId && String(d.id) === String(selectedId)) opt.selected = true;
            og.appendChild(opt);
        });
        sel.appendChild(og);
    };
    addGroup('Mejoras', grpMejora);
    addGroup('Proyectos', grpProyecto);
}

document.addEventListener('DOMContentLoaded', function () {

    // ── Create Payment ────────────────────────────────────────
    const modal    = document.getElementById('createPaymentModal');
    const clientSel = document.getElementById('pay_client_id');

    const openModal  = () => modal.classList.add('open');
    const closeModal = () => modal.classList.remove('open');

    document.getElementById('btnOpenCreatePayment').addEventListener('click', openModal);
    document.getElementById('btnCloseCreatePayment').addEventListener('click', closeModal);
    document.getElementById('btnCancelCreatePayment').addEventListener('click', closeModal);
    document.getElementById('createPaymentBackdrop').addEventListener('click', closeModal);

    clientSel.addEventListener('change', () => populateDevSelect(clientSel.value));

    // ── Delete ────────────────────────────────────────────────
    const closeDelete = () => document.getElementById('deletePaymentModal').classList.remove('open');
    document.getElementById('btnCancelDeletePayment').addEventListener('click', closeDelete);
    document.getElementById('deletePaymentBackdrop').addEventListener('click', closeDelete);

    // ── Reopen on error ───────────────────────────────────────
    @if($errors->any())
        openModal();
        @if(old('client_id'))
            populateDevSelect({{ old('client_id') }});
        @endif
    @endif
});

function openDeletePaymentModal(id, amount, clientName) {
    document.getElementById('deletePaymentAmount').textContent = '$' + amount;
    document.getElementById('deletePaymentClient').textContent = clientName;
    document.getElementById('deletePaymentForm').action        = `/payments/${id}`;
    document.getElementById('deletePaymentModal').classList.add('open');
}
</script>

@endsection
