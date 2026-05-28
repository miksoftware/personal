@extends('layouts.app')

@section('title', 'Préstamos - MIK Software Control')
@section('page_title', 'Préstamos')
@section('page_subtitle', 'Gestión de préstamos de dinero o artículos con clientes.')

@section('content')

{{-- ── Status / Error Alerts ────────────────────────────── --}}
@if(session('status'))
    <div class="alert-banner-success" id="loan-status-alert" style="margin-bottom:25px;">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('status') }}</span>
    </div>
    <script>
        setTimeout(() => {
            const el = document.getElementById('loan-status-alert');
            if (el) el.style.display = 'none';
        }, 7000);
    </script>
@endif

@if($errors->any())
    <div class="alert-banner" id="loan-error-alert" style="margin-bottom:25px;">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

{{-- ── Summary Cards ────────────────────────────────────── --}}
@php
    $pendingLoans = \App\Models\Loan::where('status', 'pendiente')->get();
    $iLent = $pendingLoans->where('type', 'entregado')->sum('amount');
    $theyLent = $pendingLoans->where('type', 'recibido')->sum('amount');
@endphp
<div style="display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
    <div style="flex:1; min-width:160px; padding:14px 18px; background:rgba(66,165,245,0.07); border:1px solid rgba(66,165,245,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-arrow-up-right-circle" style="font-size:22px; color:#42a5f5;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Yo presté (Pendiente)</div>
            <div style="font-size:18px; font-weight:700; color:#42a5f5;">${{ number_format($iLent, 2) }}</div>
        </div>
    </div>
    <div style="flex:1; min-width:160px; padding:14px 18px; background:rgba(255,152,0,0.07); border:1px solid rgba(255,152,0,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-arrow-down-left-circle" style="font-size:22px; color:#ff9800;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Me prestaron (Pendiente)</div>
            <div style="font-size:18px; font-weight:700; color:#ff9800;">${{ number_format($theyLent, 2) }}</div>
        </div>
    </div>
</div>

{{-- ── Main Table Card ──────────────────────────────────── --}}
<div class="client-table-card">

    {{-- Filter Bar --}}
    <div class="filter-bar">
        <form action="{{ route('loans.index') }}" method="GET" class="search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="search-input"
                placeholder="Buscar por descripción o cliente..."
                value="{{ $search }}" autocomplete="off">
        </form>
        <button class="btn-primary-action" id="btnOpenCreateLoan">
            <i class="bi bi-plus-lg"></i>
            <span>Nuevo Préstamo</span>
        </button>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        @if($loans->count() > 0)
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th style="width:100px; text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loans as $loan)
                        <tr>
                            <td style="color:var(--silver-light); font-size:13px; white-space:nowrap;">
                                {{ \Carbon\Carbon::parse($loan->loan_date)->format('d/m/Y') }}
                            </td>
                            <td style="font-weight:600;">{{ $loan->client->name }}</td>
                            <td>
                                <span style="font-size:12px; color:{{ $loan->type === 'recibido' ? '#ff9800' : '#42a5f5' }};">
                                    <i class="bi bi-{{ $loan->type === 'recibido' ? 'arrow-down-left' : 'arrow-up-right' }}"></i>
                                    {{ $loan->type_label }}
                                </span>
                            </td>
                            <td>
                                <span style="color:var(--silver-light); font-size:13px;">{{ $loan->description }}</span>
                            </td>
                            <td style="font-weight:600; white-space:nowrap;">
                                ${{ number_format($loan->amount, 2) }}
                            </td>
                            <td>
                                <span class="badge-status {{ $loan->status === 'pendiente' ? 'pendiente' : ($loan->status === 'devuelto' ? 'activa' : 'suspendida') }}">
                                    {{ $loan->status_label }}
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <div class="actions-cell" style="justify-content:center;">
                                    <button type="button" class="btn-action edit" title="Editar préstamo"
                                        onclick="openEditLoanModal({{ json_encode($loan) }})">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button type="button" class="btn-action delete" title="Eliminar préstamo"
                                        onclick="openDeleteLoanModal({{ $loan->id }}, '{{ addslashes($loan->description) }}')">
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
                <div class="empty-state-icon"><i class="bi bi-hand-thumbs-up"></i></div>
                <h3 class="empty-state-title">Sin préstamos registrados</h3>
                <p class="empty-state-desc">Registra tu primer préstamo haciendo clic en el botón superior.</p>
            </div>
        @endif
    </div>

    @if($loans->count() > 0)
        <div class="pagination-wrapper">
            <div>Mostrando {{ $loans->firstItem() }} al {{ $loans->lastItem() }} de {{ $loans->total() }} registros</div>
            <div>{{ $loans->appends(['search' => $search, 'filter' => $filter])->links('vendor.pagination.mik') }}</div>
        </div>
    @endif

</div>

{{-- ==============================================================
     MODAL — CREAR PRÉSTAMO
     ============================================================== --}}
<div class="modal" id="createLoanModal">
    <div class="modal-backdrop" id="createLoanBackdrop"></div>
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="bi bi-plus-circle" style="color:var(--salmon); margin-right:8px;"></i>Nuevo Préstamo
            </h3>
            <button class="modal-close" id="btnCloseCreateLoan">&times;</button>
        </div>
        <form action="{{ route('loans.store') }}" method="POST" autocomplete="off">
            @csrf

            <div class="form-group">
                <label for="cr_client_id" class="form-label">Cliente *</label>
                <select name="client_id" id="cr_client_id" class="form-input" required>
                    <option value="">-- Seleccionar cliente --</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="cr_type" class="form-label">Tipo de Movimiento *</label>
                <select name="type" id="cr_type" class="form-input" required>
                    <option value="entregado">Yo presté (Salida)</option>
                    <option value="recibido">Me prestaron (Entrada)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="cr_description" class="form-label">Descripción / Artículo *</label>
                <input type="text" name="description" id="cr_description" class="form-input"
                    placeholder="Ej. $50.000 efectivo, Taladro Bosch, etc." required>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="cr_amount" class="form-label">Valor Estimado ($)</label>
                    <input type="number" name="amount" id="cr_amount" class="form-input"
                        placeholder="0.00" step="0.01" min="0" value="0">
                </div>
                <div class="form-group">
                    <label for="cr_loan_date" class="form-label">Fecha *</label>
                    <input type="date" name="loan_date" id="cr_loan_date" class="form-input"
                        value="{{ date('Y-m-d') }}" required>
                </div>
            </div>

            <div class="form-group">
                <label for="cr_notes" class="form-label">Notas</label>
                <textarea name="notes" id="cr_notes" rows="3" placeholder="Observaciones adicionales..."></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelCreateLoan">Cancelar</button>
                <button type="submit" class="btn-primary-action">
                    <i class="bi bi-check-circle"></i> Registrar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==============================================================
     MODAL — EDITAR PRÉSTAMO
     ============================================================== --}}
<div class="modal" id="editLoanModal">
    <div class="modal-backdrop" id="editLoanBackdrop"></div>
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="bi bi-pencil-square" style="color:#42a5f5; margin-right:8px;"></i>Editar Préstamo
            </h3>
            <button class="modal-close" id="btnCloseEditLoan">&times;</button>
        </div>
        <form id="editLoanForm" method="POST" autocomplete="off">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="ed_client_id" class="form-label">Cliente *</label>
                <select name="client_id" id="ed_client_id" class="form-input" required>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="ed_type" class="form-label">Tipo *</label>
                <select name="type" id="ed_type" class="form-input" required>
                    <option value="entregado">Yo presté</option>
                    <option value="recibido">Me prestaron</option>
                </select>
            </div>

            <div class="form-group">
                <label for="ed_description" class="form-label">Descripción *</label>
                <input type="text" name="description" id="ed_description" class="form-input" required>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="ed_amount" class="form-label">Valor ($)</label>
                    <input type="number" name="amount" id="ed_amount" class="form-input" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label for="ed_loan_date" class="form-label">Fecha *</label>
                    <input type="date" name="loan_date" id="ed_loan_date" class="form-input" required>
                </div>
            </div>

            <div class="form-group">
                <label for="ed_status" class="form-label">Estado *</label>
                <select name="status" id="ed_status" class="form-input" required>
                    <option value="pendiente">Pendiente</option>
                    <option value="devuelto">Devuelto</option>
                    <option value="canjeado">Canjeado</option>
                </select>
            </div>

            <div class="form-group">
                <label for="ed_notes" class="form-label">Notas</label>
                <textarea name="notes" id="ed_notes" rows="3"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelEditLoan">Cancelar</button>
                <button type="submit" class="btn-primary-action">
                    <i class="bi bi-check-circle"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==============================================================
     MODAL — ELIMINAR PRÉSTAMO
     ============================================================== --}}
<div class="modal" id="deleteLoanModal">
    <div class="modal-backdrop" id="deleteLoanBackdrop"></div>
    <div class="modal-content" style="max-width:400px; text-align:center;">
        <div style="font-size:48px; color:#ff5252; margin-bottom:15px;">
            <i class="bi bi-exclamation-circle"></i>
        </div>
        <h3 class="modal-title">¿Eliminar registro?</h3>
        <p style="color:var(--silver-light); font-size:14px; margin-bottom:25px;">
            Se eliminará el registro: <strong id="deleteLoanDesc" style="color:var(--white);"></strong>.
        </p>
        <form id="deleteLoanForm" method="POST">
            @csrf
            @method('DELETE')
            <div style="display:flex; gap:12px;">
                <button type="button" class="btn-secondary" id="btnCancelDeleteLoan" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn-danger-action" style="flex:1;">Eliminar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Create Modal
    const modalCreate = document.getElementById('createLoanModal');
    const openCreate = () => modalCreate.classList.add('open');
    const closeCreate = () => modalCreate.classList.remove('open');
    document.getElementById('btnOpenCreateLoan').addEventListener('click', openCreate);
    document.getElementById('btnCloseCreateLoan').addEventListener('click', closeCreate);
    document.getElementById('btnCancelCreateLoan').addEventListener('click', closeCreate);
    document.getElementById('createLoanBackdrop').addEventListener('click', closeCreate);

    // Edit Modal
    const modalEdit = document.getElementById('editLoanModal');
    const closeEdit = () => modalEdit.classList.remove('open');
    document.getElementById('btnCloseEditLoan').addEventListener('click', closeEdit);
    document.getElementById('btnCancelEditLoan').addEventListener('click', closeEdit);
    document.getElementById('editLoanBackdrop').addEventListener('click', closeEdit);

    // Delete Modal
    const modalDelete = document.getElementById('deleteLoanModal');
    const closeDelete = () => modalDelete.classList.remove('open');
    document.getElementById('btnCancelDeleteLoan').addEventListener('click', closeDelete);
    document.getElementById('deleteLoanBackdrop').addEventListener('click', closeDelete);

    window.openEditLoanModal = function(loan) {
        const form = document.getElementById('editLoanForm');
        form.action = `/loans/${loan.id}`;
        document.getElementById('ed_client_id').value = loan.client_id;
        document.getElementById('ed_type').value = loan.type;
        document.getElementById('ed_description').value = loan.description;
        document.getElementById('ed_amount').value = loan.amount;
        document.getElementById('ed_loan_date').value = loan.loan_date;
        document.getElementById('ed_status').value = loan.status;
        document.getElementById('ed_notes').value = loan.notes || '';
        modalEdit.classList.add('open');
    };

    window.openDeleteLoanModal = function(id, desc) {
        document.getElementById('deleteLoanForm').action = `/loans/${id}`;
        document.getElementById('deleteLoanDesc').textContent = desc;
        modalDelete.classList.add('open');
    };
});
</script>

@endsection
