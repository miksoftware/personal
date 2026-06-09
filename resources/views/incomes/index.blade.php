@extends('layouts.app')

@section('title', 'Ingresos - MIK Software Control')
@section('page_title', 'Ingresos')
@section('page_subtitle', 'Registro y control de ingresos personales y del negocio.')

@section('content')

{{-- ── Status / Error Alerts ────────────────────────────── --}}
@if(session('status'))
    <div class="alert-banner-success" id="income-status-alert" style="margin-bottom:25px;">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('status') }}</span>
    </div>
    <script>
        setTimeout(() => {
            const el = document.getElementById('income-status-alert');
            if (el) el.style.display = 'none';
        }, 7000);
    </script>
@endif

@if($errors->any())
    <div class="alert-banner" id="income-error-alert" style="margin-bottom:25px;">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

{{-- ── Summary Cards ────────────────────────────────────── --}}
@php
    $totalIncomesMonth = \App\Models\Income::whereMonth('income_date', now()->month)
        ->whereYear('income_date', now()->year)
        ->sum('amount');
    $totalIncomesYear = \App\Models\Income::whereYear('income_date', now()->year)
        ->sum('amount');
@endphp
<div style="display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
    <div style="flex:1; min-width:160px; padding:14px 18px; background:rgba(76,175,80,0.07); border:1px solid rgba(76,175,80,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-calendar-event" style="font-size:22px; color:#4caf50;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Ingresos del Mes</div>
            <div style="font-size:18px; font-weight:700; color:#4caf50;">${{ number_format($totalIncomesMonth, 2) }}</div>
        </div>
    </div>
    <div style="flex:1; min-width:160px; padding:14px 18px; background:rgba(38,166,154,0.07); border:1px solid rgba(38,166,154,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-graph-up-arrow" style="font-size:22px; color:#26a69a;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Ingresos del Año</div>
            <div style="font-size:18px; font-weight:700; color:#26a69a;">${{ number_format($totalIncomesYear, 2) }}</div>
        </div>
    </div>
</div>

{{-- ── Main Table Card ──────────────────────────────────── --}}
<div class="client-table-card">

    {{-- Filter Bar --}}
    <div class="filter-bar">
        <form action="{{ route('incomes.index') }}" method="GET" class="search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="search-input"
                placeholder="Buscar por descripción, categoría..."
                value="{{ $search }}" autocomplete="off">
        </form>
        <button class="btn-primary-action" id="btnOpenCreateIncome">
            <i class="bi bi-plus-lg"></i>
            <span>Registrar Ingreso</span>
        </button>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        @if($incomes->count() > 0)
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Categoría</th>
                        <th>Cuenta Destino</th>
                        <th style="text-align:right;">Monto</th>
                        <th style="width:100px; text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($incomes as $income)
                        <tr>
                            <td style="color:var(--silver-light); font-size:13px; white-space:nowrap;">
                                {{ \Carbon\Carbon::parse($income->income_date)->format('d/m/Y') }}
                            </td>
                            <td>
                                <div style="font-weight:600; color:var(--white);">{{ $income->description }}</div>
                                @if($income->reference)
                                    <div style="font-size:11px; color:rgba(255,255,255,0.3);">Ref: {{ $income->reference }}</div>
                                @endif
                            </td>
                            <td>
                                <span style="font-size:12px; color:var(--silver); background:rgba(255,255,255,0.05); padding:2px 8px; border-radius:4px;">
                                    {{ $income->category ?: 'General' }}
                                </span>
                            </td>
                            <td>
                                <div style="font-size:13px; color:#4caf50; font-weight:600;">
                                    <i class="bi bi-bank"></i> {{ $income->bankAccount->name }}
                                </div>
                            </td>
                            <td style="text-align:right; font-weight:700; color:#4caf50; font-size:15px;">
                                ${{ number_format($income->amount, 2) }}
                            </td>
                            <td style="text-align:center;">
                                <div class="actions-cell" style="justify-content:center;">
                                    <button type="button" class="btn-action edit" title="Editar ingreso"
                                        onclick="openEditIncomeModal({{ json_encode($income) }})">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button type="button" class="btn-action delete" title="Eliminar ingreso"
                                        onclick="openDeleteIncomeModal({{ $income->id }}, '{{ addslashes($income->description) }}', '{{ number_format($income->amount, 2) }}')">
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
                <div class="empty-state-icon"><i class="bi bi-cash-coin"></i></div>
                <h3 class="empty-state-title">Sin ingresos registrados</h3>
                <p class="empty-state-desc">Registra tu primer ingreso haciendo clic en el botón superior.</p>
            </div>
        @endif
    </div>

    @if($incomes->count() > 0)
        <div class="pagination-wrapper">
            <div>Mostrando {{ $incomes->firstItem() }} al {{ $incomes->lastItem() }} de {{ $incomes->total() }} ingresos</div>
            <div>{{ $incomes->appends(['search' => $search])->links('vendor.pagination.mik') }}</div>
        </div>
    @endif

</div>

{{-- ==============================================================
     MODAL — REGISTRAR INGRESO
     ============================================================== --}}
<div class="modal" id="createIncomeModal">
    <div class="modal-backdrop" id="createIncomeBackdrop"></div>
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="bi bi-cash-stack" style="color:#4caf50; margin-right:8px;"></i>Nuevo Ingreso
            </h3>
            <button class="modal-close" id="btnCloseCreateIncome">&times;</button>
        </div>
        <form action="{{ route('incomes.store') }}" method="POST" autocomplete="off">
            @csrf

            {{-- Descripción --}}
            <div class="form-group">
                <label for="in_description" class="form-label">Descripción del Ingreso *</label>
                <input type="text" name="description" id="in_description" class="form-input"
                    placeholder="Ej. Pago de proyecto freelance, Salario, Venta..." value="{{ old('description') }}" required>
            </div>

            {{-- Categoría + Monto --}}
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="in_category" class="form-label">Categoría</label>
                    <input type="text" name="category" id="in_category" class="form-input"
                        placeholder="Ej. Freelance, Salario, Venta..." value="{{ old('category') }}">
                </div>
                <div class="form-group">
                    <label for="in_amount" class="form-label">Monto ($) *</label>
                    <input type="number" name="amount" id="in_amount" class="form-input"
                        placeholder="0.00" step="0.01" min="0.01" value="{{ old('amount') }}" required>
                </div>
            </div>

            {{-- Cuenta Bancaria --}}
            <div class="form-group">
                <label for="in_bank_account_id" class="form-label">Cuenta Bancaria de Destino *</label>
                <select name="bank_account_id" id="in_bank_account_id" class="form-input" required>
                    <option value="">-- Selecciona una cuenta --</option>
                    @foreach($bankAccounts as $account)
                        <option value="{{ $account->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>
                            {{ $account->name }} (${{ number_format($account->current_balance, 2) }})
                        </option>
                    @endforeach
                </select>
                <p style="font-size:10px; color:rgba(255,255,255,0.35); margin-top:4px;">Se sumará automáticamente al saldo de la cuenta elegida.</p>
            </div>

            {{-- Fecha + Referencia --}}
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="in_income_date" class="form-label">Fecha *</label>
                    <input type="date" name="income_date" id="in_income_date" class="form-input"
                        value="{{ old('income_date', date('Y-m-d')) }}" required>
                </div>
                <div class="form-group">
                    <label for="in_reference" class="form-label">Referencia</label>
                    <input type="text" name="reference" id="in_reference" class="form-input"
                        placeholder="Ej. Transferencia #123" value="{{ old('reference') }}">
                </div>
            </div>

            {{-- Notas --}}
            <div class="form-group">
                <label for="in_notes" class="form-label">Notas <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span></label>
                <textarea name="notes" id="in_notes" rows="3" class="form-input"
                    placeholder="Observaciones adicionales...">{{ old('notes') }}</textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelCreateIncome">Cancelar</button>
                <button type="submit" class="btn-primary-action">
                    <i class="bi bi-check-circle"></i> Registrar Ingreso
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==============================================================
     MODAL — EDITAR INGRESO
     ============================================================== --}}
<div class="modal" id="editIncomeModal">
    <div class="modal-backdrop" id="editIncomeBackdrop"></div>
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="bi bi-pencil-square" style="color:#42a5f5; margin-right:8px;"></i>Editar Ingreso
            </h3>
            <button class="modal-close" id="btnCloseEditIncome">&times;</button>
        </div>
        <form id="editIncomeForm" method="POST" autocomplete="off">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="ed_description" class="form-label">Descripción *</label>
                <input type="text" name="description" id="ed_description" class="form-input" required>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="ed_category" class="form-label">Categoría</label>
                    <input type="text" name="category" id="ed_category" class="form-input">
                </div>
                <div class="form-group">
                    <label for="ed_amount" class="form-label">Monto ($) *</label>
                    <input type="number" name="amount" id="ed_amount" class="form-input" step="0.01" min="0.01" required>
                </div>
            </div>

            {{-- Cuenta Bancaria --}}
            <div class="form-group">
                <label for="ed_bank_account_id" class="form-label">Cuenta Bancaria de Destino *</label>
                <select name="bank_account_id" id="ed_bank_account_id" class="form-input" required>
                    @foreach($bankAccounts as $account)
                        <option value="{{ $account->id }}">
                            {{ $account->name }} (${{ number_format($account->current_balance, 2) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="ed_income_date" class="form-label">Fecha *</label>
                    <input type="date" name="income_date" id="ed_income_date" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="ed_reference" class="form-label">Referencia</label>
                    <input type="text" name="reference" id="ed_reference" class="form-input">
                </div>
            </div>

            <div class="form-group">
                <label for="ed_notes" class="form-label">Notas</label>
                <textarea name="notes" id="ed_notes" rows="3" class="form-input"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelEditIncome">Cancelar</button>
                <button type="submit" class="btn-primary-action">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

{{-- ==============================================================
     MODAL — ELIMINAR INGRESO
     ============================================================== --}}
<div class="modal" id="deleteIncomeModal">
    <div class="modal-backdrop" id="deleteIncomeBackdrop"></div>
    <div class="modal-content" style="max-width:400px; text-align:center;">
        <div style="font-size:48px; color:#ff5252; margin-bottom:15px;">
            <i class="bi bi-exclamation-circle"></i>
        </div>
        <h3 class="modal-title" style="margin-bottom:10px; display:inline-block;">¿Eliminar Ingreso?</h3>
        <p style="color:var(--silver-light); font-size:14px; line-height:1.6; margin-bottom:25px;">
            Se eliminará el ingreso <strong id="deleteIncomeDesc" style="color:var(--white);"></strong>
            por <strong id="deleteIncomeAmount" style="color:#4caf50;"></strong>.
            Si se sumó a una cuenta, el saldo será revertido.
        </p>
        <form id="deleteIncomeForm" method="POST">
            @csrf
            @method('DELETE')
            <div style="display:flex; justify-content:center; gap:12px;">
                <button type="button" class="btn-secondary" id="btnCancelDeleteIncome" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn-danger-action" style="flex:1;">Eliminar</button>
            </div>
        </form>
    </div>
</div>

{{-- ── JavaScript ────────────────────────────────────────── --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Create Modal ──────────────────────────────────────
    const createModal = document.getElementById('createIncomeModal');
    const openCreate  = () => createModal.classList.add('open');
    const closeCreate = () => createModal.classList.remove('open');

    document.getElementById('btnOpenCreateIncome').addEventListener('click', openCreate);
    document.getElementById('btnCloseCreateIncome').addEventListener('click', closeCreate);
    document.getElementById('btnCancelCreateIncome').addEventListener('click', closeCreate);
    document.getElementById('createIncomeBackdrop').addEventListener('click', closeCreate);

    // ── Edit Modal ────────────────────────────────────────
    const editModal = document.getElementById('editIncomeModal');
    const closeEdit = () => editModal.classList.remove('open');

    document.getElementById('btnCloseEditIncome').addEventListener('click', closeEdit);
    document.getElementById('btnCancelEditIncome').addEventListener('click', closeEdit);
    document.getElementById('editIncomeBackdrop').addEventListener('click', closeEdit);

    // ── Delete Modal ──────────────────────────────────────
    const deleteModal = document.getElementById('deleteIncomeModal');
    const closeDelete = () => deleteModal.classList.remove('open');

    document.getElementById('btnCancelDeleteIncome').addEventListener('click', closeDelete);
    document.getElementById('deleteIncomeBackdrop').addEventListener('click', closeDelete);

    // Reopen on error
    @if($errors->any())
        openCreate();
    @endif
});

function openEditIncomeModal(income) {
    document.getElementById('editIncomeForm').action = `/incomes/${income.id}`;
    document.getElementById('ed_description').value   = income.description;
    document.getElementById('ed_category').value      = income.category || '';
    document.getElementById('ed_amount').value        = income.amount;
    document.getElementById('ed_bank_account_id').value = income.bank_account_id || '';
    document.getElementById('ed_income_date').value  = income.income_date;
    document.getElementById('ed_reference').value     = income.reference || '';
    document.getElementById('ed_notes').value         = income.notes || '';
    document.getElementById('editIncomeModal').classList.add('open');
}

function openDeleteIncomeModal(id, desc, amount) {
    document.getElementById('deleteIncomeForm').action = `/incomes/${id}`;
    document.getElementById('deleteIncomeDesc').textContent = desc;
    document.getElementById('deleteIncomeAmount').textContent = '$' + amount;
    document.getElementById('deleteIncomeModal').classList.add('open');
}
</script>

@endsection