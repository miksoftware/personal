@extends('layouts.app')

@section('title', 'Desarrollos a Medida - MIK Software Control')
@section('page_title', 'Desarrollos a Medida')
@section('page_subtitle', 'Registra y controla las mejoras y proyectos personalizados para tus clientes.')

@section('content')

{{-- ── Status / Error Alerts ────────────────────────────────── --}}
@if(session('status'))
    <div class="alert-banner-success" id="dev-status-alert" style="margin-bottom: 25px;">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('status') }}</span>
    </div>
    <script>
        setTimeout(() => {
            const el = document.getElementById('dev-status-alert');
            if (el) el.style.display = 'none';
        }, 7000);
    </script>
@endif

@if($errors->any())
    <div class="alert-banner" id="dev-error-alert" style="margin-bottom: 25px;">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

{{-- ── Summary Cards ────────────────────────────────────────── --}}
@php
    $totalPendiente = $developments->getCollection()->where('status', 'pendiente')->sum('amount');
    $totalDone      = $developments->getCollection()->whereIn('status', ['pagado', 'completado'])->sum('amount');
    $countPendiente = $developments->getCollection()->where('status', 'pendiente')->count();
    $countDone      = $developments->getCollection()->whereIn('status', ['pagado', 'completado'])->count();
    $labelPend = $filter === 'proyecto' ? 'En Proceso' : ($filter === 'mejora' ? 'Pendiente de pago' : ($filter === 'soporte' ? 'Contratos Activos' : 'Pendiente / En Proceso'));
    $labelDone = $filter === 'proyecto' ? 'Completados' : ($filter === 'mejora' ? 'Cobrado' : ($filter === 'soporte' ? 'Finalizados' : 'Cobrado / Completado'));
    $unit      = $filter === 'proyecto' ? ['proyecto','proyectos'] : ($filter === 'mejora' ? ['mejora','mejoras'] : ($filter === 'soporte' ? ['contrato','contratos'] : ['registro','registros']));
@endphp

@if($developments->count() > 0)
<div style="display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
    <div style="flex:1; min-width:180px; padding:14px 18px; background:rgba(255,193,7,0.07); border:1px solid rgba(255,193,7,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-hourglass-split" style="font-size:22px; color:#ffd54f;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">{{ $labelPend }}</div>
            <div style="font-size:18px; font-weight:700; color:#ffd54f;">${{ number_format($totalPendiente, 2) }}</div>
            <div style="font-size:11px; color:rgba(255,255,255,0.35);">{{ $countPendiente }} {{ $countPendiente === 1 ? $unit[0] : $unit[1] }}</div>
        </div>
    </div>
    <div style="flex:1; min-width:180px; padding:14px 18px; background:rgba(72,199,142,0.07); border:1px solid rgba(72,199,142,0.2); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-check-circle-fill" style="font-size:22px; color:#48c78e;"></i>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">{{ $labelDone }} (esta página)</div>
            <div style="font-size:18px; font-weight:700; color:#48c78e;">${{ number_format($totalDone, 2) }}</div>
            <div style="font-size:11px; color:rgba(255,255,255,0.35);">{{ $countDone }} {{ $countDone === 1 ? $unit[0] : $unit[1] }}</div>
        </div>
    </div>
</div>
@endif

{{-- ── Main Table Card ──────────────────────────────────────── --}}
<div class="client-table-card" style="padding:0; overflow:hidden;">

    {{-- Type Filter Tabs --}}
    <div class="dev-type-tabs">
        <a href="{{ route('developments.index', ['search' => $search]) }}"
           class="dev-type-tab {{ $filter === 'all' ? 'active' : '' }}">
            <i class="bi bi-grid-3x3-gap-fill"></i> Todos
        </a>
        <a href="{{ route('developments.index', ['search' => $search, 'filter' => 'mejora']) }}"
           class="dev-type-tab {{ $filter === 'mejora' ? 'active' : '' }}">
            <i class="bi bi-tools"></i> Mejoras
        </a>
        <a href="{{ route('developments.index', ['search' => $search, 'filter' => 'proyecto']) }}"
           class="dev-type-tab {{ $filter === 'proyecto' ? 'active' : '' }}">
            <i class="bi bi-kanban-fill"></i> Proyectos a Medida
        </a>
        <a href="{{ route('developments.index', ['search' => $search, 'filter' => 'soporte']) }}"
           class="dev-type-tab {{ $filter === 'soporte' ? 'active' : '' }}">
            <i class="bi bi-headset"></i> Soporte
        </a>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-bar">
        <form action="{{ route('developments.index') }}" method="GET" class="search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="search-input"
                placeholder="Buscar por título o cliente..."
                value="{{ $search }}" autocomplete="off">
            @if($filter !== 'all')
                <input type="hidden" name="filter" value="{{ $filter }}">
            @endif
        </form>

        <div style="display:flex; gap:8px;">
            <button class="btn-primary-action" id="btnOpenCreateMejora"
                style="background:linear-gradient(135deg,#42a5f5,#1e88e5); box-shadow:0 4px 12px rgba(30,136,229,0.25);">
                <i class="bi bi-tools"></i>
                <span>Nueva Mejora</span>
            </button>
            <button class="btn-primary-action" id="btnOpenCreateProyecto"
                style="background:linear-gradient(135deg,#ab47bc,#8e24aa); box-shadow:0 4px 12px rgba(142,36,170,0.25);">
                <i class="bi bi-kanban-fill"></i>
                <span>Nuevo Proyecto</span>
            </button>
            <button class="btn-primary-action" id="btnOpenCreateSoporte"
                style="background:linear-gradient(135deg,#26c6da,#00838f); box-shadow:0 4px 12px rgba(0,131,143,0.25);">
                <i class="bi bi-headset"></i>
                <span>Nuevo Soporte</span>
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        @if($developments->count() > 0)
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        @if($filter === 'all')<th>Tipo</th>@endif
                        <th>{{ $filter === 'proyecto' ? 'Proyecto' : ($filter === 'soporte' ? 'Contrato de Soporte' : 'Mejora') }}</th>
                        @if($filter !== 'proyecto' && $filter !== 'soporte')<th>Licencia</th>@endif
                        <th>Monto</th>
                        <th>Estado</th>
                        @if($filter === 'proyecto')
                            <th>Inicio</th>
                            <th>Fin Est.</th>
                        @elseif($filter === 'mejora')
                            <th>Entrega</th>
                            <th>Pago</th>
                        @elseif($filter === 'soporte')
                            <th>Inicio</th>
                            <th>Ciclo</th>
                        @else
                            <th>Fecha</th>
                        @endif
                        <th style="width:80px; text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($developments as $dev)
                        <tr>
                            <td style="font-weight:600;">{{ $dev->client->name }}</td>

                            @if($filter === 'all')
                                <td>
                                    <span class="badge-type {{ $dev->type }}">
                                        <i class="bi bi-{{ $dev->type === 'proyecto' ? 'kanban-fill' : ($dev->type === 'soporte' ? 'headset' : 'tools') }}"></i>
                                        {{ $dev->type_label }}
                                    </span>
                                </td>
                            @endif

                            <td>
                                <span class="dev-title">{{ $dev->title }}</span>
                                @if($dev->description)
                                    <span class="dev-desc-preview" title="{{ $dev->description }}">{{ $dev->description }}</span>
                                @endif
                            </td>

                            @if($filter !== 'proyecto' && $filter !== 'soporte')
                                <td>
                                    @if($dev->license)
                                        <span class="license-url" title="{{ $dev->license->url }}">{{ $dev->license->url }}</span>
                                    @else
                                        <span style="color:rgba(255,255,255,0.25); font-size:13px;">—</span>
                                    @endif
                                </td>
                            @endif

                            <td>
                                <span class="dev-amount">${{ number_format($dev->amount, 2) }}</span>
                                @if($dev->type === 'soporte' && $dev->monthly_fee && $dev->contract_months)
                                    <span class="dev-desc-preview">${{ number_format($dev->monthly_fee, 0) }}/período × {{ $dev->contract_months }} períodos</span>
                                @endif
                            </td>

                            <td>
                                <span class="badge-status {{ $dev->status }}">
                                    <i class="bi bi-circle-fill" style="font-size:7px;"></i>
                                    {{ $dev->status_label }}
                                </span>
                            </td>

                            @if($filter === 'proyecto')
                                <td style="color:var(--silver-light); font-size:13px;">
                                    {{ $dev->started_at ? \Carbon\Carbon::parse($dev->started_at)->format('d M Y') : '—' }}
                                </td>
                                <td style="font-size:13px;">
                                    @if($dev->estimated_end_at)
                                        <span style="color:var(--silver-light);">{{ \Carbon\Carbon::parse($dev->estimated_end_at)->format('d M Y') }}</span>
                                    @else
                                        <span style="color:rgba(255,255,255,0.25);">—</span>
                                    @endif
                                </td>
                            @elseif($filter === 'mejora')
                                <td style="color:var(--silver-light); font-size:13px;">
                                    {{ $dev->delivered_at ? \Carbon\Carbon::parse($dev->delivered_at)->format('d M Y') : '—' }}
                                </td>
                                <td style="font-size:13px;">
                                    @if($dev->paid_at)
                                        <span style="color:#48c78e;">{{ \Carbon\Carbon::parse($dev->paid_at)->format('d M Y') }}</span>
                                    @else
                                        <span style="color:rgba(255,255,255,0.25);">—</span>
                                    @endif
                                </td>
                            @elseif($filter === 'soporte')
                                <td style="color:var(--silver-light); font-size:13px;">
                                    {{ $dev->started_at ? \Carbon\Carbon::parse($dev->started_at)->format('d M Y') : '—' }}
                                </td>
                                <td style="color:var(--silver-light); font-size:13px;">
                                    @if($dev->billing_cycle)
                                        <span class="badge-cycle {{ $dev->billing_cycle }}">{{ $dev->billing_cycle_label }}</span>
                                        @if($dev->contract_months)<span style="font-size:11px; color:rgba(255,255,255,0.35);"> × {{ $dev->contract_months }}</span>@endif
                                    @else
                                        {{ $dev->contract_months ? $dev->contract_months . ' períodos' : '—' }}
                                    @endif
                                </td>
                            @else
                                <td style="color:var(--silver-light); font-size:13px;">
                                    @php $mainDate = $dev->type === 'proyecto' || $dev->type === 'soporte' ? $dev->started_at : $dev->delivered_at; @endphp
                                    {{ $mainDate ? \Carbon\Carbon::parse($mainDate)->format('d M Y') : '—' }}
                                </td>
                            @endif

                            <td style="text-align:center;">
                                <div class="actions-cell" style="justify-content:center;">
                                    <button type="button" class="btn-action edit" title="Editar"
                                        onclick="openEditDevModal(
                                            {{ $dev->id }},
                                            '{{ $dev->type }}',
                                            {{ $dev->client_id }},
                                            {{ $dev->license_id ?? 'null' }},
                                            '{{ addslashes($dev->title) }}',
                                            '{{ addslashes($dev->description ?? '') }}',
                                            '{{ $dev->amount }}',
                                            '{{ $dev->status }}',
                                            '{{ $dev->delivered_at ?? '' }}',
                                            '{{ $dev->paid_at ?? '' }}',
                                            '{{ $dev->started_at ?? '' }}',
                                            '{{ $dev->estimated_end_at ?? '' }}',
                                            {{ $dev->monthly_fee ?? 'null' }},
                                            {{ $dev->contract_months ?? 'null' }},
                                            {{ $dev->parent_id ?? 'null' }},
                                            '{{ $dev->billing_cycle ?? '' }}'
                                        )">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button type="button" class="btn-action delete" title="Eliminar"
                                        onclick="openDeleteDevModal({{ $dev->id }}, '{{ addslashes($dev->title) }}')">
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
                    <i class="bi bi-{{ $filter === 'proyecto' ? 'kanban' : ($filter === 'soporte' ? 'headset' : 'tools') }}"></i>
                </div>
                <h3 class="empty-state-title">
                        Sin {{ $filter === 'proyecto' ? 'proyectos' : ($filter === 'mejora' ? 'mejoras' : ($filter === 'soporte' ? 'contratos de soporte' : 'registros')) }} aún
                </h3>
                    <p class="empty-state-desc">Usa los botones superiores para registrar una mejora, proyecto o contrato de soporte.</p>
            </div>
        @endif
    </div>

    @if($developments->count() > 0)
        <div class="pagination-wrapper">
            <div>Mostrando {{ $developments->firstItem() }} al {{ $developments->lastItem() }} de {{ $developments->total() }} registros</div>
            <div>{{ $developments->appends(['search' => $search, 'filter' => $filter])->links('vendor.pagination.mik') }}</div>
        </div>
    @endif

</div>

{{-- ====================================================================
     LICENSES IN SOPORTE TAB
     ==================================================================== --}}
@if($filter === 'soporte')
<div class="client-table-card" style="margin-top:24px; padding:0; overflow:hidden;">
    <div style="padding:18px 20px 14px; border-bottom:1px solid rgba(255,255,255,0.06); display:flex; align-items:center; justify-content:space-between;">
        <h3 style="font-size:15px; font-weight:600; color:var(--white); margin:0;">
            <i class="bi bi-key-fill" style="color:#4dd0e1; margin-right:8px;"></i>Licencias (Soporte incluido)
        </h3>
        <span style="font-size:12px; color:rgba(255,255,255,0.4);">{{ $licenses->count() }} licencia(s)</span>
    </div>
    <div class="table-responsive">
        @if($licenses->count() > 0)
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>URL / Licencia</th>
                        <th>Ciclo</th>
                        <th>Tarifa</th>
                        <th>Próximo Cobro</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($licenses as $lic)
                        <tr>
                            <td style="font-weight:600;">{{ $lic->client->name ?? '—' }}</td>
                            <td>
                                <span class="license-url">{{ $lic->url }}</span>
                                @if($lic->is_free)
                                    <span style="margin-left:6px; font-size:10px; background:rgba(72,199,142,0.12); color:#48c78e; border:1px solid rgba(72,199,142,0.2); border-radius:4px; padding:1px 6px;">Gratis</span>
                                @endif
                            </td>
                            <td>
                                @if($lic->billing_cycle)
                                    <span class="badge-cycle {{ $lic->billing_cycle }}">{{ $lic->billing_cycle_label }}</span>
                                @else
                                    <span style="color:rgba(255,255,255,0.25); font-size:13px;">—</span>
                                @endif
                            </td>
                            <td>
                                @if(!$lic->is_free && $lic->monthly_fee)
                                    <span class="dev-amount">${{ number_format($lic->monthly_fee, 0) }}</span>
                                @else
                                    <span style="color:rgba(255,255,255,0.25); font-size:13px;">—</span>
                                @endif
                            </td>
                            <td style="color:var(--silver-light); font-size:13px;">
                                {{ $lic->next_billing_date ? \Carbon\Carbon::parse($lic->next_billing_date)->format('d M Y') : '—' }}
                            </td>
                            <td>
                                <span class="badge-status {{ $lic->status }}">
                                    <i class="bi bi-circle-fill" style="font-size:7px;"></i>
                                    {{ $lic->status_label }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state" style="padding:40px 20px;">
                <i class="bi bi-key" style="font-size:32px; color:rgba(255,255,255,0.1);"></i>
                <p style="color:rgba(255,255,255,0.35); margin-top:12px;">No hay licencias registradas.</p>
            </div>
        @endif
    </div>
</div>
@endif

{{-- ==============================================================
     MODAL 1 — CREAR MEJORA
     ============================================================== --}}
<div class="modal" id="createMejoraModal">
    <div class="modal-backdrop" id="createMejoraBackdrop"></div>
    <div class="modal-content" style="max-width:560px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="bi bi-tools" style="color:#42a5f5; margin-right:8px;"></i>Nueva Mejora
            </h3>
            <button class="modal-close" id="btnCloseCreateMejora">&times;</button>
        </div>
        <form action="{{ route('developments.store') }}" method="POST" autocomplete="off">
            @csrf
            <input type="hidden" name="type" value="mejora">

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="cm_client_id" class="form-label">Cliente *</label>
                    <select name="client_id" id="cm_client_id" required>
                        <option value="">— Selecciona —</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}"
                                {{ old('client_id') == $c->id && old('type') === 'mejora' ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="cm_license_id" class="form-label">
                        Licencia <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                    </label>
                    <select name="license_id" id="cm_license_id">
                        <option value="">— Sin licencia —</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="cm_title" class="form-label">Título de la Mejora *</label>
                <input type="text" name="title" id="cm_title" class="form-input"
                    placeholder="Ej. Módulo de reportes exportables"
                    value="{{ old('type') === 'mejora' ? old('title') : '' }}" required>
            </div>

            <div class="form-group">
                <label for="cm_description" class="form-label">
                    Descripción <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                </label>
                <textarea name="description" id="cm_description" rows="3"
                    placeholder="Detalla qué incluye esta mejora...">{{ old('type') === 'mejora' ? old('description') : '' }}</textarea>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="cm_amount" class="form-label">Monto ($) *</label>
                    <input type="number" name="amount" id="cm_amount" class="form-input"
                        placeholder="0.00" step="0.01" min="0"
                        value="{{ old('type') === 'mejora' ? old('amount') : '' }}" required>
                </div>
                <div class="form-group">
                    <label for="cm_status" class="form-label">Estado *</label>
                    <select name="status" id="cm_status" required>
                        <option value="pendiente"
                            {{ (!old('type') || old('type') === 'mejora') && old('status', 'pendiente') === 'pendiente' ? 'selected' : '' }}>
                            Pendiente de pago
                        </option>
                        <option value="pagado"
                            {{ old('type') === 'mejora' && old('status') === 'pagado' ? 'selected' : '' }}>
                            Pagado
                        </option>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="cm_delivered_at" class="form-label">
                        Fecha de Entrega <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                    </label>
                    <input type="date" name="delivered_at" id="cm_delivered_at" class="form-input"
                        value="{{ old('type') === 'mejora' ? old('delivered_at') : '' }}">
                </div>
                <div class="form-group" id="cm_paid_at_group">
                    <label for="cm_paid_at" class="form-label">Fecha de Pago</label>
                    <input type="date" name="paid_at" id="cm_paid_at" class="form-input"
                        value="{{ old('type') === 'mejora' ? old('paid_at') : '' }}">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelCreateMejora">Cancelar</button>
                <button type="submit" class="btn-primary-action"
                    style="background:linear-gradient(135deg,#42a5f5,#1e88e5);">
                    <i class="bi bi-plus-circle"></i> Guardar Mejora
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==============================================================
     MODAL 2 — CREAR PROYECTO
     ============================================================== --}}
<div class="modal" id="createProyectoModal">
    <div class="modal-backdrop" id="createProyectoBackdrop"></div>
    <div class="modal-content" style="max-width:560px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="bi bi-kanban-fill" style="color:#ab47bc; margin-right:8px;"></i>Nuevo Proyecto a Medida
            </h3>
            <button class="modal-close" id="btnCloseCreateProyecto">&times;</button>
        </div>
        <form action="{{ route('developments.store') }}" method="POST" autocomplete="off">
            @csrf
            <input type="hidden" name="type" value="proyecto">

            <div class="form-group">
                <label for="cp_client_id" class="form-label">Cliente *</label>
                <select name="client_id" id="cp_client_id" required>
                    <option value="">— Selecciona un cliente —</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}"
                            {{ old('client_id') == $c->id && old('type') === 'proyecto' ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="cp_title" class="form-label">Nombre del Proyecto *</label>
                <input type="text" name="title" id="cp_title" class="form-input"
                    placeholder="Ej. CRM Personalizado para Empresa X"
                    value="{{ old('type') === 'proyecto' ? old('title') : '' }}" required>
            </div>

            <div class="form-group">
                <label for="cp_description" class="form-label">
                    Descripción <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                </label>
                <textarea name="description" id="cp_description" rows="3"
                    placeholder="Describe el alcance del proyecto...">{{ old('type') === 'proyecto' ? old('description') : '' }}</textarea>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="cp_amount" class="form-label">Valor del Contrato ($) *</label>
                    <input type="number" name="amount" id="cp_amount" class="form-input"
                        placeholder="0.00" step="0.01" min="0"
                        value="{{ old('type') === 'proyecto' ? old('amount') : '' }}" required>
                </div>
                <div class="form-group">
                    <label for="cp_status" class="form-label">Estado *</label>
                    <select name="status" id="cp_status" required>
                        <option value="pendiente"
                            {{ !(old('type') === 'proyecto' && old('status') === 'completado') ? 'selected' : '' }}>
                            En Proceso
                        </option>
                        <option value="completado"
                            {{ old('type') === 'proyecto' && old('status') === 'completado' ? 'selected' : '' }}>
                            Completado
                        </option>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="cp_started_at" class="form-label">
                        Fecha Inicio <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                    </label>
                    <input type="date" name="started_at" id="cp_started_at" class="form-input"
                        value="{{ old('type') === 'proyecto' ? old('started_at') : '' }}">
                </div>
                <div class="form-group">
                    <label for="cp_estimated_end_at" class="form-label">
                        Fecha Fin Estimada <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                    </label>
                    <input type="date" name="estimated_end_at" id="cp_estimated_end_at" class="form-input"
                        value="{{ old('type') === 'proyecto' ? old('estimated_end_at') : '' }}">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelCreateProyecto">Cancelar</button>
                <button type="submit" class="btn-primary-action"
                    style="background:linear-gradient(135deg,#ab47bc,#8e24aa);">
                    <i class="bi bi-plus-circle"></i> Crear Proyecto
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==============================================================
     MODAL 3 — CREAR SOPORTE
     ============================================================== --}}
<div class="modal" id="createSoporteModal">
    <div class="modal-backdrop" id="createSoporteBackdrop"></div>
    <div class="modal-content" style="max-width:560px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="bi bi-headset" style="color:#26c6da; margin-right:8px;"></i>Nuevo Contrato de Soporte
            </h3>
            <button class="modal-close" id="btnCloseCreateSoporte">&times;</button>
        </div>
        <form action="{{ route('developments.store') }}" method="POST" autocomplete="off">
            @csrf
            <input type="hidden" name="type" value="soporte">

            <div class="form-group">
                <label for="cs_client_id" class="form-label">Cliente *</label>
                <select name="client_id" id="cs_client_id" required>
                    <option value="">— Selecciona un cliente —</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}"
                            {{ old('client_id') == $c->id && old('type') === 'soporte' ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="cs_title" class="form-label">Nombre del Contrato *</label>
                <input type="text" name="title" id="cs_title" class="form-input"
                    placeholder="Ej. Soporte técnico anual MikPOS"
                    value="{{ old('type') === 'soporte' ? old('title') : '' }}" required>
            </div>

            <div class="form-group">
                <label for="cs_description" class="form-label">
                    Descripción <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                </label>
                <textarea name="description" id="cs_description" rows="2"
                    placeholder="¿Qué incluye el soporte?...">{{ old('type') === 'soporte' ? old('description') : '' }}</textarea>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="cs_billing_cycle" class="form-label">Ciclo de Facturación *</label>
                    <select name="billing_cycle" id="cs_billing_cycle" required>
                        <option value="mensual" {{ old('billing_cycle', 'mensual') === 'mensual' ? 'selected' : '' }}>Mensual</option>
                        <option value="bimestral" {{ old('billing_cycle') === 'bimestral' ? 'selected' : '' }}>Bimestral (cada 2 meses)</option>
                        <option value="trimestral" {{ old('billing_cycle') === 'trimestral' ? 'selected' : '' }}>Trimestral (cada 3 meses)</option>
                        <option value="semestral" {{ old('billing_cycle') === 'semestral' ? 'selected' : '' }}>Semestral (cada 6 meses)</option>
                        <option value="anual" {{ old('billing_cycle') === 'anual' ? 'selected' : '' }}>Anual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="cs_contract_periods" class="form-label">Número de Períodos *</label>
                    <input type="number" name="contract_months" id="cs_contract_periods" class="form-input"
                        placeholder="12" min="1" max="120" step="1"
                        value="{{ old('type') === 'soporte' ? old('contract_months') : '' }}" required>
                </div>
            </div>

            <div class="form-group">
                <label for="cs_monthly_fee" class="form-label">Tarifa por Período ($) *</label>
                <input type="number" name="monthly_fee" id="cs_monthly_fee" class="form-input"
                    placeholder="50000" step="0.01" min="0"
                    value="{{ old('type') === 'soporte' ? old('monthly_fee') : '' }}" required>
            </div>

            {{-- Auto-calculated total preview --}}
            <div id="cs_total_preview" style="padding:10px 14px; background:rgba(0,188,212,0.06); border:1px solid rgba(0,188,212,0.2); border-radius:10px; margin-bottom:16px; font-size:13px; color:#4dd0e1; display:none;">
                <i class="bi bi-calculator"></i> Total estimado: <strong id="cs_total_amount"></strong>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="cs_status" class="form-label">Estado *</label>
                    <select name="status" id="cs_status" required>
                        <option value="pendiente" {{ old('type') === 'soporte' && old('status') === 'completado' ? '' : 'selected' }}>
                            Activo
                        </option>
                        <option value="completado" {{ old('type') === 'soporte' && old('status') === 'completado' ? 'selected' : '' }}>
                            Finalizado
                        </option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="cs_started_at" class="form-label">
                        Fecha Inicio <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                    </label>
                    <input type="date" name="started_at" id="cs_started_at" class="form-input"
                        value="{{ old('type') === 'soporte' ? old('started_at') : '' }}">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelCreateSoporte">Cancelar</button>
                <button type="submit" class="btn-primary-action"
                    style="background:linear-gradient(135deg,#26c6da,#00838f);">
                    <i class="bi bi-plus-circle"></i> Registrar Soporte
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==============================================================
     MODAL 4 — EDITAR (adapts to type via JS)
     ============================================================== --}}
<div class="modal" id="editDevModal">
    <div class="modal-backdrop" id="editDevBackdrop"></div>
    <div class="modal-content" style="max-width:560px;">
        <div class="modal-header">
            <h3 class="modal-title" id="editDevTitle">Editar</h3>
            <button class="modal-close" id="btnCloseEditDev">&times;</button>
        </div>
        <form id="editDevForm" method="POST" autocomplete="off">
            @csrf
            @method('PUT')
            <input type="hidden" name="type" id="edit_dev_type">

            {{-- Client + License (license hidden for proyecto) --}}
            <div id="edit_client_license_row" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="edit_dev_client_id" class="form-label">Cliente *</label>
                    <select name="client_id" id="edit_dev_client_id" required>
                        <option value="">— Selecciona —</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" id="edit_license_col">
                    <label for="edit_dev_license_id" class="form-label">
                        Licencia <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                    </label>
                    <select name="license_id" id="edit_dev_license_id">
                        <option value="">— Sin licencia —</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="edit_dev_title" class="form-label" id="edit_title_label">Título *</label>
                <input type="text" name="title" id="edit_dev_title" class="form-input" required>
            </div>

            <div class="form-group">
                <label for="edit_dev_description" class="form-label">
                    Descripción <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                </label>
                <textarea name="description" id="edit_dev_description" rows="3"></textarea>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;" id="edit_amount_row">
                <div class="form-group">
                    <label for="edit_dev_amount" class="form-label" id="edit_amount_label">Monto ($) *</label>
                    <input type="number" name="amount" id="edit_dev_amount" class="form-input" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label for="edit_dev_status" class="form-label">Estado *</label>
                    <select name="status" id="edit_dev_status" required></select>
                </div>
            </div>

            {{-- Soporte-only fields (billing_cycle × monthly_fee × contract_months) --}}
            <div id="edit_soporte_fields" style="display:none;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div class="form-group">
                        <label for="edit_dev_billing_cycle" class="form-label">Ciclo de Facturación *</label>
                        <select name="billing_cycle" id="edit_dev_billing_cycle">
                            <option value="mensual">Mensual</option>
                            <option value="bimestral">Bimestral</option>
                            <option value="trimestral">Trimestral</option>
                            <option value="semestral">Semestral</option>
                            <option value="anual">Anual</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_dev_monthly_fee" class="form-label">Tarifa por Período ($) *</label>
                        <input type="number" name="monthly_fee" id="edit_dev_monthly_fee" class="form-input" step="0.01" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit_dev_contract_months" class="form-label">Número de Períodos *</label>
                    <input type="number" name="contract_months" id="edit_dev_contract_months" class="form-input" min="1" max="120" step="1">
                </div>
                <div id="edit_soporte_total" style="padding:10px 14px; background:rgba(0,188,212,0.06); border:1px solid rgba(0,188,212,0.2); border-radius:10px; margin-bottom:16px; font-size:13px; color:#4dd0e1; display:none;">
                    <i class="bi bi-calculator"></i> Total: <strong id="edit_soporte_total_val"></strong>
                </div>
            </div>

            {{-- Mejora-only date fields --}}
            <div id="edit_mejora_dates" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="edit_dev_delivered_at" class="form-label">
                        Fecha de Entrega <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                    </label>
                    <input type="date" name="delivered_at" id="edit_dev_delivered_at" class="form-input">
                </div>
                <div class="form-group" id="edit_paid_at_group">
                    <label for="edit_dev_paid_at" class="form-label">Fecha de Pago</label>
                    <input type="date" name="paid_at" id="edit_dev_paid_at" class="form-input">
                </div>
            </div>

            {{-- Proyecto / Soporte date fields --}}
            <div id="edit_proyecto_dates" style="display:none; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="edit_dev_started_at" class="form-label">
                        Fecha Inicio <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                    </label>
                    <input type="date" name="started_at" id="edit_dev_started_at" class="form-input">
                </div>
                <div class="form-group">
                    <label for="edit_dev_estimated_end_at" class="form-label">
                        Fecha Fin Estimada <span style="color:rgba(255,255,255,0.3); font-size:11px;">(opcional)</span>
                    </label>
                    <input type="date" name="estimated_end_at" id="edit_dev_estimated_end_at" class="form-input">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelEditDev">Cancelar</button>
                <button type="submit" class="btn-primary-action" id="edit_dev_submit">
                    <i class="bi bi-check-circle"></i> Actualizar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==============================================================
     MODAL 4 — ELIMINAR
     ============================================================== --}}
<div class="modal" id="deleteDevModal">
    <div class="modal-backdrop" id="deleteDevBackdrop"></div>
    <div class="modal-content" style="max-width:420px; text-align:center;">
        <div style="font-size:50px; color:#ff5252; margin-bottom:15px;">
            <i class="bi bi-exclamation-circle"></i>
        </div>
        <h3 class="modal-title" style="margin-bottom:12px; display:inline-block;">¿Eliminar Registro?</h3>
        <p style="color:var(--silver-light); font-size:14px; line-height:1.6; margin-bottom:25px;">
            Estás a punto de eliminar
            <strong id="deleteDevTitle" style="color:var(--white);"></strong>.
            Esta acción no se puede deshacer.
        </p>
        <form id="deleteDevForm" method="POST">
            @csrf
            @method('DELETE')
            <div style="display:flex; justify-content:center; gap:12px;">
                <button type="button" class="btn-secondary" id="btnCancelDeleteDev" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn-danger-action" style="flex:1;">Eliminar</button>
            </div>
        </form>
    </div>
</div>

{{-- ── JavaScript ───────────────────────────────────────────── --}}
<script>
const allLicenses = @json($licenses->map(fn($l) => ['id' => $l->id, 'client_id' => $l->client_id, 'url' => $l->url]));

function populateLicenseSelect(selectEl, clientId, selectedId = null) {
    selectEl.innerHTML = '<option value="">— Sin licencia —</option>';
    if (!clientId) return;
    const filtered = allLicenses.filter(l => String(l.client_id) === String(clientId));
    filtered.forEach(l => {
        const opt = document.createElement('option');
        opt.value       = l.id;
        opt.textContent = l.url;
        if (selectedId && String(l.id) === String(selectedId)) opt.selected = true;
        selectEl.appendChild(opt);
    });
}

function syncPaidAt(statusEl, paidAtInput, groupEl) {
    if (statusEl.value === 'pagado') {
        if (!paidAtInput.value) paidAtInput.value = new Date().toISOString().substring(0, 10);
        groupEl.style.opacity = '1';
    } else {
        groupEl.style.opacity = '0.45';
    }
}

document.addEventListener('DOMContentLoaded', function () {

    // ── Create Mejora ─────────────────────────────────────────
    const modalCM      = document.getElementById('createMejoraModal');
    const cmClientSel  = document.getElementById('cm_client_id');
    const cmLicenseSel = document.getElementById('cm_license_id');
    const cmStatusSel  = document.getElementById('cm_status');
    const cmPaidAt     = document.getElementById('cm_paid_at');
    const cmPaidGroup  = document.getElementById('cm_paid_at_group');

    const openCM  = () => { modalCM.classList.add('open'); syncPaidAt(cmStatusSel, cmPaidAt, cmPaidGroup); };
    const closeCM = () => modalCM.classList.remove('open');

    document.getElementById('btnOpenCreateMejora').addEventListener('click', openCM);
    document.getElementById('btnCloseCreateMejora').addEventListener('click', closeCM);
    document.getElementById('btnCancelCreateMejora').addEventListener('click', closeCM);
    document.getElementById('createMejoraBackdrop').addEventListener('click', closeCM);
    cmClientSel.addEventListener('change', () => populateLicenseSelect(cmLicenseSel, cmClientSel.value));
    cmStatusSel.addEventListener('change', () => syncPaidAt(cmStatusSel, cmPaidAt, cmPaidGroup));

    // ── Create Proyecto ───────────────────────────────────────
    const modalCP  = document.getElementById('createProyectoModal');
    const openCP   = () => modalCP.classList.add('open');
    const closeCP  = () => modalCP.classList.remove('open');

    document.getElementById('btnOpenCreateProyecto').addEventListener('click', openCP);
    document.getElementById('btnCloseCreateProyecto').addEventListener('click', closeCP);
    document.getElementById('btnCancelCreateProyecto').addEventListener('click', closeCP);
    document.getElementById('createProyectoBackdrop').addEventListener('click', closeCP);

    // ── Create Soporte ────────────────────────────────────────
    const modalCS   = document.getElementById('createSoporteModal');
    const csFee     = document.getElementById('cs_monthly_fee');
    const csMonths  = document.getElementById('cs_contract_periods');
    const csPreview = document.getElementById('cs_total_preview');
    const csTotalEl = document.getElementById('cs_total_amount');

    const openCS  = () => modalCS.classList.add('open');
    const closeCS = () => modalCS.classList.remove('open');

    document.getElementById('btnOpenCreateSoporte').addEventListener('click', openCS);
    document.getElementById('btnCloseCreateSoporte').addEventListener('click', closeCS);
    document.getElementById('btnCancelCreateSoporte').addEventListener('click', closeCS);
    document.getElementById('createSoporteBackdrop').addEventListener('click', closeCS);

    function updateSoportePreview(fee, months, previewEl, totalEl) {
        const f = parseFloat(fee.value), m = parseInt(months.value);
        if (f > 0 && m > 0) {
            const total = f * m;
            totalEl.textContent = '$' + total.toLocaleString('es-CO', { minimumFractionDigits: 0 });
            previewEl.style.display = 'block';
        } else {
            previewEl.style.display = 'none';
        }
    }
    csFee.addEventListener('input', () => updateSoportePreview(csFee, csMonths, csPreview, csTotalEl));
    csMonths.addEventListener('input', () => updateSoportePreview(csFee, csMonths, csPreview, csTotalEl));

    // ── Edit ──────────────────────────────────────────────────
    const modalEdit     = document.getElementById('editDevModal');
    const editClientSel = document.getElementById('edit_dev_client_id');
    const editLicenseSel= document.getElementById('edit_dev_license_id');
    const editStatusSel = document.getElementById('edit_dev_status');
    const editPaidAt    = document.getElementById('edit_dev_paid_at');
    const editPaidGroup = document.getElementById('edit_paid_at_group');

    const closeEdit = () => modalEdit.classList.remove('open');
    document.getElementById('btnCloseEditDev').addEventListener('click', closeEdit);
    document.getElementById('btnCancelEditDev').addEventListener('click', closeEdit);
    document.getElementById('editDevBackdrop').addEventListener('click', closeEdit);

    editClientSel.addEventListener('change', () => {
        if (document.getElementById('edit_dev_type').value === 'mejora') {
            populateLicenseSelect(editLicenseSel, editClientSel.value);
        }
    });
    editStatusSel.addEventListener('change', () => {
        if (document.getElementById('edit_dev_type').value === 'mejora') {
            syncPaidAt(editStatusSel, editPaidAt, editPaidGroup);
        }
    });

    // Soporte total preview in edit modal
    const editFee    = document.getElementById('edit_dev_monthly_fee');
    const editMonths = document.getElementById('edit_dev_contract_months');
    const editSoporteTotal    = document.getElementById('edit_soporte_total');
    const editSoporteTotalVal = document.getElementById('edit_soporte_total_val');
    [editFee, editMonths].forEach(el => el.addEventListener('input', () => {
        updateSoportePreview(editFee, editMonths, editSoporteTotal, editSoporteTotalVal);
    }));

    // ── Delete ────────────────────────────────────────────────
    const closeDelete = () => document.getElementById('deleteDevModal').classList.remove('open');
    document.getElementById('btnCancelDeleteDev').addEventListener('click', closeDelete);
    document.getElementById('deleteDevBackdrop').addEventListener('click', closeDelete);

    // ── Reopen on validation error ────────────────────────────
    @if($errors->any())
        @if(old('type') === 'proyecto')
            openCP();
        @elseif(old('type') === 'soporte')
            openCS();
        @else
            openCM();
        @endif
    @endif
});

function openEditDevModal(id, type, clientId, licenseId, title, description, amount, status,
                          deliveredAt, paidAt, startedAt, estimatedEndAt,
                          monthlyFee, contractMonths, parentId, billingCycle) {
    const modal         = document.getElementById('editDevModal');
    const typeInput     = document.getElementById('edit_dev_type');
    const clientSel     = document.getElementById('edit_dev_client_id');
    const licenseSel    = document.getElementById('edit_dev_license_id');
    const statusSel     = document.getElementById('edit_dev_status');
    const paidAtInp     = document.getElementById('edit_dev_paid_at');
    const paidGroup     = document.getElementById('edit_paid_at_group');
    const licenseCol    = document.getElementById('edit_license_col');
    const clRow         = document.getElementById('edit_client_license_row');
    const mejoraDates   = document.getElementById('edit_mejora_dates');
    const proyectoDates = document.getElementById('edit_proyecto_dates');
    const soporteFields = document.getElementById('edit_soporte_fields');
    const amountRow     = document.getElementById('edit_amount_row');
    const titleLabel    = document.getElementById('edit_title_label');
    const amountLabel   = document.getElementById('edit_amount_label');
    const submitBtn     = document.getElementById('edit_dev_submit');
    const modalTitle    = document.getElementById('editDevTitle');

    typeInput.value = type;
    document.getElementById('edit_dev_title').value       = title;
    document.getElementById('edit_dev_description').value = description;
    clientSel.value = clientId;

    // Reset all type-specific sections
    mejoraDates.style.display   = 'none';
    proyectoDates.style.display = 'none';
    soporteFields.style.display = 'none';
    amountRow.style.display     = 'grid';

    if (type === 'mejora') {
        modalTitle.innerHTML = '<i class="bi bi-tools" style="color:#42a5f5; margin-right:8px;"></i>Editar Mejora';
        titleLabel.textContent  = 'Título de la Mejora *';
        amountLabel.textContent = 'Monto ($) *';
        submitBtn.innerHTML     = '<i class="bi bi-check-circle"></i> Actualizar Mejora';

        licenseCol.style.display        = '';
        clRow.style.gridTemplateColumns = '1fr 1fr';

        statusSel.innerHTML =
            '<option value="pendiente">Pendiente de pago</option>' +
            '<option value="pagado">Pagado</option>';
        statusSel.value = status;

        document.getElementById('edit_dev_amount').value = amount;
        mejoraDates.style.display   = 'grid';
        document.getElementById('edit_dev_delivered_at').value = deliveredAt;
        paidAtInp.value = paidAt;

        populateLicenseSelect(licenseSel, clientId, licenseId);
        syncPaidAt(statusSel, paidAtInp, paidGroup);

    } else if (type === 'soporte') {
        modalTitle.innerHTML = '<i class="bi bi-headset" style="color:#26c6da; margin-right:8px;"></i>Editar Contrato de Soporte';
        titleLabel.textContent = 'Nombre del Contrato *';
        submitBtn.innerHTML    = '<i class="bi bi-check-circle"></i> Actualizar Soporte';

        licenseCol.style.display        = 'none';
        clRow.style.gridTemplateColumns = '1fr';

        statusSel.innerHTML =
            '<option value="pendiente">Activo</option>' +
            '<option value="completado">Finalizado</option>';
        statusSel.value = status;

        amountRow.style.display     = 'none';
        soporteFields.style.display = 'block';

        document.getElementById('edit_dev_monthly_fee').value      = monthlyFee || '';
        document.getElementById('edit_dev_contract_months').value  = contractMonths || '';
        const billingCycleEl = document.getElementById('edit_dev_billing_cycle');
        if (billingCycleEl) billingCycleEl.value = billingCycle || 'mensual';
        proyectoDates.style.display = 'grid';
        document.getElementById('edit_dev_started_at').value       = startedAt;
        document.getElementById('edit_dev_estimated_end_at').value = estimatedEndAt;

        // Update preview
        const fee    = document.getElementById('edit_dev_monthly_fee');
        const months = document.getElementById('edit_dev_contract_months');
        const previewEl = document.getElementById('edit_soporte_total');
        const totalEl   = document.getElementById('edit_soporte_total_val');
        const f = parseFloat(fee.value), m = parseInt(months.value);
        if (f > 0 && m > 0) {
            totalEl.textContent = '$' + (f * m).toLocaleString('es-CO', { minimumFractionDigits: 0 });
            previewEl.style.display = 'block';
        }

    } else { // proyecto
        modalTitle.innerHTML = '<i class="bi bi-kanban-fill" style="color:#ab47bc; margin-right:8px;"></i>Editar Proyecto';
        titleLabel.textContent  = 'Nombre del Proyecto *';
        amountLabel.textContent = 'Valor del Contrato ($) *';
        submitBtn.innerHTML     = '<i class="bi bi-check-circle"></i> Actualizar Proyecto';

        licenseCol.style.display        = 'none';
        clRow.style.gridTemplateColumns = '1fr';

        statusSel.innerHTML =
            '<option value="pendiente">En Proceso</option>' +
            '<option value="completado">Completado</option>';
        statusSel.value = status;

        document.getElementById('edit_dev_amount').value = amount;
        proyectoDates.style.display = 'grid';
        document.getElementById('edit_dev_started_at').value       = startedAt;
        document.getElementById('edit_dev_estimated_end_at').value = estimatedEndAt;
    }

    document.getElementById('editDevForm').action = `/developments/${id}`;
    modal.classList.add('open');
}

function openDeleteDevModal(id, title) {
    document.getElementById('deleteDevTitle').textContent = title;
    document.getElementById('deleteDevForm').action       = `/developments/${id}`;
    document.getElementById('deleteDevModal').classList.add('open');
}
</script>

@endsection