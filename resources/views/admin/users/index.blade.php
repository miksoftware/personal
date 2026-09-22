@extends('layouts.app')

@section('title', 'Gestión de Usuarios SaaS - MIK Software')
@section('page_title', 'Gestión de Usuarios SaaS')
@section('page_subtitle', 'Panel de Superadministrador para control de acceso y credenciales de los clientes registrados.')

@section('content')

{{-- ── Banner de Alertas y Notificaciones ────────────────────────────────── --}}
@if(session('status'))
    <div class="alert-banner-success" id="crud-status-alert" style="margin-bottom: 25px;">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('status') }}</span>
    </div>
    <script>
        setTimeout(() => {
            const el = document.getElementById('crud-status-alert');
            if (el) el.style.display = 'none';
        }, 7000);
    </script>
@endif

@if(session('generic_password_assigned'))
    @php
        $passInfo = session('generic_password_assigned');
    @endphp
    <div style="background: rgba(196, 113, 74, 0.15); border: 1px solid rgba(196, 113, 74, 0.35); border-radius: 12px; padding: 18px 22px; margin-bottom: 25px; display: flex; flex-direction: column; gap: 10px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(196, 113, 74, 0.25); display: flex; align-items: center; justify-content: center; color: #D4855E; font-size: 20px;">
                    <i class="bi bi-key-fill"></i>
                </div>
                <div>
                    <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: #FFFFFF;">
                        Contraseña generada para: <span style="color: #D4855E;">{{ $passInfo['user_name'] }}</span> ({{ $passInfo['email'] }})
                    </h4>
                    <p style="margin: 2px 0 0; font-size: 13px; color: var(--silver-light);">
                        Copia esta clave temporal para enviarla al cliente.
                    </p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <input 
                    type="text" 
                    id="assignedPasswordInput" 
                    value="{{ $passInfo['password'] }}" 
                    readonly 
                    style="background: rgba(0,0,0,0.5); border: 1px solid rgba(196, 113, 74, 0.4); border-radius: 8px; color: #FFFFFF; font-family: monospace; font-size: 15px; font-weight: 700; padding: 8px 14px; width: 190px; text-align: center;"
                >
                <button 
                    type="button" 
                    onclick="copyAssignedPassword()" 
                    id="btnCopyAssigned" 
                    class="btn-primary-action" 
                    style="padding: 8px 16px; font-size: 13px; background: var(--salmon);"
                >
                    <i class="bi bi-clipboard-check"></i>
                    <span>Copiar Clave</span>
                </button>
            </div>
        </div>
    </div>
    <script>
        function copyAssignedPassword() {
            const input = document.getElementById('assignedPasswordInput');
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value).then(() => {
                const btn = document.getElementById('btnCopyAssigned');
                btn.innerHTML = '<i class="bi bi-check-lg"></i> <span>¡Copiada!</span>';
                setTimeout(() => {
                    btn.innerHTML = '<i class="bi bi-clipboard-check"></i> <span>Copiar Clave</span>';
                }, 3000);
            });
        }
    </script>
@endif

@if(isset($errors) && $errors->any())
    <div class="alert-banner" id="crud-error-alert" style="margin-bottom: 25px;">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

{{-- ── Métricas Resumen KPI ────────────────────────────────────────── --}}
<div class="metrics-grid" style="margin-bottom: 30px;">
    <!-- Total Usuarios -->
    <div class="metric-card">
        <div class="metric-card-header">
            <span class="metric-title">Total Registrados</span>
            <div class="metric-icon-box purple">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
        <div class="metric-value">{{ number_format($stats['total']) }}</div>
    </div>

    <!-- Usuarios Activos -->
    <div class="metric-card">
        <div class="metric-card-header">
            <span class="metric-title">Acceso Activo</span>
            <div class="metric-icon-box green">
                <i class="bi bi-check-circle-fill"></i>
            </div>
        </div>
        <div class="metric-value" style="color: #10b981;">{{ number_format($stats['active']) }}</div>
    </div>

    <!-- Acceso Cancelado / Inactivos -->
    <div class="metric-card">
        <div class="metric-card-header">
            <span class="metric-title">Acceso Cancelado</span>
            <div class="metric-icon-box red">
                <i class="bi bi-slash-circle-fill"></i>
            </div>
        </div>
        <div class="metric-value highlighted" style="color: #ef4444;">{{ number_format($stats['inactive']) }}</div>
    </div>

    <!-- Nuevos del Mes -->
    <div class="metric-card">
        <div class="metric-card-header">
            <span class="metric-title">Nuevos Este Mes</span>
            <div class="metric-icon-box salmon">
                <i class="bi bi-calendar-check-fill"></i>
            </div>
        </div>
        <div class="metric-value">{{ number_format($stats['new_month']) }}</div>
    </div>
</div>

{{-- ── Main Table Card ──────────────────────────────────────────── --}}
<div class="client-table-card">

    {{-- Filter Bar --}}
    <div class="filter-bar" style="display: flex; gap: 14px; flex-wrap: wrap; justify-content: space-between; align-items: center;">
        <form action="{{ route('admin.users.index') }}" method="GET" class="search-wrapper" style="flex: 1; min-width: 280px; max-width: 480px; position: relative;">
            <i class="bi bi-search search-icon" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--silver);"></i>
            <input 
                type="text" 
                name="search" 
                class="search-input" 
                placeholder="Buscar por nombre o correo..." 
                value="{{ $search }}"
                autocomplete="off"
                style="padding-left: 45px; width: 100%;"
            >
            @if(request('status') && request('status') !== 'all')
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
        </form>

        <!-- Filtros de Estado Pills -->
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <a 
                href="{{ route('admin.users.index', array_merge(request()->query(), ['status' => 'all'])) }}"
                class="btn-filter {{ $status === 'all' ? 'active' : '' }}"
                style="padding: 7px 14px; border-radius: 8px; font-size: 13px; text-decoration: none; border: 1px solid rgba(255, 255, 255, 0.1); color: var(--white); background: {{ $status === 'all' ? 'var(--salmon)' : 'rgba(255, 255, 255, 0.04)' }}; font-weight: 500;"
            >
                Todos ({{ $stats['total'] }})
            </a>
            <a 
                href="{{ route('admin.users.index', array_merge(request()->query(), ['status' => 'active'])) }}"
                class="btn-filter {{ $status === 'active' ? 'active' : '' }}"
                style="padding: 7px 14px; border-radius: 8px; font-size: 13px; text-decoration: none; border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; background: {{ $status === 'active' ? 'rgba(16, 185, 129, 0.25)' : 'rgba(255, 255, 255, 0.04)' }}; font-weight: 500;"
            >
                <i class="bi bi-check-circle"></i> Activos ({{ $stats['active'] }})
            </a>
            <a 
                href="{{ route('admin.users.index', array_merge(request()->query(), ['status' => 'inactive'])) }}"
                class="btn-filter {{ $status === 'inactive' ? 'active' : '' }}"
                style="padding: 7px 14px; border-radius: 8px; font-size: 13px; text-decoration: none; border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; background: {{ $status === 'inactive' ? 'rgba(239, 68, 68, 0.25)' : 'rgba(255, 255, 255, 0.04)' }}; font-weight: 500;"
            >
                <i class="bi bi-slash-circle"></i> Cancelados ({{ $stats['inactive'] }})
            </a>
        </div>
    </div>

    {{-- Table Responsive Wrap --}}
    <div class="table-responsive">
        @if($users->count() > 0)
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Usuario Registrado</th>
                        <th>Rol</th>
                        <th>Estado de Acceso</th>
                        <th>Datos Registrados (SaaS)</th>
                        <th>Fecha de Registro</th>
                        <th style="width: 240px; text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        @php
                            $isSelf = $user->id === auth()->id();
                            $isRootAdmin = str_contains(strtolower($user->email), 'softwaremik');
                            $userActive = $user->isActive();
                        @endphp
                        <tr>
                            <!-- Usuario info: Avatar, Nombre, Correo -->
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: {{ $user->isSuperAdmin() ? 'linear-gradient(135deg, #C4714A, #4a0082)' : 'rgba(255, 255, 255, 0.08)' }}; border: 1px solid rgba(255, 255, 255, 0.15); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; color: #FFFFFF;">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; font-size: 14px; color: #FFFFFF; display: flex; align-items: center; gap: 6px;">
                                            {{ $user->name }}
                                            @if($isRootAdmin)
                                                <span title="Super Administrador Global" style="background: rgba(196, 113, 74, 0.2); color: #D4855E; font-size: 10px; padding: 2px 7px; border-radius: 6px; border: 1px solid rgba(196, 113, 74, 0.4); font-weight: 600;">
                                                    <i class="bi bi-shield-lock-fill"></i> ROOT ADMIN
                                                </span>
                                            @elseif($user->role === 'admin')
                                                <span style="background: rgba(138, 43, 226, 0.2); color: #C8CDD6; font-size: 10px; padding: 2px 7px; border-radius: 6px; font-weight: 600;">
                                                    ADMIN
                                                </span>
                                            @endif
                                            @if($isSelf)
                                                <span style="background: rgba(255, 255, 255, 0.1); color: #FFFFFF; font-size: 10px; padding: 2px 6px; border-radius: 4px;">
                                                    (Tú)
                                                </span>
                                            @endif
                                        </div>
                                        <div style="font-size: 12px; color: var(--silver-light); margin-top: 2px;">
                                            <i class="bi bi-envelope"></i> {{ $user->email }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Rol -->
                            <td>
                                <span style="font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 8px; background: {{ $user->isAdmin() ? 'rgba(138, 43, 226, 0.2)' : 'rgba(255, 255, 255, 0.05)' }}; color: {{ $user->isAdmin() ? '#D4855E' : 'var(--silver-light)' }}; border: 1px solid rgba(255,255,255,0.08);">
                                    {{ $user->isAdmin() ? 'Administrador' : 'Usuario SaaS' }}
                                </span>
                            </td>

                            <!-- Estado de Acceso -->
                            <td>
                                @if($userActive)
                                    <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 20px; background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Acceso Activo</span>
                                    </span>
                                @else
                                    <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 20px; background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">
                                        <i class="bi bi-slash-circle-fill"></i>
                                        <span>Acceso Cancelado</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Actividad / Tenant items -->
                            <td>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <span title="Clientes creados" style="font-size: 11px; padding: 3px 8px; border-radius: 6px; background: rgba(255,255,255,0.04); color: var(--silver-light); border: 1px solid rgba(255,255,255,0.05);">
                                        <i class="bi bi-people"></i> {{ $user->clients_count }} clientes
                                    </span>
                                    <span title="Licencias creadas" style="font-size: 11px; padding: 3px 8px; border-radius: 6px; background: rgba(255,255,255,0.04); color: var(--silver-light); border: 1px solid rgba(255,255,255,0.05);">
                                        <i class="bi bi-key"></i> {{ $user->licenses_count }} lic.
                                    </span>
                                    <span title="Cuentas bancarias" style="font-size: 11px; padding: 3px 8px; border-radius: 6px; background: rgba(255,255,255,0.04); color: var(--silver-light); border: 1px solid rgba(255,255,255,0.05);">
                                        <i class="bi bi-bank"></i> {{ $user->bank_accounts_count }} cuentas
                                    </span>
                                </div>
                            </td>

                            <!-- Fecha Registro -->
                            <td style="color: var(--silver-light); font-size: 13px;">
                                {{ $user->created_at ? $user->created_at->format('d/m/Y h:i A') : 'N/A' }}
                            </td>

                            <!-- Botones de Acción -->
                            <td style="text-align: center;">
                                <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">

                                    <!-- Botón Toggle Acceso -->
                                    @if($isSelf || $isRootAdmin)
                                        <button 
                                            type="button" 
                                            class="btn-action-icon" 
                                            disabled 
                                            title="La cuenta de Superadministrador no se puede suspender"
                                            style="opacity: 0.35; cursor: not-allowed; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 6px 10px; font-size: 12px; background: transparent; color: var(--silver);"
                                        >
                                            <i class="bi bi-shield-check"></i> Protegido
                                        </button>
                                    @else
                                        <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas {{ $userActive ? 'CANCELAR' : 'ACTIVAR' }} el acceso para el usuario {{ $user->name }}?');" style="margin: 0;">
                                            @csrf
                                            @if($userActive)
                                                <button 
                                                    type="submit" 
                                                    title="Suspender / Cancelar acceso del usuario al sistema"
                                                    style="cursor: pointer; border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 500; background: rgba(239, 68, 68, 0.1); color: #ef4444; transition: all 0.2s;"
                                                    onmouseover="this.style.background='rgba(239,68,68,0.25)'"
                                                    onmouseout="this.style.background='rgba(239,68,68,0.1)'"
                                                >
                                                    <i class="bi bi-slash-circle"></i> Cancelar
                                                </button>
                                            @else
                                                <button 
                                                    type="submit" 
                                                    title="Reactivar acceso del usuario al sistema"
                                                    style="cursor: pointer; border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 500; background: rgba(16, 185, 129, 0.1); color: #10b981; transition: all 0.2s;"
                                                    onmouseover="this.style.background='rgba(16,185,129,0.25)'"
                                                    onmouseout="this.style.background='rgba(16,185,129,0.1)'"
                                                >
                                                    <i class="bi bi-check2-circle"></i> Activar
                                                </button>
                                            @endif
                                        </form>
                                    @endif

                                    <!-- Botón Contraseña Genérica -->
                                    <button 
                                        type="button" 
                                        class="btn-reset-password" 
                                        title="Asignar contraseña genérica o temporal"
                                        onclick="openResetPasswordModal('{{ $user->id }}', '{{ addslashes($user->name) }}', '{{ addslashes($user->email) }}')"
                                        style="cursor: pointer; border: 1px solid rgba(196, 113, 74, 0.4); border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 500; background: rgba(196, 113, 74, 0.1); color: #D4855E; transition: all 0.2s; display: inline-flex; align-items: center; gap: 5px;"
                                        onmouseover="this.style.background='rgba(196,113,74,0.25)'"
                                        onmouseout="this.style.background='rgba(196,113,74,0.1)'"
                                    >
                                        <i class="bi bi-key"></i> Clave
                                    </button>

                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state" style="padding: 50px 20px; text-align: center;">
                <div style="font-size: 48px; color: var(--silver); margin-bottom: 15px;">
                    <i class="bi bi-people"></i>
                </div>
                <h3 class="empty-state-title" style="color: #FFFFFF; font-size: 18px; margin-bottom: 8px;">No se encontraron usuarios</h3>
                <p class="empty-state-desc" style="color: var(--silver-light); font-size: 14px;">Intenta cambiar el término de búsqueda o el filtro de estado seleccionado.</p>
            </div>
        @endif
    </div>

    {{-- Paginación --}}
    @if($users->total() > 0)
        <div class="pagination-wrapper" style="padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.06); font-size: 13px; color: var(--silver-light);">
            <div>
                Mostrando {{ $users->firstItem() }} al {{ $users->lastItem() }} de {{ $users->total() }} usuarios
            </div>
            <div>
                {{ $users->links('vendor.pagination.mik') }}
            </div>
        </div>
    @endif

</div>

{{-- ============================================================
     MODAL: ASIGNAR CONTRASEÑA GENÉRICA / TEMPORAL
     ============================================================ --}}
<div class="modal" id="resetPasswordModal" style="display: none; position: fixed; inset: 0; z-index: 9999; align-items: center; justify-content: center;">
    <div class="modal-backdrop" id="resetPasswordBackdrop" onclick="closeResetPasswordModal()" style="position: absolute; inset: 0; background: rgba(10, 0, 20, 0.75); backdrop-filter: blur(6px);"></div>
    <div class="modal-content" style="position: relative; z-index: 10; width: 100%; max-width: 480px; background: #22003D; border: 1px solid rgba(255,255,255,0.12); border-radius: 16px; padding: 26px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);">
        
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 14px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(196, 113, 74, 0.2); display: flex; align-items: center; justify-content: center; color: #D4855E; font-size: 18px;">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h3 class="modal-title" style="margin: 0; font-size: 17px; font-weight: 700; color: #FFFFFF;">Asignar Contraseña</h3>
            </div>
            <button class="modal-close" onclick="closeResetPasswordModal()" style="background: transparent; border: none; font-size: 22px; color: var(--silver); cursor: pointer;">&times;</button>
        </div>

        <form id="resetPasswordForm" action="" method="POST" autocomplete="off">
            @csrf

            <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); border-radius: 10px; padding: 12px 16px; margin-bottom: 20px;">
                <div style="font-size: 12px; color: var(--silver-light);">Usuario seleccionado:</div>
                <div id="modalUserName" style="font-size: 15px; font-weight: 700; color: #FFFFFF; margin-top: 2px;"></div>
                <div id="modalUserEmail" style="font-size: 12px; color: #D4855E; margin-top: 1px;"></div>
            </div>

            <!-- Opción 1: Contraseña Genérica Recomendada -->
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label" style="display: block; font-size: 13px; font-weight: 600; color: var(--silver-light); margin-bottom: 8px;">
                    Contraseña Genérica Recomendada:
                </label>
                <div style="display: flex; gap: 10px;">
                    <input 
                        type="text" 
                        id="genericPasswordInput" 
                        name="generic_password" 
                        value="MikSoftware2026*" 
                        readonly 
                        style="flex: 1; background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; color: #FFFFFF; font-family: monospace; font-size: 15px; font-weight: 700; padding: 10px 14px;"
                    >
                    <button 
                        type="button" 
                        onclick="copyModalGenericPassword()" 
                        id="btnCopyModalPassword"
                        title="Copiar contraseña genérica"
                        style="cursor: pointer; background: rgba(196, 113, 74, 0.2); border: 1px solid rgba(196, 113, 74, 0.4); border-radius: 10px; padding: 0 16px; color: #D4855E; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; transition: all 0.2s;"
                    >
                        <i class="bi bi-clipboard"></i>
                        <span>Copiar</span>
                    </button>
                </div>
                <small style="display: block; color: var(--silver); font-size: 11px; margin-top: 6px;">
                    <i class="bi bi-info-circle"></i> Esta es la clave por defecto que puedes facilitar al usuario para que ingrese de inmediato.
                </small>
            </div>

            <!-- Opción 2: O ingresar una contraseña manual diferente -->
            <div class="form-group" style="margin-bottom: 24px;">
                <label for="custom_password" class="form-label" style="display: block; font-size: 13px; font-weight: 600; color: var(--silver-light); margin-bottom: 8px;">
                    O ingresa una contraseña personalizada (opcional):
                </label>
                <input 
                    type="text" 
                    id="custom_password" 
                    name="custom_password" 
                    placeholder="Dejar en blanco para usar la genérica de arriba"
                    class="form-input"
                    style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; color: #FFFFFF; padding: 10px 14px; font-size: 14px;"
                >
            </div>

            <!-- Botones de Acción -->
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button 
                    type="button" 
                    onclick="closeResetPasswordModal()" 
                    style="cursor: pointer; background: transparent; border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; padding: 10px 18px; color: var(--silver-light); font-size: 13px; font-weight: 500;"
                >
                    Cancelar
                </button>
                <button 
                    type="submit" 
                    class="btn-primary-action" 
                    style="cursor: pointer; background: var(--salmon); border: none; border-radius: 10px; padding: 10px 22px; color: #FFFFFF; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;"
                >
                    <i class="bi bi-check2-circle"></i>
                    <span>Confirmar y Asignar</span>
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    function openResetPasswordModal(userId, userName, userEmail) {
        const modal = document.getElementById('resetPasswordModal');
        const form = document.getElementById('resetPasswordForm');
        const nameEl = document.getElementById('modalUserName');
        const emailEl = document.getElementById('modalUserEmail');
        const customInput = document.getElementById('custom_password');

        nameEl.textContent = userName;
        emailEl.textContent = userEmail;
        customInput.value = '';

        form.action = `/admin/users/${userId}/reset-password`;

        modal.style.display = 'flex';
    }

    function closeResetPasswordModal() {
        const modal = document.getElementById('resetPasswordModal');
        modal.style.display = 'none';
    }

    function copyModalGenericPassword() {
        const input = document.getElementById('genericPasswordInput');
        input.select();
        input.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(input.value).then(() => {
            const btn = document.getElementById('btnCopyModalPassword');
            btn.innerHTML = '<i class="bi bi-check-lg"></i> <span>¡Listo!</span>';
            setTimeout(() => {
                btn.innerHTML = '<i class="bi bi-clipboard"></i> <span>Copiar</span>';
            }, 2500);
        });
    }

    // Cerrar modal al presionar escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeResetPasswordModal();
        }
    });
</script>

@endsection
