@extends('layouts.app')

@section('title', 'Cuentas Bancarias - MIK Software Control')
@section('page_title', 'Cuentas Bancarias')
@section('page_subtitle', 'Gestión de tus cuentas y saldos actuales.')

@section('content')

{{-- ── Status / Error Alerts ────────────────────────────── --}}
@if(session('status'))
    <div class="alert-banner-success" id="bank-status-alert" style="margin-bottom:25px;">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('status') }}</span>
    </div>
@endif

@if($errors->any())
    <div class="alert-banner" id="bank-error-alert" style="margin-bottom:25px;">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

{{-- ── Summary Cards ────────────────────────────────────── --}}
<div style="display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
    <div style="flex:1; min-width:200px; padding:18px; background:rgba(72,199,142,0.07); border:1px solid rgba(72,199,142,0.2); border-radius:12px; display:flex; align-items:center; gap:15px;">
        <div style="width:40px; height:40px; border-radius:10px; background:#48c78e; display:flex; align-items:center; justify-content:center; color:white; font-size:20px;">
            <i class="bi bi-wallet2"></i>
        </div>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Saldo Total Global</div>
            <div style="font-size:22px; font-weight:700; color:#48c78e;">${{ number_format($accounts->sum('current_balance'), 2) }}</div>
        </div>
    </div>
</div>

{{-- ── Main Table Card ──────────────────────────────────── --}}
<div class="client-table-card">
    <div class="filter-bar">
        <h3 style="margin:0; font-size:16px; color:var(--white);">Mis Cuentas</h3>
        <button class="btn-primary-action" id="btnOpenCreateAccount">
            <i class="bi bi-plus-lg"></i>
            <span>Nueva Cuenta</span>
        </button>
    </div>

    <div class="table-responsive">
        @if($accounts->count() > 0)
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Número de Cuenta</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Saldo Actual</th>
                        <th style="width:100px; text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accounts as $account)
                        <tr>
                            <td style="font-weight:600; font-size:15px; color:var(--white);">
                                {{ $account->name }}
                            </td>
                            <td style="color:var(--silver-light);">
                                {{ $account->account_number ?: '—' }}
                            </td>
                            <td>
                                <span class="badge-status {{ $account->is_active ? 'activa' : 'suspendida' }}">
                                    {{ $account->is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td style="text-align:right; font-weight:700; font-size:16px; color:#48c78e;">
                                ${{ number_format($account->current_balance, 2) }}
                            </td>
                            <td style="text-align:center;">
                                <div class="actions-cell" style="justify-content:center;">
                                    <button type="button" class="btn-action edit" title="Editar cuenta"
                                        onclick="openEditAccountModal({{ json_encode($account) }})">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button type="button" class="btn-action delete" title="Eliminar cuenta"
                                        onclick="openDeleteAccountModal({{ $account->id }}, '{{ addslashes($account->name) }}')">
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
                <div class="empty-state-icon"><i class="bi bi-bank"></i></div>
                <h3 class="empty-state-title">No tienes cuentas registradas</h3>
                <p class="empty-state-desc">Registra tus cuentas para empezar a gestionar tus saldos.</p>
            </div>
        @endif
    </div>
</div>

{{-- MODAL CREAR --}}
<div class="modal" id="createAccountModal">
    <div class="modal-backdrop" id="createAccountBackdrop"></div>
    <div class="modal-content" style="max-width:450px;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-plus-circle" style="color:var(--salmon);"></i> Nueva Cuenta</h3>
            <button class="modal-close" id="btnCloseCreateAccount">&times;</button>
        </div>
        <form action="{{ route('bank-accounts.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Nombre de la Cuenta (Nequi, Bancolombia...)*</label>
                <input type="text" name="name" class="form-input" placeholder="Ej. Nequi Principal" required>
            </div>
            <div class="form-group">
                <label class="form-label">Saldo Inicial ($)*</label>
                <input type="number" name="current_balance" class="form-input" step="0.01" min="0" value="0" required>
            </div>
            <div class="form-group">
                <label class="form-label">Número de Cuenta (Opcional)</label>
                <input type="text" name="account_number" class="form-input" placeholder="Ej. 312...">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelCreateAccount">Cancelar</button>
                <button type="submit" class="btn-primary-action">Crear Cuenta</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDITAR --}}
<div class="modal" id="editAccountModal">
    <div class="modal-backdrop" id="editAccountBackdrop"></div>
    <div class="modal-content" style="max-width:450px;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-pencil-square" style="color:#42a5f5;"></i> Editar Cuenta</h3>
            <button class="modal-close" id="btnCloseEditAccount">&times;</button>
        </div>
        <form id="editAccountForm" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label class="form-label">Nombre*</label>
                <input type="text" name="name" id="ed_name" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Saldo Actual ($)*</label>
                <input type="number" name="current_balance" id="ed_balance" class="form-input" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label">Número de Cuenta</label>
                <input type="text" name="account_number" id="ed_number" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Estado</label>
                <select name="is_active" id="ed_active" class="form-input">
                    <option value="1">Activa</option>
                    <option value="0">Inactiva</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelEditAccount">Cancelar</button>
                <button type="submit" class="btn-primary-action">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL ELIMINAR --}}
<div class="modal" id="deleteAccountModal">
    <div class="modal-backdrop" id="deleteAccountBackdrop"></div>
    <div class="modal-content" style="max-width:400px; text-align:center;">
        <div style="font-size:48px; color:#ff5252; margin-bottom:15px;"><i class="bi bi-exclamation-circle"></i></div>
        <h3>¿Eliminar cuenta?</h3>
        <p style="color:var(--silver-light); margin-bottom:25px;">Se eliminará la cuenta: <strong id="deleteAccountName" style="color:white;"></strong></p>
        <form id="deleteAccountForm" method="POST">
            @csrf
            @method('DELETE')
            <div style="display:flex; gap:12px;">
                <button type="button" class="btn-secondary" id="btnCancelDeleteAccount" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn-danger-action" style="flex:1;">Eliminar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalCreate = document.getElementById('createAccountModal');
    const openCreate = () => modalCreate.classList.add('open');
    const closeCreate = () => modalCreate.classList.remove('open');
    document.getElementById('btnOpenCreateAccount').addEventListener('click', openCreate);
    document.getElementById('btnCloseCreateAccount').addEventListener('click', closeCreate);
    document.getElementById('btnCancelCreateAccount').addEventListener('click', closeCreate);

    const modalEdit = document.getElementById('editAccountModal');
    const closeEdit = () => modalEdit.classList.remove('open');
    document.getElementById('btnCloseEditAccount').addEventListener('click', closeEdit);
    document.getElementById('btnCancelEditAccount').addEventListener('click', closeEdit);

    const modalDelete = document.getElementById('deleteAccountModal');
    const closeDelete = () => modalDelete.classList.remove('open');
    document.getElementById('btnCancelDeleteAccount').addEventListener('click', closeDelete);

        window.openEditAccountModal = function(account) {
        document.getElementById('editAccountForm').action = `/bank-accounts/${account.id}`;
        document.getElementById('ed_name').value = account.name;
        document.getElementById('ed_balance').value = account.current_balance;
        document.getElementById('ed_number').value = account.account_number || '';
        document.getElementById('ed_active').value = account.is_active ? "1" : "0";
        modalEdit.classList.add('open');
    };

    window.openDeleteAccountModal = function(id, name) {
        document.getElementById('deleteAccountForm').action = `/bank-accounts/${id}`;
        document.getElementById('deleteAccountName').textContent = name;
        modalDelete.classList.add('open');
    };
});
</script>

@endsection
