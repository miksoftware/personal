@extends('layouts.app')

@section('title', 'Gastos y Compras - MIK Software Control')
@section('page_title', 'Gastos / Compras')
@section('page_subtitle', 'Registro y control de egresos y compras personales.')

@section('content')

{{-- ── Status / Error Alerts ────────────────────────────── --}}
@if(session('status'))
    <div class="alert-banner-success" id="expense-status-alert" style="margin-bottom:25px;">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('status') }}</span>
    </div>
    <script>
        setTimeout(() => {
            const el = document.getElementById('expense-status-alert');
            if (el) el.style.display = 'none';
        }, 7000);
    </script>
@endif

@if($errors->any())
    <div class="alert-banner" id="expense-error-alert" style="margin-bottom:25px;">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

{{-- ── Summary Cards ────────────────────────────────────── --}}
@php
    $totalExpensesMonth = \App\Models\Expense::whereMonth('expense_date', now()->month)
        ->whereYear('expense_date', now()->year)
        ->sum('amount');
    $totalExpensesYear = \App\Models\Expense::whereYear('expense_date', now()->year)
        ->sum('amount');
@endphp
<div style="display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
    <div style="flex:1; min-width:160px; padding:14px 18px; background:rgba(239,83,80,0.07); border:1px solid rgba(239,83,80,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-calendar-event" style="font-size:22px; color:#ef5350;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Gastos del Mes</div>
            <div style="font-size:18px; font-weight:700; color:#ef5350;">${{ number_format($totalExpensesMonth, 2) }}</div>
        </div>
    </div>
    <div style="flex:1; min-width:160px; padding:14px 18px; background:rgba(255,152,0,0.07); border:1px solid rgba(255,152,0,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-graph-down-arrow" style="font-size:22px; color:#ff9800;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Gastos del Año</div>
            <div style="font-size:18px; font-weight:700; color:#ff9800;">${{ number_format($totalExpensesYear, 2) }}</div>
        </div>
    </div>
</div>

{{-- ── Main Table Card ──────────────────────────────────── --}}
<div class="client-table-card">

    {{-- Filter Bar --}}
    <div class="filter-bar">
        <form action="{{ route('expenses.index') }}" method="GET" class="search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="search-input"
                placeholder="Buscar por descripción, categoría..."
                value="{{ $search }}" autocomplete="off">
        </form>
        <button class="btn-primary-action" id="btnOpenCreateExpense">
            <i class="bi bi-plus-lg"></i>
            <span>Registrar Gasto</span>
        </button>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        @if($expenses->count() > 0)
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Categoría</th>
                        <th>Cuenta Origen</th>
                        <th style="text-align:right;">Monto</th>
                        <th style="width:100px; text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenses as $expense)
                        <tr>
                            <td style="color:var(--silver-light); font-size:13px; white-space:nowrap;">
                                {{ \Carbon\Carbon::parse($expense->expense_date)->format('d/m/Y') }}
                            </td>
                            <td>
                                <div style="font-weight:600; color:var(--white);">{{ $expense->description }}</div>
                                @if($expense->reference)
                                    <div style="font-size:11px; color:rgba(255,255,255,0.3);">Ref: {{ $expense->reference }}</div>
                                @endif
                            </td>
                            <td>
                                <span style="font-size:12px; color:var(--silver); background:rgba(255,255,255,0.05); padding:2px 8px; border-radius:4px;">
                                    {{ $expense->category ?: 'General' }}
                                </span>
                            </td>
                            <td>
                                <div style="font-size:13px; color:var(--salmon); font-weight:600;">
                                    <i class="bi bi-bank"></i> {{ $expense->bankAccount->name }}
                                </div>
                            </td>
                            <td style="text-align:right; font-weight:700; color:#ef5350; font-size:15px;">
                                ${{ number_format($expense->amount, 2) }}
                            </td>
                            <td style="text-align:center;">
                                <div class="actions-cell" style="justify-content:center;">
                                    <button type="button" class="btn-action edit" title="Editar gasto"
                                        onclick="openEditExpenseModal({{ json_encode($expense) }})">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button type="button" class="btn-action delete" title="Eliminar gasto"
                                        onclick="openDeleteExpenseModal({{ $expense->id }}, '{{ addslashes($expense->description) }}', '{{ number_format($expense->amount, 2) }}')">
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
                <div class="empty-state-icon"><i class="bi bi-cart-x"></i></div>
                <h3 class="empty-state-title">Sin gastos registrados</h3>
                <p class="empty-state-desc">Registra tu primer gasto haciendo clic en el botón superior.</p>
            </div>
        @endif
    </div>

    @if($expenses->count() > 0)
        <div class="pagination-wrapper">
            <div>Mostrando {{ $expenses->firstItem() }} al {{ $expenses->lastItem() }} de {{ $expenses->total() }} gastos</div>
            <div>{{ $expenses->appends(['search' => $search])->links('vendor.pagination.mik') }}</div>
        </div>
    @endif

</div>

{{-- ==============================================================
     MODAL — REGISTRAR GASTO
     ============================================================== --}}
<div class="modal" id="createExpenseModal">
    <div class="modal-backdrop" id="createExpenseBackdrop"></div>
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="bi bi-cart-plus" style="color:var(--salmon); margin-right:8px;"></i>Nuevo Gasto
            </h3>
            <button class="modal-close" id="btnCloseCreateExpense">&times;</button>
        </div>
        <form action="{{ route('expenses.store') }}" method="POST" autocomplete="off">
            @csrf

            {{-- Descripción --}}
            <div class="form-group">
                <label for="ex_description" class="form-label">Descripción del Gasto / Compra *</label>
                <input type="text" name="description" id="ex_description" class="form-input"
                    placeholder="Ej. Compra de periféricos, Pago de internet..." value="{{ old('description') }}" required>
            </div>

            {{-- Categoría + Monto --}}
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="ex_category" class="form-label">Categoría</label>
                    <input type="text" name="category" id="ex_category" class="form-input"
                        placeholder="Ej. Compras, Servicios..." value="{{ old('category') }}">
                </div>
                <div class="form-group">
                    <label for="ex_amount" class="form-label">Monto ($) *</label>
                    <input type="number" name="amount" id="ex_amount" class="form-input"
                        placeholder="0.00" step="0.01" min="0.01" value="{{ old('amount') }}" required>
                </div>
            </div>

            {{-- Cuenta Bancaria --}}
            <div class="form-group">
                <label for="ex_bank_account_id" class="form-label">Cuenta Bancaria de Origen *</label>
                <select name="bank_account_id" id="ex_bank_account_id" class="form-input" required>
                    <option value="">-- Selecciona una cuenta --</option>
                    @foreach($bankAccounts as $account)
                        <option value="{{ $account->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>
                            {{ $account->name }} (${{ number_format($account->current_balance, 2) }})
                        </option>
                    @endforeach
                </select>
                <p style="font-size:10px; color:rgba(255,255,255,0.35); margin-top:4px;">Se descontará automáticamente del saldo de la cuenta elegida.</p>
            </div>

            {{-- Fecha + Referencia --}}
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="ex_expense_date" class="form-label">Fecha *</label>
                    <input type="date" name="expense_date" id="ex_expense_date" class="form-input"
                        value="{{ old('expense_date', date('Y-m-d')) }}" required>
                </div>
                <div class="form-group">
                    <label for="ex_reference" class="form-label">Referencia</label>
                    <input type="text" name="reference" id="ex_reference" class="form-input"
                        placeholder="Ej. Factura #123" value="{{ old('reference') }}">
                </div>
            </div>

            {{-- Notas --}}
            <div class="form-group">
                <label for="ex_notes" class="form-label">Notas <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span></label>
                <textarea name="notes" id="ex_notes" rows="3" class="form-input"
                    placeholder="Observaciones adicionales...">{{ old('notes') }}</textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelCreateExpense">Cancelar</button>
                <button type="submit" class="btn-primary-action">
                    <i class="bi bi-check-circle"></i> Registrar Gasto
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==============================================================
     MODAL — EDITAR GASTO
     ============================================================== --}}
<div class="modal" id="editExpenseModal">
    <div class="modal-backdrop" id="editExpenseBackdrop"></div>
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="bi bi-pencil-square" style="color:#42a5f5; margin-right:8px;"></i>Editar Gasto
            </h3>
            <button class="modal-close" id="btnCloseEditExpense">&times;</button>
        </div>
        <form id="editExpenseForm" method="POST" autocomplete="off">
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
                <label for="ed_bank_account_id" class="form-label">Cuenta Bancaria de Origen *</label>
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
                    <label for="ed_expense_date" class="form-label">Fecha *</label>
                    <input type="date" name="expense_date" id="ed_expense_date" class="form-input" required>
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
                <button type="button" class="btn-secondary" id="btnCancelEditExpense">Cancelar</button>
                <button type="submit" class="btn-primary-action">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

{{-- ==============================================================
     MODAL — ELIMINAR GASTO
     ============================================================== --}}
<div class="modal" id="deleteExpenseModal">
    <div class="modal-backdrop" id="deleteExpenseBackdrop"></div>
    <div class="modal-content" style="max-width:400px; text-align:center;">
        <div style="font-size:48px; color:#ff5252; margin-bottom:15px;">
            <i class="bi bi-exclamation-circle"></i>
        </div>
        <h3 class="modal-title" style="margin-bottom:10px; display:inline-block;">¿Eliminar Gasto?</h3>
        <p style="color:var(--silver-light); font-size:14px; line-height:1.6; margin-bottom:25px;">
            Se eliminará el gasto <strong id="deleteExpenseDesc" style="color:var(--white);"></strong>
            por <strong id="deleteExpenseAmount" style="color:#ef5350;"></strong>.
            Si se descontó de una cuenta, el saldo será revertido.
        </p>
        <form id="deleteExpenseForm" method="POST">
            @csrf
            @method('DELETE')
            <div style="display:flex; justify-content:center; gap:12px;">
                <button type="button" class="btn-secondary" id="btnCancelDeleteExpense" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn-danger-action" style="flex:1;">Eliminar</button>
            </div>
        </form>
    </div>
</div>

{{-- ── JavaScript ────────────────────────────────────────── --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Create Modal ──────────────────────────────────────
    const createModal = document.getElementById('createExpenseModal');
    const openCreate  = () => createModal.classList.add('open');
    const closeCreate = () => createModal.classList.remove('open');

    document.getElementById('btnOpenCreateExpense').addEventListener('click', openCreate);
    document.getElementById('btnCloseCreateExpense').addEventListener('click', closeCreate);
    document.getElementById('btnCancelCreateExpense').addEventListener('click', closeCreate);
    document.getElementById('createExpenseBackdrop').addEventListener('click', closeCreate);

    // ── Edit Modal ────────────────────────────────────────
    const editModal = document.getElementById('editExpenseModal');
    const closeEdit = () => editModal.classList.remove('open');

    document.getElementById('btnCloseEditExpense').addEventListener('click', closeEdit);
    document.getElementById('btnCancelEditExpense').addEventListener('click', closeEdit);
    document.getElementById('editExpenseBackdrop').addEventListener('click', closeEdit);

    // ── Delete Modal ──────────────────────────────────────
    const deleteModal = document.getElementById('deleteExpenseModal');
    const closeDelete = () => deleteModal.classList.remove('open');

    document.getElementById('btnCancelDeleteExpense').addEventListener('click', closeDelete);
    document.getElementById('deleteExpenseBackdrop').addEventListener('click', closeDelete);

    // Reopen on error
    @if($errors->any())
        openCreate();
    @endif
});

function openEditExpenseModal(expense) {
    document.getElementById('editExpenseForm').action = `/expenses/${expense.id}`;
    document.getElementById('ed_description').value   = expense.description;
    document.getElementById('ed_category').value      = expense.category || '';
    document.getElementById('ed_amount').value        = expense.amount;
    document.getElementById('ed_bank_account_id').value = expense.bank_account_id || '';
    document.getElementById('ed_expense_date').value  = expense.expense_date;
    document.getElementById('ed_reference').value     = expense.reference || '';
    document.getElementById('ed_notes').value         = expense.notes || '';
    document.getElementById('editExpenseModal').classList.add('open');
}

function openDeleteExpenseModal(id, desc, amount) {
    document.getElementById('deleteExpenseForm').action = `/expenses/${id}`;
    document.getElementById('deleteExpenseDesc').textContent = desc;
    document.getElementById('deleteExpenseAmount').textContent = '$' + amount;
    document.getElementById('deleteExpenseModal').classList.add('open');
}
</script>

@endsection
