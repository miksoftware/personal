@extends('layouts.app')

@section('title', 'Licencias - MIK Software Control')
@section('page_title', 'Licencias')
@section('page_subtitle', 'Administra las licencias de software de tus clientes.')

@section('content')

{{-- ── Status / Error Alerts ────────────────────────────────────── --}}
@if(session('status'))
    <div class="alert-banner-success" id="license-status-alert" style="margin-bottom: 25px;">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('status') }}</span>
    </div>
    <script>
        setTimeout(() => {
            const el = document.getElementById('license-status-alert');
            if (el) el.style.display = 'none';
        }, 7000);
    </script>
@endif

@if($errors->any())
    <div class="alert-banner" id="license-error-alert" style="margin-bottom: 25px;">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

{{-- ── Main Table Card ──────────────────────────────────────────── --}}
<div class="client-table-card licenses-view">

    {{-- Filter Bar --}}
    <div class="filter-bar">
        <form action="{{ route('licenses.index') }}" method="GET" class="search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input
                type="text"
                name="search"
                class="search-input"
                placeholder="Buscar por URL o nombre de cliente..."
                value="{{ $search }}"
                autocomplete="off"
            >
        </form>

        <button class="btn-primary-action" id="btnOpenCreateModal">
            <i class="bi bi-plus-lg"></i>
            <span>Nueva Licencia</span>
        </button>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        @if($licenses->count() > 0)
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>URL del Sitio</th>
                        <th>Token Bloqueo</th>
                        <th>Estado</th>
                        <th>Ciclo</th>
                        <th>Instalación</th>
                        <th>Tarifa Mensual</th>
                        <th>Próx. Facturación</th>
                        <th style="width:90px; text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($licenses as $license)
                        <tr>
                            {{-- Client --}}
                            <td style="font-weight:600;">
                                {{ $license->client->name }}
                            </td>

                            {{-- URL --}}
                            <td>
                                <span class="license-url" title="{{ $license->url }}">{{ $license->url }}</span>
                            </td>

                            {{-- Token --}}
                            <td>
                                <span class="license-token" title="{{ $license->block_token }}">{{ $license->block_token }}</span>
                            </td>

                            {{-- Status Badge --}}
                            <td>
                                <span class="badge-status {{ $license->status }}">
                                    <i class="bi bi-circle-fill" style="font-size:7px;"></i>
                                    {{ $license->status_label }}
                                </span>
                            </td>

                            {{-- Billing Cycle --}}
                            <td>
                                <span class="badge-cycle">{{ $license->billing_cycle_label }}</span>
                            </td>

                            {{-- Setup Fee --}}
                            <td>
                                <span style="font-weight:600; color:var(--salmon);">
                                    ${{ number_format($license->setup_fee, 2) }}
                                </span>
                            </td>

                            {{-- Monthly Fee --}}
                            <td>
                                @if($license->is_free)
                                    <span class="badge-free">
                                        <i class="bi bi-gift-fill"></i>
                                        Gratuita
                                    </span>
                                @else
                                    <span class="license-fee">
                                        ${{ number_format($license->monthly_fee, 2) }}
                                    </span>
                                @endif
                            </td>

                            {{-- Next Billing Date --}}
                            <td style="color: var(--silver-light); font-size:13px;">
                                {{ \Carbon\Carbon::parse($license->next_billing_date)->format('d M Y') }}
                            </td>

                            {{-- Actions --}}
                            <td style="text-align:center;">
                                <div class="actions-cell" style="justify-content:center; gap:8px;">
                                    {{-- Registrar Pago (Icono Billetes) --}}
                                    <button type="button" class="btn-action edit" title="Registrar pago"
                                        style="background:rgba(72,199,142,0.1); color:#48c78e; border:1px solid rgba(72,199,142,0.2);"
                                        onclick="openLicensePaymentSelectorModal({{ json_encode($license) }})">
                                        <i class="bi bi-cash-stack"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn-action edit"
                                        title="Editar licencia"
                                        onclick="openEditLicenseModal(
                                            '{{ $license->id }}',
                                            '{{ $license->client_id }}',
                                            '{{ addslashes($license->url) }}',
                                            '{{ addslashes($license->block_token) }}',
                                            '{{ $license->status }}',
                                            '{{ $license->billing_cycle }}',
                                            '{{ $license->setup_fee }}',
                                            '{{ $license->monthly_fee }}',
                                            '{{ $license->next_billing_date }}',
                                            {{ $license->is_free ? 'true' : 'false' }}
                                        )"
                                    >
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    @if($license->block_token)
                                    <button
                                        type="button"
                                        class="btn-action power"
                                        title="Control Remoto del Sistema"
                                        onclick="openSystemControlModal('{{ $license->id }}', '{{ addslashes($license->url) }}')"
                                    >
                                        <i class="bi bi-power"></i>
                                    </button>
                                    @endif
                                    <button
                                        type="button"
                                        class="btn-action delete"
                                        title="Eliminar Licencia"
                                        onclick="openDeleteLicenseModal('{{ $license->id }}', '{{ addslashes($license->url) }}')"
                                    >
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
                <div class="empty-state-icon">
                    <i class="bi bi-key"></i>
                </div>
                <h3 class="empty-state-title">No hay licencias registradas</h3>
                <p class="empty-state-desc">Comienza registrando la primera licencia de uno de tus clientes haciendo clic en el botón superior.</p>
            </div>
        @endif
    </div>

    {{-- Pagination --}}
    @if($licenses->count() > 0)
        <div class="pagination-wrapper">
            <div>
                Mostrando {{ $licenses->firstItem() }} al {{ $licenses->lastItem() }} de {{ $licenses->total() }} licencias
            </div>
            <div>
                {{ $licenses->appends(['search' => $search])->links('vendor.pagination.mik') }}
            </div>
        </div>
    @endif

</div>

{{-- ============================================================
     1. CREATE LICENSE MODAL
     ============================================================ --}}
<div class="modal" id="createLicenseModal">
    <div class="modal-backdrop" id="createLicenseBackdrop"></div>
    <div class="modal-content" style="max-width: 540px;">
        <div class="modal-header">
            <h3 class="modal-title">Nueva Licencia</h3>
            <button class="modal-close" id="btnCloseCreateLicense">&times;</button>
        </div>

        {{-- Business rule info note --}}
        <div class="modal-note">
            <i class="bi bi-info-circle-fill"></i>
            <span>Recuerda: la licencia N.° 5, 10, 15, 20... de cada cliente se marcará automáticamente como <strong>Gratuita ($0.00)</strong>.</span>
        </div>

        <form action="{{ route('licenses.store') }}" method="POST" autocomplete="off">
            @csrf

            {{-- Client Select --}}
            <div class="form-group">
                <label for="create_client_id" class="form-label">Cliente</label>
                <select name="client_id" id="create_client_id" required>
                    <option value="">— Selecciona un cliente —</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- URL --}}
            <div class="form-group">
                <label for="create_url" class="form-label">URL del Sitio</label>
                <input
                    type="text"
                    name="url"
                    id="create_url"
                    class="form-input"
                    placeholder="Ej. https://cliente.miksoftware.com"
                    value="{{ old('url') }}"
                    required
                >
            </div>

            {{-- Block Token --}}
            <div class="form-group">
                <label for="create_block_token" class="form-label">Token para Bloqueo</label>
                <input
                    type="text"
                    name="block_token"
                    id="create_block_token"
                    class="form-input"
                    placeholder="Ej. ABC123XYZ789"
                    value="{{ old('block_token') }}"
                    required
                >
            </div>

            {{-- Two columns: Status + Billing Cycle --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label for="create_status" class="form-label">Estado</label>
                    <select name="status" id="create_status" required>
                        <option value="activa"      {{ old('status') == 'activa'      ? 'selected' : '' }}>Activa</option>
                        <option value="suspendida"  {{ old('status') == 'suspendida'  ? 'selected' : '' }}>Suspendida</option>
                        <option value="vencida"     {{ old('status') == 'vencida'     ? 'selected' : '' }}>Vencida</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="create_billing_cycle" class="form-label">Ciclo de Facturación</label>
                    <select name="billing_cycle" id="create_billing_cycle" required>
                        <option value="mensual"     {{ old('billing_cycle') == 'mensual'     ? 'selected' : '' }}>Mensual</option>
                        <option value="trimestral"  {{ old('billing_cycle') == 'trimestral'  ? 'selected' : '' }}>Trimestral</option>
                        <option value="semestral"   {{ old('billing_cycle') == 'semestral'   ? 'selected' : '' }}>Semestral</option>
                        <option value="anual"       {{ old('billing_cycle') == 'anual'       ? 'selected' : '' }}>Anual</option>
                    </select>
                </div>
            </div>

            {{-- Three columns: Setup Fee + Monthly Fee + Next Billing Date --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label for="create_setup_fee" class="form-label">Instalación ($)</label>
                    <input
                        type="number"
                        name="setup_fee"
                        id="create_setup_fee"
                        class="form-input"
                        placeholder="0.00"
                        step="0.01"
                        min="0"
                        value="{{ old('setup_fee', 0) }}"
                        required
                    >
                </div>
                <div class="form-group">
                    <label for="create_monthly_fee" class="form-label">Mensualidad ($)</label>
                    <input
                        type="number"
                        name="monthly_fee"
                        id="create_monthly_fee"
                        class="form-input"
                        placeholder="0.00"
                        step="0.01"
                        min="0"
                        value="{{ old('monthly_fee') }}"
                        required
                    >
                </div>
                <div class="form-group">
                    <label for="create_next_billing_date" class="form-label">Próx. Factura</label>
                    <input
                        type="date"
                        name="next_billing_date"
                        id="create_next_billing_date"
                        class="form-input"
                        value="{{ old('next_billing_date') }}"
                        required
                    >
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelCreateLicense">Cancelar</button>
                <button type="submit" class="btn-primary-action">
                    <i class="bi bi-plus-circle"></i>
                    Guardar Licencia
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ============================================================
     2. EDIT LICENSE MODAL
     ============================================================ --}}
<div class="modal" id="editLicenseModal">
    <div class="modal-backdrop" id="editLicenseBackdrop"></div>
    <div class="modal-content" style="max-width: 540px;">
        <div class="modal-header">
            <h3 class="modal-title">Editar Licencia</h3>
            <button class="modal-close" id="btnCloseEditLicense">&times;</button>
        </div>

        {{-- Free license warning (shown dynamically) --}}
        <div class="modal-note" id="editFreeNote" style="display:none;">
            <i class="bi bi-gift-fill"></i>
            <span>Esta es una licencia <strong>Gratuita</strong>. La tarifa se mantendrá en <strong>$0.00</strong> automáticamente.</span>
        </div>

        <form id="editLicenseForm" method="POST" autocomplete="off">
            @csrf
            @method('PUT')

            {{-- Client Select --}}
            <div class="form-group">
                <label for="edit_client_id" class="form-label">Cliente</label>
                <select name="client_id" id="edit_client_id" required>
                    <option value="">— Selecciona un cliente —</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- URL --}}
            <div class="form-group">
                <label for="edit_url" class="form-label">URL del Sitio</label>
                <input type="text" name="url" id="edit_url" class="form-input" placeholder="https://cliente.miksoftware.com" required>
            </div>

            {{-- Block Token --}}
            <div class="form-group">
                <label for="edit_block_token" class="form-label">Token para Bloqueo</label>
                <input type="text" name="block_token" id="edit_block_token" class="form-input" placeholder="ABC123XYZ789" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label for="edit_status" class="form-label">Estado</label>
                    <select name="status" id="edit_status" required>
                        <option value="activa">Activa</option>
                        <option value="suspendida">Suspendida</option>
                        <option value="vencida">Vencida</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_billing_cycle" class="form-label">Ciclo de Facturación</label>
                    <select name="billing_cycle" id="edit_billing_cycle" required>
                        <option value="mensual">Mensual</option>
                        <option value="trimestral">Trimestral</option>
                        <option value="semestral">Semestral</option>
                        <option value="anual">Anual</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label for="edit_setup_fee" class="form-label">Instalación ($)</label>
                    <input type="number" name="setup_fee" id="edit_setup_fee" class="form-input" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="edit_monthly_fee" class="form-label">Mensualidad ($)</label>
                    <input type="number" name="monthly_fee" id="edit_monthly_fee" class="form-input" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="edit_next_billing_date" class="form-label">Próx. Factura</label>
                    <input type="date" name="next_billing_date" id="edit_next_billing_date" class="form-input" required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelEditLicense">Cancelar</button>
                <button type="submit" class="btn-primary-action">
                    <i class="bi bi-check-circle"></i>
                    Actualizar Licencia
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ============================================================
     3. DELETE CONFIRMATION MODAL
     ============================================================ --}}
<div class="modal" id="deleteLicenseModal">
    <div class="modal-backdrop" id="deleteLicenseBackdrop"></div>
    <div class="modal-content" style="max-width:420px; text-align:center;">
        <div style="font-size:50px; color:#ff5252; margin-bottom:15px;">
            <i class="bi bi-exclamation-circle"></i>
        </div>

        <h3 class="modal-title" style="margin-bottom:12px; display:inline-block;">¿Eliminar Licencia?</h3>

        <p style="color:var(--silver-light); font-size:14px; line-height:1.6; margin-bottom:25px;">
            ¿Estás seguro de que deseas eliminar la licencia para
            <strong id="deleteLicenseUrl" style="color:var(--white);"></strong>?
            Esta acción no se puede deshacer.
        </p>

        <form id="deleteLicenseForm" method="POST">
            @csrf
            @method('DELETE')

            <div style="display:flex; justify-content:center; gap:12px;">
                <button type="button" class="btn-secondary" id="btnCancelDeleteLicense" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn-danger-action" style="flex:1;">Eliminar</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal JS Controller ──────────────────────────────────────── --}}
{{-- CSRF meta for JS fetch calls --}}
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- ============================================================
     4. SYSTEM CONTROL MODAL
     ============================================================ --}}
<div class="modal" id="systemControlModal">
    <div class="modal-backdrop" id="systemControlBackdrop"></div>
    <div class="modal-content" style="max-width: 460px;">

        {{-- Header --}}
        <div class="modal-header">
            <h3 class="modal-title" style="display:flex; align-items:center; gap:9px;">
                <i class="bi bi-power" style="color:#4fc3f7; font-size:18px;"></i>
                Control Remoto del Sistema
            </h3>
            <button class="modal-close" id="btnCloseSystemControl">&times;</button>
        </div>

        {{-- URL Tag --}}
        <div style="display:flex; align-items:center; gap:8px; padding:10px 14px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:9px; margin-bottom:4px;">
            <i class="bi bi-link-45deg" style="color:rgba(255,255,255,0.35); font-size:15px; flex-shrink:0;"></i>
            <span id="sysControlUrlText" style="font-size:12px; color:rgba(255,255,255,0.55); word-break:break-all;"></span>
        </div>

        {{-- Status Card --}}
        <div class="sys-status-card loading" id="sysStatusCard">
            <div class="sys-status-icon" id="sysStatusIcon">
                <span class="sys-spinner"></span>
            </div>
            <div>
                <div class="sys-status-label" id="sysStatusLabel">Verificando estado…</div>
                <div class="sys-status-sub" id="sysStatusSub">Conectando con el sistema remoto</div>
            </div>
        </div>

        {{-- Feedback message --}}
        <div class="sys-feedback" id="sysFeedback" style="display:none;"></div>

        {{-- Action Buttons --}}
        <div id="sysActionRow" style="display:none; gap:10px; margin-top:16px;">
            <button class="btn-sys-enable" id="btnSysEnable" onclick="doSystemToggle('enable')">
                <i class="bi bi-play-circle-fill"></i>
                Habilitar Sistema
            </button>
            <button class="btn-sys-disable" id="btnSysDisable" onclick="doSystemToggle('disable')">
                <i class="bi bi-pause-circle-fill"></i>
                Deshabilitar Sistema
            </button>
        </div>

        {{-- Footer row: refresh + close --}}
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:18px; padding-top:14px; border-top:1px solid rgba(255,255,255,0.07);">
            <button class="btn-sys-refresh" id="btnSysRefresh" onclick="_sysSetState('loading'); _sysFetchStatus();" disabled>
                <i class="bi bi-arrow-clockwise"></i>
                Actualizar estado
            </button>
            <button class="btn-secondary" id="btnCancelSystemControl" style="min-width:90px;">Cerrar</button>
        </div>
    </div>
</div>

{{-- ── Modal JS Controller ──────────────────────────────────────── --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── CREATE MODAL ─────────────────────────────────────────────
    const modalCreate    = document.getElementById('createLicenseModal');
    const btnOpenCreate  = document.getElementById('btnOpenCreateModal');
    const btnCloseCreate = document.getElementById('btnCloseCreateLicense');
    const btnCancelCreate= document.getElementById('btnCancelCreateLicense');
    const backdropCreate = document.getElementById('createLicenseBackdrop');

    const openCreate  = () => modalCreate.classList.add('open');
    const closeCreate = () => modalCreate.classList.remove('open');

    if (btnOpenCreate)   btnOpenCreate.addEventListener('click', openCreate);
    if (btnCloseCreate)  btnCloseCreate.addEventListener('click', closeCreate);
    if (btnCancelCreate) btnCancelCreate.addEventListener('click', closeCreate);
    if (backdropCreate)  backdropCreate.addEventListener('click', closeCreate);

    // ── EDIT MODAL ───────────────────────────────────────────────
    const modalEdit    = document.getElementById('editLicenseModal');
    const btnCloseEdit = document.getElementById('btnCloseEditLicense');
    const btnCancelEdit= document.getElementById('btnCancelEditLicense');
    const backdropEdit = document.getElementById('editLicenseBackdrop');

    const closeEdit = () => modalEdit.classList.remove('open');

    if (btnCloseEdit)  btnCloseEdit.addEventListener('click', closeEdit);
    if (btnCancelEdit) btnCancelEdit.addEventListener('click', closeEdit);
    if (backdropEdit)  backdropEdit.addEventListener('click', closeEdit);

    // ── DELETE MODAL ─────────────────────────────────────────────
    const modalDelete    = document.getElementById('deleteLicenseModal');
    const btnCancelDelete= document.getElementById('btnCancelDeleteLicense');
    const backdropDelete = document.getElementById('deleteLicenseBackdrop');

    const closeDelete = () => modalDelete.classList.remove('open');

    if (btnCancelDelete) btnCancelDelete.addEventListener('click', closeDelete);
    if (backdropDelete)  backdropDelete.addEventListener('click', closeDelete);

    // ── SYSTEM CONTROL MODAL ─────────────────────────────────────
    const modalSys         = document.getElementById('systemControlModal');
    const btnCloseSys      = document.getElementById('btnCloseSystemControl');
    const btnCancelSys     = document.getElementById('btnCancelSystemControl');
    const backdropSys      = document.getElementById('systemControlBackdrop');

    const closeSys = () => modalSys.classList.remove('open');

    if (btnCloseSys)  btnCloseSys.addEventListener('click', closeSys);
    if (btnCancelSys) btnCancelSys.addEventListener('click', closeSys);
    if (backdropSys)  backdropSys.addEventListener('click', closeSys);

    // Auto-open create modal if there are validation errors (form was submitted)
    @if($errors->any())
        openCreate();
    @endif
});

// ── Global: open Edit Modal populated with license data ──────────
function openEditLicenseModal(id, clientId, url, token, status, billingCycle, setupFee, monthlyFee, nextBillingDate, isFree) {
    const modal    = document.getElementById('editLicenseModal');
    const form     = document.getElementById('editLicenseForm');
    const freeNote = document.getElementById('editFreeNote');
    const feeInput = document.getElementById('edit_monthly_fee');

    // Populate fields
    document.getElementById('edit_client_id').value        = clientId;
    document.getElementById('edit_url').value               = url;
    document.getElementById('edit_block_token').value       = token;
    document.getElementById('edit_status').value            = status;
    document.getElementById('edit_billing_cycle').value     = billingCycle;
    document.getElementById('edit_setup_fee').value         = setupFee;
    document.getElementById('edit_next_billing_date').value = nextBillingDate;
    feeInput.value                                          = monthlyFee;

    // Show free note & lock fee field if is_free
    if (isFree) {
        freeNote.style.display = 'flex';
        feeInput.value         = '0.00';
        feeInput.readOnly      = true;
        feeInput.style.opacity = '0.5';
    } else {
        freeNote.style.display = 'none';
        feeInput.readOnly      = false;
        feeInput.style.opacity = '1';
    }

    // Set form action
    form.action = `/licenses/${id}`;

    // Open
    modal.classList.add('open');
}

// ── Global: open Delete Modal ─────────────────────────────────────
function openDeleteLicenseModal(id, url) {
    const modal       = document.getElementById('deleteLicenseModal');
    const form        = document.getElementById('deleteLicenseForm');
    const urlSpan     = document.getElementById('deleteLicenseUrl');

    urlSpan.textContent = url;
    form.action         = `/licenses/${id}`;

    modal.classList.add('open');
}

// ── Global: System Control Modal ──────────────────────────────────
let _sysLicenseId  = null;
let _sysBusy       = false;

function openSystemControlModal(licenseId, licenseUrl) {
    _sysLicenseId = licenseId;
    _sysBusy      = false;

    // Populate URL display
    document.getElementById('sysControlUrlText').textContent = licenseUrl;

    // Reset feedback
    const fb = document.getElementById('sysFeedback');
    fb.className = 'sys-feedback';
    fb.style.display = 'none';
    fb.innerHTML = '';

    // Open modal, then fetch status
    document.getElementById('systemControlModal').classList.add('open');
    _sysSetState('loading');
    _sysFetchStatus();
}

function _sysSetState(state) {
    // state: 'loading' | 'enabled' | 'disabled' | 'error'
    const card   = document.getElementById('sysStatusCard');
    const icon   = document.getElementById('sysStatusIcon');
    const label  = document.getElementById('sysStatusLabel');
    const sub    = document.getElementById('sysStatusSub');
    const btnRow = document.getElementById('sysActionRow');
    const btnEn  = document.getElementById('btnSysEnable');
    const btnDis = document.getElementById('btnSysDisable');
    const btnRef = document.getElementById('btnSysRefresh');

    // Reset card class
    card.className = 'sys-status-card ' + state;

    if (state === 'loading') {
        icon.innerHTML  = '<span class="sys-spinner"></span>';
        label.textContent = 'Verificando estado…';
        sub.textContent   = 'Conectando con el sistema remoto';
        btnRow.style.display = 'none';
        btnRef.disabled  = true;
    } else if (state === 'enabled') {
        icon.innerHTML    = '<i class="bi bi-check-circle-fill"></i>';
        label.textContent = 'Sistema Habilitado';
        sub.textContent   = 'El sistema está operando con normalidad';
        btnRow.style.display = 'flex';
        btnEn.disabled  = true;
        btnDis.disabled = false;
        btnRef.disabled = false;
    } else if (state === 'disabled') {
        icon.innerHTML    = '<i class="bi bi-slash-circle-fill"></i>';
        label.textContent = 'Sistema Deshabilitado';
        sub.textContent   = 'El sistema muestra la página de mantenimiento';
        btnRow.style.display = 'flex';
        btnEn.disabled  = false;
        btnDis.disabled = true;
        btnRef.disabled = false;
    } else if (state === 'unauthorized') {
        icon.innerHTML    = '<i class="bi bi-shield-lock-fill"></i>';
        label.textContent = 'Token no autorizado';
        sub.textContent   = 'El token no coincide con el configurado en el sistema remoto';
        btnRow.style.display = 'none';
        btnRef.disabled = false;
    } else {
        icon.innerHTML    = '<i class="bi bi-wifi-off"></i>';
        label.textContent = 'Sin conexión';
        sub.textContent   = 'No se pudo establecer comunicación con el sistema remoto';
        btnRow.style.display = 'none';
        btnRef.disabled = false;
    }
}

function _sysShowFeedback(type, msg) {
    const fb = document.getElementById('sysFeedback');
    fb.className      = 'sys-feedback ' + type;
    fb.style.display  = 'flex';
    const ico = type === 'success' ? 'bi-check-circle-fill' : 'bi-x-circle-fill';
    fb.innerHTML = `<i class="bi ${ico}"></i><span>${msg}</span>`;
}

async function _sysFetchStatus() {
    _sysBusy = true;
    try {
        const res  = await fetch(`/licenses/${_sysLicenseId}/system-status`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        if (data.success && data.status) {
            _sysSetState(data.status === 'enabled' ? 'enabled' : 'disabled');
        } else if (res.status === 401 || res.status === 403) {
            // Remote system responded but rejected the token
            _sysSetState('unauthorized');
        } else if (res.status === 503) {
            // My proxy couldn't reach the remote host
            _sysSetState('error');
            _sysShowFeedback('failure', data.message ?? 'No se pudo conectar con el sistema remoto.');
        } else {
            _sysSetState('error');
            _sysShowFeedback('failure', data.message ?? 'Error inesperado al consultar el estado.');
        }
    } catch (e) {
        _sysSetState('error');
        _sysShowFeedback('failure', 'Error de red al conectar con el servidor.');
    }
    _sysBusy = false;
}

async function doSystemToggle(action) {
    if (_sysBusy) return;
    _sysBusy = true;

    // Disable buttons & show spinner on card
    document.getElementById('btnSysEnable').disabled  = true;
    document.getElementById('btnSysDisable').disabled = true;
    document.getElementById('btnSysRefresh').disabled = true;

    const fb = document.getElementById('sysFeedback');
    fb.style.display = 'none';

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                       ?? document.querySelector('input[name="_token"]')?.value ?? '';

        const res  = await fetch(`/licenses/${_sysLicenseId}/system-toggle`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ action })
        });
        const data = await res.json();

        if (data.success) {
            _sysSetState(data.status === 'enabled' ? 'enabled' : 'disabled');
            _sysShowFeedback('success', data.message ?? 'Operación completada.');
        } else if (res.status === 401 || res.status === 403) {
            _sysSetState('unauthorized');
        } else {
            // Refresh actual state
            await _sysFetchStatus();
            _sysShowFeedback('failure', data.message ?? 'Error al ejecutar la acción.');
        }
    } catch (e) {
        _sysShowFeedback('failure', 'Error de red al ejecutar la acción.');
        document.getElementById('btnSysEnable').disabled  = false;
        document.getElementById('btnSysDisable').disabled = false;
        document.getElementById('btnSysRefresh').disabled = false;
    }
    _sysBusy = false;
}
</script>

@endsection

{{-- ==============================================================
     MODAL — SELECCIONAR TIPO DE PAGO
     ============================================================== --}}
<div class="modal" id="licensePaymentSelectorModal">
    <div class="modal-backdrop" id="paymentSelectorBackdrop"></div>
    <div class="modal-content" style="max-width:400px; text-align:center;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="bi bi-cash-stack" style="color:#48c78e; margin-right:8px;"></i>¿Qué pago registrará?
            </h3>
            <button class="modal-close" id="btnClosePaymentSelector">&times;</button>
        </div>
        <p id="paymentSelectorUrl" style="color:var(--silver); font-size:14px; margin-bottom:20px;"></p>
        
        <div style="display:flex; flex-direction:column; gap:12px; padding:0 20px 20px;">
            <button type="button" id="btnPayMonthly" class="btn-primary-action" style="background:#42a5f5; border-color:#1e88e5; justify-content:center;">
                <i class="bi bi-calendar-check"></i> Pago de Mensualidad
            </button>
            
            <button type="button" id="btnPaySetup" class="btn-primary-action" style="background:#ff9800; border-color:#f57c00; justify-content:center;">
                <i class="bi bi-tools"></i> Pago de Instalación
            </button>
        </div>
    </div>
</div>

{{-- ==============================================================
     MODAL — REGISTRAR PAGO DE LICENCIA (Formulario)
     ============================================================== --}}
<div class="modal" id="registerLicensePaymentModal">
    <div class="modal-backdrop" id="licensePaymentBackdrop"></div>
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="bi bi-receipt" style="color:var(--salmon); margin-right:8px;"></i>Registrar Pago Físico
            </h3>
            <button class="modal-close" id="btnCloseLicensePayment">&times;</button>
        </div>
        <p id="licensePaymentDesc" style="color:var(--silver); font-size:13px; margin: -8px 0 16px; padding: 0 28px;">
            Registrar ingreso de dinero por licencia.
        </p>
        <form action="{{ route('payments.store') }}" method="POST" autocomplete="off">
            @csrf
            <input type="hidden" name="client_id" id="lp_client_id">
            <input type="hidden" name="license_id" id="lp_license_id">
            <input type="hidden" name="license_payment_type" id="lp_type">
            <input type="hidden" name="method" value="transferencia">

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label class="form-label">Monto a Recibir ($)*</label>
                    <input type="number" name="amount" id="lp_amount" class="form-input" step="0.01" required>
                </div>
                @php $bankAccounts = \App\Models\BankAccount::where('is_active', true)->get(); @endphp
                <div class="form-group">
                    <label class="form-label">Cuenta de Destino*</label>
                    <select name="bank_account_id" id="lp_bank_account_id" class="form-input" required>
                        <option value="">Seleccionar cuenta</option>
                        @foreach($bankAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (${{ number_format($acc->current_balance, 0) }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Fecha del Pago*</label>
                <input type="date" name="payment_date" class="form-input" value="{{ date('Y-m-d') }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Referencia / Notas</label>
                <input type="text" name="notes" id="lp_notes" class="form-input" placeholder="Ej. Pago mes de mayo">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelLicensePayment">Cancelar</button>
                <button type="submit" class="btn-primary-action">Confirmar Ingreso</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ... (scripts existentes)

    // ── Register License Payment ────────────────────────────────
    const modalLP = document.getElementById('registerLicensePaymentModal');
    const modalSelector = document.getElementById('licensePaymentSelectorModal');
    
    const closeLP = () => modalLP.classList.remove('open');
    const closeSelector = () => modalSelector.classList.remove('open');

    document.getElementById('btnCloseLicensePayment').addEventListener('click', closeLP);
    document.getElementById('btnCancelLicensePayment').addEventListener('click', closeLP);
    document.getElementById('licensePaymentBackdrop').addEventListener('click', closeLP);

    document.getElementById('btnClosePaymentSelector').addEventListener('click', closeSelector);
    document.getElementById('paymentSelectorBackdrop').addEventListener('click', closeSelector);

    let currentLicenseForPayment = null;

    window.openLicensePaymentSelectorModal = function(license) {
        currentLicenseForPayment = license;
        document.getElementById('paymentSelectorUrl').textContent = license.url;
        
        // Mostrar/Ocultar botón de instalación según si tiene valor
        const btnSetup = document.getElementById('btnPaySetup');
        if (parseFloat(license.setup_fee) > 0) {
            btnSetup.style.display = 'flex';
        } else {
            btnSetup.style.display = 'none';
        }
        
        modalSelector.classList.add('open');
    };

    document.getElementById('btnPayMonthly').addEventListener('click', function() {
        closeSelector();
        openRegisterLicensePaymentForm(currentLicenseForPayment, 'mensualidad');
    });

    document.getElementById('btnPaySetup').addEventListener('click', function() {
        closeSelector();
        openRegisterLicensePaymentForm(currentLicenseForPayment, 'instalacion');
    });

    function openRegisterLicensePaymentForm(license, type) {
        const amount = type === 'instalacion' ? license.setup_fee : license.monthly_fee;
        const typeLabel = type === 'instalacion' ? 'Instalación' : 'Mensualidad';
        
        document.getElementById('lp_client_id').value = license.client_id;
        document.getElementById('lp_license_id').value = license.id;
        document.getElementById('lp_type').value = type;
        document.getElementById('lp_amount').value = amount;
        document.getElementById('lp_notes').value = `Pago ${typeLabel} - ${license.url}`;
        document.getElementById('licensePaymentDesc').textContent = `Registrar pago de ${typeLabel} para ${license.url}`;
        
        modalLP.classList.add('open');
    }
});
</script>
