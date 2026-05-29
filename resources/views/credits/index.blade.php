@extends('layouts.app')

@section('title', 'Créditos - MIK Software Control')
@section('page_title', 'Créditos')
@section('page_subtitle', 'Gestión de compras a crédito y control de abonos.')

@section('content')

{{-- ── Status / Error Alerts ────────────────────────────── --}}
@if(session('status'))
    <div class="alert-banner-success" id="credit-status-alert" style="margin-bottom:25px;">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('status') }}</span>
    </div>
    <script>
        setTimeout(() => {
            const el = document.getElementById('credit-status-alert');
            if (el) el.style.display = 'none';
        }, 7000);
    </script>
@endif

@if($errors->any())
    <div class="alert-banner" id="credit-error-alert" style="margin-bottom:25px;">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

{{-- ── Summary Cards ────────────────────────────────────── --}}
@php
    $activeCredits = \App\Models\Credit::withSum('payments', 'amount')->where('status', 'activo')->get();
    $totalDebt     = $activeCredits->sum('total_amount');
    $totalPaidAll  = 0;
    foreach ($activeCredits as $ac) {
        $totalPaidAll += $ac->total_paid;
    }
    $totalBalance  = $totalDebt - $totalPaidAll;
    $creditCount   = $activeCredits->count();
@endphp
<div style="display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
    <div style="flex:1; min-width:160px; padding:14px 18px; background:rgba(255,152,0,0.07); border:1px solid rgba(255,152,0,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-credit-card-2-front" style="font-size:22px; color:#ff9800;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Créditos Activos</div>
            <div style="font-size:18px; font-weight:700; color:#ff9800;">{{ $creditCount }}</div>
        </div>
    </div>
    <div style="flex:1; min-width:160px; padding:14px 18px; background:rgba(239,83,80,0.07); border:1px solid rgba(239,83,80,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-arrow-down-circle" style="font-size:22px; color:#ef5350;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Deuda Total</div>
            <div style="font-size:18px; font-weight:700; color:#ef5350;">${{ number_format($totalDebt, 2) }}</div>
        </div>
    </div>
    <div style="flex:1; min-width:160px; padding:14px 18px; background:rgba(72,199,142,0.07); border:1px solid rgba(72,199,142,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-check2-circle" style="font-size:22px; color:#48c78e;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Total Abonado</div>
            <div style="font-size:18px; font-weight:700; color:#48c78e;">${{ number_format($totalPaidAll, 2) }}</div>
        </div>
    </div>
    <div style="flex:1; min-width:160px; padding:14px 18px; background:rgba(66,165,245,0.07); border:1px solid rgba(66,165,245,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-hourglass-split" style="font-size:22px; color:#42a5f5;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Saldo Pendiente</div>
            <div style="font-size:18px; font-weight:700; color:#42a5f5;">${{ number_format($totalBalance, 2) }}</div>
        </div>
    </div>
</div>

{{-- ── Main Table Card ──────────────────────────────────── --}}
<div class="client-table-card">

    {{-- Filter Bar --}}
    <div class="filter-bar">
        <form action="{{ route('credits.index') }}" method="GET" class="search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="search-input"
                placeholder="Buscar por acreedor o descripción..."
                value="{{ $search }}" autocomplete="off">
        </form>
        <button class="btn-primary-action" id="btnOpenCreateCredit">
            <i class="bi bi-plus-lg"></i>
            <span>Nuevo Crédito</span>
        </button>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        @if($credits->count() > 0)
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo / Acreedor</th>
                        <th>Descripción</th>
                        <th>Total</th>
                        <th>Abonado</th>
                        <th>Saldo</th>
                        <th style="min-width:130px;">Progreso</th>
                        <th>Estado</th>
                        <th style="width:100px; text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($credits as $credit)
                        @php
                            $paid    = $credit->total_paid;
                            $balance = $credit->balance;
                            $pct     = $credit->progress_percentage;
                        @endphp
                        <tr>
                            <td style="color:var(--silver-light); font-size:13px; white-space:nowrap;">
                                {{ \Carbon\Carbon::parse($credit->credit_date)->format('d/m/Y') }}
                            </td>

                            <td>
                                <div style="font-size:10px; text-transform:uppercase; color:{{ $credit->type === 'personal' ? '#42a5f5' : '#ff9800' }}; font-weight:700; margin-bottom:2px;">
                                    {{ $credit->type_label }}
                                </div>
                                <div style="font-weight:600;">{{ $credit->creditor_name }}</div>
                                @if($credit->client)
                                    <div style="font-size:11px; color:var(--salmon); display:flex; align-items:center; gap:4px; margin-top:2px;">
                                        <i class="bi bi-person-circle"></i>
                                        {{ $credit->client->name }}
                                    </div>
                                @elseif($credit->type === 'personal' && $credit->installment_value > 0)
                                    <div style="font-size:11px; color:#42a5f5; display:flex; align-items:center; gap:4px; margin-top:2px;">
                                        <i class="bi bi-calendar-check"></i>
                                        Cuota: ${{ number_format($credit->installment_value, 2) }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                <span style="color:var(--silver-light); font-size:13px;">{{ $credit->description }}</span>
                            </td>

                            <td style="font-weight:600; white-space:nowrap;">
                                ${{ number_format($credit->total_amount, 2) }}
                            </td>

                            <td style="color:#48c78e; font-weight:600; white-space:nowrap;">
                                ${{ number_format($paid, 2) }}
                            </td>

                            <td style="font-weight:600; white-space:nowrap; color:{{ $balance > 0 ? '#ef5350' : '#48c78e' }};">
                                ${{ number_format($balance, 2) }}
                            </td>

                            <td>
                                <div class="credit-progress-bar">
                                    <div class="credit-progress-fill" style="width:{{ $pct }}%;"></div>
                                </div>
                                <span style="font-size:11px; color:rgba(255,255,255,0.45);">{{ $pct }}%</span>
                            </td>

                            <td>
                                <span class="badge-credit-status {{ $credit->status }}">
                                    {{ $credit->status_label }}
                                </span>
                            </td>

                            <td style="text-align:center;">
                                <div class="actions-cell" style="justify-content:center;">
                                    <a href="{{ route('credits.show', $credit) }}" class="btn-action view" title="Ver detalle y abonos">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    <button type="button" class="btn-action edit" title="Editar crédito"
                                        onclick="openEditCreditModal({{ json_encode($credit) }})">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button type="button" class="btn-action delete" title="Eliminar crédito"
                                        onclick="openDeleteCreditModal({{ $credit->id }}, '{{ addslashes($credit->description) }}', '{{ addslashes($credit->creditor_name) }}')">
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
                <div class="empty-state-icon"><i class="bi bi-credit-card-2-front"></i></div>
                <h3 class="empty-state-title">Sin créditos registrados</h3>
                <p class="empty-state-desc">Registra tu primer crédito haciendo clic en el botón superior.</p>
            </div>
        @endif
    </div>

    @if($credits->count() > 0)
        <div class="pagination-wrapper">
            <div>Mostrando {{ $credits->firstItem() }} al {{ $credits->lastItem() }} de {{ $credits->total() }} créditos</div>
            <div>{{ $credits->appends(['search' => $search])->links('vendor.pagination.mik') }}</div>
        </div>
    @endif

</div>

{{-- ==============================================================
     MODAL — CREAR CRÉDITO
     ============================================================== --}}
<div class="modal" id="createCreditModal">
    <div class="modal-backdrop" id="createCreditBackdrop"></div>
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="bi bi-credit-card-2-front" style="color:var(--salmon); margin-right:8px;"></i>Nuevo Crédito
            </h3>
            <button class="modal-close" id="btnCloseCreateCredit">&times;</button>
        </div>
        <p style="color:var(--silver); font-size:13px; margin: -8px 0 16px; padding: 0 28px;">
            Registra una compra a crédito que necesitas ir abonando.
        </p>
        <form action="{{ route('credits.store') }}" method="POST" autocomplete="off">
            @csrf

            {{-- Tipo de Crédito --}}
            <div class="form-group">
                <label for="cr_type" class="form-label">Tipo de Crédito *</label>
                <select name="type" id="cr_type" class="form-input" required onchange="toggleInstallmentFields('create')">
                    <option value="proveedor">Proveedor (Canje de servicios)</option>
                    <option value="personal">Personal (Pago por cuotas/efectivo)</option>
                </select>
            </div>

            {{-- Cliente (Opcional) --}}
            <div class="form-group" id="create_client_container">
                <label for="cr_client_id" class="form-label">Vincular a Cliente <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span></label>
                <select name="client_id" id="cr_client_id" class="form-input">
                    <option value="">-- No vincular --</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Acreedor --}}
            <div class="form-group">
                <label for="cr_creditor_name" class="form-label">Acreedor / Entidad *</label>
                <input type="text" name="creditor_name" id="cr_creditor_name" class="form-input"
                    placeholder="Ej. Juan Pérez, CrediOrbe, Bancolombia..." value="{{ old('creditor_name') }}" required>
            </div>

            {{-- Monto Total + Fecha --}}
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="cr_total_amount" class="form-label">Monto Total *</label>
                    <input type="number" name="total_amount" id="cr_total_amount" class="form-input"
                        placeholder="0.00" step="0.01" min="0.01" value="{{ old('total_amount') }}" required>
                </div>
                <div class="form-group">
                    <label for="cr_credit_date" class="form-label">Fecha del Crédito *</label>
                    <input type="date" name="credit_date" id="cr_credit_date" class="form-input"
                        value="{{ old('credit_date', date('Y-m-d')) }}" required>
                </div>
            </div>

            {{-- Campos de Cuotas (Solo si es Personal) --}}
            <div id="create_installment_fields" style="display:none; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:15px; background:rgba(255,255,255,0.03); padding:15px; border-radius:8px; border:1px solid rgba(255,255,255,0.05);">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="cr_installment_value" class="form-label">Valor Cuota ($)</label>
                    <input type="number" name="installment_value" id="cr_installment_value" class="form-input" placeholder="0.00" step="0.01">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="cr_total_installments" class="form-label">Total Cuotas</label>
                    <input type="number" name="total_installments" id="cr_total_installments" class="form-input" placeholder="Ej. 12">
                </div>
            </div>

            {{-- Descripción --}}
            <div class="form-group">
                <label for="cr_description" class="form-label">Descripción / Artículo *</label>
                <input type="text" name="description" id="cr_description" class="form-input"
                    placeholder="Ej. Moto Yamaha R3, Préstamo personal..." value="{{ old('description') }}" required>
            </div>

            {{-- Notas --}}
            <div class="form-group">
                <label for="cr_notes" class="form-label">
                    Notas <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                </label>
                <textarea name="notes" id="cr_notes" rows="3"
                    placeholder="Condiciones, número de cuotas, observaciones...">{{ old('notes') }}</textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelCreateCredit">Cancelar</button>
                <button type="submit" class="btn-primary-action">
                    <i class="bi bi-check-circle"></i> Registrar Crédito
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==============================================================
     MODAL — EDITAR CRÉDITO
     ============================================================== --}}
<div class="modal" id="editCreditModal">
    <div class="modal-backdrop" id="editCreditBackdrop"></div>
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="bi bi-pencil-square" style="color:#42a5f5; margin-right:8px;"></i>Editar Crédito
            </h3>
            <button class="modal-close" id="btnCloseEditCredit">&times;</button>
        </div>
        <form id="editCreditForm" method="POST" autocomplete="off">
            @csrf
            @method('PUT')

            {{-- Tipo de Crédito --}}
            <div class="form-group">
                <label for="ed_type" class="form-label">Tipo de Crédito *</label>
                <select name="type" id="ed_type" class="form-input" required onchange="toggleInstallmentFields('edit')">
                    <option value="proveedor">Proveedor (Canje de servicios)</option>
                    <option value="personal">Personal (Pago por cuotas/efectivo)</option>
                </select>
            </div>

            {{-- Cliente (Opcional) --}}
            <div class="form-group" id="edit_client_container">
                <label for="ed_client_id" class="form-label">Vincular a Cliente</label>
                <select name="client_id" id="ed_client_id" class="form-input">
                    <option value="">-- No vincular --</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Acreedor --}}
            <div class="form-group">
                <label for="ed_creditor_name" class="form-label">Acreedor / Entidad *</label>
                <input type="text" name="creditor_name" id="ed_creditor_name" class="form-input" required>
            </div>

            {{-- Monto Total + Fecha --}}
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="ed_total_amount" class="form-label">Monto Total *</label>
                    <input type="number" name="total_amount" id="ed_total_amount" class="form-input" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label for="ed_credit_date" class="form-label">Fecha del Crédito *</label>
                    <input type="date" name="credit_date" id="ed_credit_date" class="form-input" required>
                </div>
            </div>

            {{-- Campos de Cuotas (Solo si es Personal) --}}
            <div id="edit_installment_fields" style="display:none; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:15px; background:rgba(255,255,255,0.03); padding:15px; border-radius:8px; border:1px solid rgba(255,255,255,0.05);">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="ed_installment_value" class="form-label">Valor Cuota ($)</label>
                    <input type="number" name="installment_value" id="ed_installment_value" class="form-input" step="0.01">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="ed_total_installments" class="form-label">Total Cuotas</label>
                    <input type="number" name="total_installments" id="ed_total_installments" class="form-input">
                </div>
            </div>

            {{-- Descripción --}}
            <div class="form-group">
                <label for="ed_description" class="form-label">Descripción / Artículo *</label>
                <input type="text" name="description" id="ed_description" class="form-input" required>
            </div>

            <div class="form-group">
                <label for="ed_status" class="form-label">Estado *</label>
                <select name="status" id="ed_status" class="form-input" required>
                    <option value="activo">Activo</option>
                    <option value="pagado">Pagado</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>

            {{-- Notas --}}
            <div class="form-group">
                <label for="ed_notes" class="form-label">
                    Notas <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                </label>
                <textarea name="notes" id="ed_notes" rows="3"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelEditCredit">Cancelar</button>
                <button type="submit" class="btn-primary-action">
                    <i class="bi bi-check-circle"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==============================================================
     MODAL — ELIMINAR CRÉDITO
     ============================================================== --}}
<div class="modal" id="deleteCreditModal">
    <div class="modal-backdrop" id="deleteCreditBackdrop"></div>
    <div class="modal-content" style="max-width:400px; text-align:center;">
        <div style="font-size:48px; color:#ff5252; margin-bottom:15px;">
            <i class="bi bi-exclamation-circle"></i>
        </div>
        <h3 class="modal-title" style="margin-bottom:10px; display:inline-block;">¿Eliminar Crédito?</h3>
        <p style="color:var(--silver-light); font-size:14px; line-height:1.6; margin-bottom:25px;">
            Se eliminará el crédito <strong id="deleteCreditDesc" style="color:var(--white);"></strong>
            de <strong id="deleteCreditCreditor" style="color:#ff9800;"></strong>
            y todos sus abonos asociados. Esta acción no se puede deshacer.
        </p>
        <form id="deleteCreditForm" method="POST">
            @csrf
            @method('DELETE')
            <div style="display:flex; justify-content:center; gap:12px;">
                <button type="button" class="btn-secondary" id="btnCancelDeleteCredit" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn-danger-action" style="flex:1;">Eliminar</button>
            </div>
        </form>
    </div>
</div>

{{-- ── JavaScript ────────────────────────────────────────── --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Create Credit Modal ──────────────────────────────────
    const createModal = document.getElementById('createCreditModal');
    const openCreate  = () => createModal.classList.add('open');
    const closeCreate = () => createModal.classList.remove('open');

    document.getElementById('btnOpenCreateCredit').addEventListener('click', openCreate);
    document.getElementById('btnCloseCreateCredit').addEventListener('click', closeCreate);
    document.getElementById('btnCancelCreateCredit').addEventListener('click', closeCreate);
    document.getElementById('createCreditBackdrop').addEventListener('click', closeCreate);

    // ── Edit Credit Modal ────────────────────────────────────
    const editModal = document.getElementById('editCreditModal');
    const closeEdit = () => editModal.classList.remove('open');

    document.getElementById('btnCloseEditCredit').addEventListener('click', closeEdit);
    document.getElementById('btnCancelEditCredit').addEventListener('click', closeEdit);
    document.getElementById('editCreditBackdrop').addEventListener('click', closeEdit);

    // ── Delete Credit Modal ──────────────────────────────────
    const closeDelete = () => document.getElementById('deleteCreditModal').classList.remove('open');
    document.getElementById('btnCancelDeleteCredit').addEventListener('click', closeDelete);
    document.getElementById('deleteCreditBackdrop').addEventListener('click', closeDelete);

    // ── Reopen on error ──────────────────────────────────────
    @if($errors->any())
        openCreate();
    @endif
});

function toggleInstallmentFields(mode) {
    const type = document.getElementById(mode === 'create' ? 'cr_type' : 'ed_type').value;
    const installmentFields = document.getElementById(mode === 'create' ? 'create_installment_fields' : 'edit_installment_fields');
    const clientContainer = document.getElementById(mode === 'create' ? 'create_client_container' : 'edit_client_container');

    if (type === 'personal') {
        installmentFields.style.display = 'grid';
        clientContainer.style.display = 'none';
    } else {
        installmentFields.style.display = 'none';
        clientContainer.style.display = 'block';
    }
}

function openEditCreditModal(credit) {
    document.getElementById('editCreditForm').action    = `/credits/${credit.id}`;
    document.getElementById('ed_type').value            = credit.type;
    document.getElementById('ed_client_id').value       = credit.client_id || '';
    document.getElementById('ed_creditor_name').value   = credit.creditor_name;
    document.getElementById('ed_description').value     = credit.description;
    document.getElementById('ed_total_amount').value    = credit.total_amount;
    document.getElementById('ed_installment_value').value = credit.installment_value || '';
    document.getElementById('ed_total_installments').value = credit.total_installments || '';
    document.getElementById('ed_credit_date').value     = credit.credit_date;
    document.getElementById('ed_status').value          = credit.status;
    document.getElementById('ed_notes').value           = credit.notes || '';

    toggleInstallmentFields('edit');
    document.getElementById('editCreditModal').classList.add('open');
}

function openDeleteCreditModal(id, desc, creditor) {
    document.getElementById('deleteCreditDesc').textContent     = desc;
    document.getElementById('deleteCreditCreditor').textContent = creditor;
    document.getElementById('deleteCreditForm').action          = `/credits/${id}`;
    document.getElementById('deleteCreditModal').classList.add('open');
}
</script>

@endsection
