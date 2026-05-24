@extends('layouts.app')

@section('title', 'Clientes - MIK Software Control')

@section('page_title', 'Clientes')
@section('page_subtitle', 'Administra el listado de tus clientes personas y empresas.')

@section('content')

<!-- Alert Banners for Validation Status -->
@if(session('status'))
    <div class="alert-banner-success" id="crud-status-alert" style="margin-bottom: 25px;">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('status') }}</span>
    </div>
    <script>
        setTimeout(() => {
            const alertEl = document.getElementById('crud-status-alert');
            if (alertEl) alertEl.style.display = 'none';
        }, 5000);
    </script>
@endif

@if($errors->any())
    <div class="alert-banner" id="crud-error-alert" style="margin-bottom: 25px;">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<!-- Main Card for List -->
<div class="client-table-card clients-view">
    
    <!-- Filter bar (Search and Create Action) -->
    <div class="filter-bar">
        <form action="{{ route('clients.index') }}" method="GET" class="search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input 
                type="text" 
                name="search" 
                class="search-input" 
                placeholder="Buscar por nombre o teléfono..." 
                value="{{ $search }}"
                autocomplete="off"
            >
        </form>

        <button class="btn-primary-action" id="btnOpenCreateModal">
            <i class="bi bi-plus-lg"></i>
            <span>Nuevo Cliente</span>
        </button>
    </div>

    <!-- Table Responsive Wrap -->
    <div class="table-responsive">
        @if($clients->count() > 0)
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Nombre Completo / Razón Social</th>
                        <th>Modelo</th>
                        <th>Teléfono</th>
                        <th style="width: 100px; text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clients as $client)
                        <tr>
                            <!-- Type Badge -->
                            <td>
                                <span class="badge-type {{ $client->type }}">
                                    {{ $client->type_label }}
                                </span>
                            </td>
                            <!-- Client Name -->
                            <td style="font-weight: 600;">
                                {{ $client->name }}
                            </td>
                            <!-- Model Badge -->
                            <td>
                                <span class="badge-model {{ str_replace('_', '-', $client->model) }}">
                                    {{ $client->model_label }}
                                </span>
                            </td>
                            <!-- Phone -->
                            <td>
                                @if($client->phone)
                                    <span class="phone-number">{{ $client->phone }}</span>
                                @else
                                    <span class="empty-phone">Sin registrar</span>
                                @endif
                            </td>
                            <!-- Action triggers -->
                            <td style="text-align: center;">
                                <div class="actions-cell" style="justify-content: center;">
                                    <button 
                                        type="button" 
                                        class="btn-action edit" 
                                        title="Editar Cliente"
                                        onclick="openEditModal('{{ $client->id }}', '{{ $client->type }}', '{{ addslashes($client->name) }}', '{{ $client->model }}', '{{ $client->phone }}')"
                                    >
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button 
                                        type="button" 
                                        class="btn-action delete" 
                                        title="Eliminar Cliente"
                                        onclick="openDeleteModal('{{ $client->id }}', '{{ addslashes($client->name) }}')"
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
            <!-- Empty state block -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-people"></i>
                </div>
                <h3 class="empty-state-title">No hay clientes registrados</h3>
                <p class="empty-state-desc">Comienza registrando tu primer cliente persona o empresa haciendo clic en el botón superior.</p>
            </div>
        @endif
    </div>

    <!-- Pagination Wrapper -->
    @if($clients->count() > 0)
        <div class="pagination-wrapper">
            <div>
                Mostrando {{ $clients->firstItem() }} al {{ $clients->lastItem() }} de {{ $clients->total() }} clientes
            </div>
            <div>
                {{ $clients->appends(['search' => $search])->links() }}
            </div>
        </div>
    @endif

</div>

<!-- ============================================================== -->
<!-- 1. CREATE CLIENT MODAL -->
<!-- ============================================================== -->
<div class="modal" id="createClientModal">
    <div class="modal-backdrop" id="createModalBackdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Registrar Nuevo Cliente</h3>
            <button class="modal-close" id="btnCloseCreateModal">&times;</button>
        </div>
        
        <form action="{{ route('clients.store') }}" method="POST" autocomplete="off">
            @csrf

            <!-- Type Select -->
            <div class="form-group">
                <label for="create_type" class="form-label">Tipo de Cliente</label>
                <select name="type" id="create_type" required>
                    <option value="persona" {{ old('type') == 'persona' ? 'selected' : '' }}>Persona</option>
                    <option value="empresa" {{ old('type') == 'empresa' ? 'selected' : '' }}>Empresa</option>
                </select>
            </div>

            <!-- Name Input -->
            <div class="form-group">
                <label for="create_name" class="form-label">Nombre Completo / Razón Social</label>
                <input 
                    type="text" 
                    name="name" 
                    id="create_name" 
                    class="form-input" 
                    placeholder="Ej. Juan Pérez o MIK Software S.A.S" 
                    value="{{ old('name') }}" 
                    required
                >
            </div>

            <!-- Model Select -->
            <div class="form-group">
                <label for="create_model" class="form-label">Modelo de Cliente</label>
                <select name="model" id="create_model" required>
                    <option value="cliente_final" {{ old('model') == 'cliente_final' ? 'selected' : '' }}>Cliente Final</option>
                    <option value="revendedor" {{ old('model') == 'revendedor' ? 'selected' : '' }}>Revendedor</option>
                </select>
            </div>

            <!-- Phone Input -->
            <div class="form-group">
                <label for="create_phone" class="form-label">Teléfono (Opcional)</label>
                <input 
                    type="text" 
                    name="phone" 
                    id="create_phone" 
                    class="form-input" 
                    placeholder="Ej. +57 300 123 4567" 
                    value="{{ old('phone') }}"
                >
            </div>

            <!-- Footer Actions -->
            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelCreateModal">Cancelar</button>
                <button type="submit" class="btn-primary-action">Guardar Cliente</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- 2. EDIT CLIENT MODAL -->
<!-- ============================================================== -->
<div class="modal" id="editClientModal">
    <div class="modal-backdrop" id="editModalBackdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Editar Cliente</h3>
            <button class="modal-close" id="btnCloseEditModal">&times;</button>
        </div>
        
        <form id="editClientForm" method="POST" autocomplete="off">
            @csrf
            @method('PUT')

            <!-- Type Select -->
            <div class="form-group">
                <label for="edit_type" class="form-label">Tipo de Cliente</label>
                <select name="type" id="edit_type" required>
                    <option value="persona">Persona</option>
                    <option value="empresa">Empresa</option>
                </select>
            </div>

            <!-- Name Input -->
            <div class="form-group">
                <label for="edit_name" class="form-label">Nombre Completo / Razón Social</label>
                <input 
                    type="text" 
                    name="name" 
                    id="edit_name" 
                    class="form-input" 
                    placeholder="Ej. Juan Pérez o MIK Software S.A.S" 
                    required
                >
            </div>

            <!-- Model Select -->
            <div class="form-group">
                <label for="edit_model" class="form-label">Modelo de Cliente</label>
                <select name="model" id="edit_model" required>
                    <option value="cliente_final">Cliente Final</option>
                    <option value="revendedor">Revendedor</option>
                </select>
            </div>

            <!-- Phone Input -->
            <div class="form-group">
                <label for="edit_phone" class="form-label">Teléfono (Opcional)</label>
                <input 
                    type="text" 
                    name="phone" 
                    id="edit_phone" 
                    class="form-input" 
                    placeholder="Ej. +57 300 123 4567"
                >
            </div>

            <!-- Footer Actions -->
            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelEditModal">Cancelar</button>
                <button type="submit" class="btn-primary-action">Actualizar Cliente</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- 3. DELETE CONFIRMATION MODAL -->
<!-- ============================================================== -->
<div class="modal" id="deleteClientModal">
    <div class="modal-backdrop" id="deleteModalBackdrop"></div>
    <div class="modal-content" style="max-width: 420px; text-align: center;">
        <div style="font-size: 50px; color: #ff5252; margin-bottom: 15px;">
            <i class="bi bi-exclamation-circle"></i>
        </div>
        
        <h3 class="modal-title" style="margin-bottom: 12px; display: inline-block;">¿Eliminar Cliente?</h3>
        
        <p style="color: var(--silver-light); font-size: 14px; line-height: 1.6; margin-bottom: 25px;">
            ¿Estás seguro de que deseas eliminar al cliente <strong id="deleteClientName" style="color: var(--white);"></strong>? Esta acción no se puede deshacer.
        </p>
        
        <form id="deleteClientForm" method="POST">
            @csrf
            @method('DELETE')

            <div style="display: flex; justify-content: center; gap: 12px;">
                <button type="button" class="btn-secondary" id="btnCancelDeleteModal" style="flex: 1;">Cancelar</button>
                <button type="submit" class="btn-danger-action" style="flex: 1;">Eliminar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Control Logic (Vanilla JS) -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- Create Modal Selectors ---
        const btnOpenCreate = document.getElementById('btnOpenCreateModal');
        const modalCreate = document.getElementById('createClientModal');
        const btnCloseCreate = document.getElementById('btnCloseCreateModal');
        const btnCancelCreate = document.getElementById('btnCancelCreateModal');
        const backdropCreate = document.getElementById('createModalBackdrop');

        // --- Edit Modal Selectors ---
        const modalEdit = document.getElementById('editClientModal');
        const btnCloseEdit = document.getElementById('btnCloseEditModal');
        const btnCancelEdit = document.getElementById('btnCancelEditModal');
        const backdropEdit = document.getElementById('editModalBackdrop');

        // --- Delete Modal Selectors ---
        const modalDelete = document.getElementById('deleteClientModal');
        const btnCancelDelete = document.getElementById('btnCancelDeleteModal');
        const backdropDelete = document.getElementById('deleteModalBackdrop');

        // --- Event Listeners for Create Modal ---
        if(btnOpenCreate) {
            btnOpenCreate.addEventListener('click', () => modalCreate.classList.add('open'));
        }
        
        const closeCreateModal = () => {
            modalCreate.classList.remove('open');
        };
        
        if(btnCloseCreate) btnCloseCreate.addEventListener('click', closeCreateModal);
        if(btnCancelCreate) btnCancelCreate.addEventListener('click', closeCreateModal);
        if(backdropCreate) backdropCreate.addEventListener('click', closeCreateModal);

        // --- Event Listeners for Edit Modal ---
        const closeEditModal = () => {
            modalEdit.classList.remove('open');
        };
        
        if(btnCloseEdit) btnCloseEdit.addEventListener('click', closeEditModal);
        if(btnCancelEdit) btnCancelEdit.addEventListener('click', closeEditModal);
        if(backdropEdit) backdropEdit.addEventListener('click', closeEditModal);

        // --- Event Listeners for Delete Modal ---
        const closeDeleteModal = () => {
            modalDelete.classList.remove('open');
        };
        
        if(btnCancelDelete) btnCancelDelete.addEventListener('click', closeDeleteModal);
        if(backdropDelete) backdropDelete.addEventListener('click', closeDeleteModal);

    });

    // --- Global helper to populate and open the Edit Modal ---
    function openEditModal(id, type, name, model, phone) {
        const modal = document.getElementById('editClientModal');
        const form = document.getElementById('editClientForm');
        
        // Populate inputs
        document.getElementById('edit_type').value = type;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_model').value = model;
        document.getElementById('edit_phone').value = phone || '';

        // Dynamically build and set form target
        form.action = `/clients/${id}`;

        // Open modal
        modal.classList.add('open');
    }

    // --- Global helper to open Delete Confirmation Modal ---
    function openDeleteModal(id, name) {
        const modal = document.getElementById('deleteClientModal');
        const form = document.getElementById('deleteClientForm');
        const namePlaceholder = document.getElementById('deleteClientName');

        // Set name text and form action target
        namePlaceholder.textContent = name;
        form.action = `/clients/${id}`;

        // Open modal
        modal.classList.add('open');
    }
</script>

@endsection
