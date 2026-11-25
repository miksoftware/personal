@extends('layouts.app')

@section('title', 'Licencias - MIK Software Control')
@section('page_title', 'Licencias')
@section('page_subtitle', 'Administra tus licencias, cobros por ciclo, pagos anuales totales y contratos en PDF.')
@section('layout_class', 'app-layout--viewport-lock')

@section('content')

{{-- ── Status / Error Alerts ────────────────────────────────────── --}}
@if(session('status'))
    <div class="alert-banner-success" id="license-status-alert" role="status" style="margin-bottom: 20px;">
        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
        <span>{{ session('status') }}</span>
    </div>
    <script>
        setTimeout(() => {
            const el = document.getElementById('license-status-alert');
            if (el) el.style.display = 'none';
        }, 7000);
    </script>
@endif

@if(isset($errors) && $errors->any())
    <div class="alert-banner" id="license-error-alert" role="alert" style="margin-bottom: 20px;">
        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- ── Refreshable Area (KPIs + Filter Pills + Table + Pagination) ── --}}
<div id="licensesRefreshArea">

    {{-- ── KPI Summary Cards ────────────────────────────────────── --}}
    <div class="lic-kpi-grid">
        <div class="lic-kpi-card {{ $overdueLicensesCount > 0 ? 'is-danger' : 'is-emerald' }}">
            <div class="lic-kpi-icon">
                <i class="bi {{ $overdueLicensesCount > 0 ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill' }}" aria-hidden="true"></i>
            </div>
            <div>
                <div class="lic-kpi-label">Por Cobrar Ahora (Vencido / Hoy)</div>
                <div class="lic-kpi-value">${{ number_format($totalDueNowAmount, 2) }}</div>
                <div class="lic-kpi-sub">
                    @if($overdueLicensesCount > 0)
                        {{ $overdueLicensesCount }} {{ $overdueLicensesCount === 1 ? 'licencia requiere cobro' : 'licencias requieren cobro' }}
                    @else
                        Todas las licencias están al día
                    @endif
                </div>
            </div>
        </div>

        <div class="lic-kpi-card is-emerald">
            <div class="lic-kpi-icon">
                <i class="bi bi-key-fill" aria-hidden="true"></i>
            </div>
            <div>
                <div class="lic-kpi-label">Licencias Registradas</div>
                <div class="lic-kpi-value">{{ $activeLicensesCount }} Activas</div>
                <div class="lic-kpi-sub">
                    {{ $totalLicensesCount }} totales · {{ $annualTotalCount }} con Pago Anual Total
                </div>
            </div>
        </div>

        <div class="lic-kpi-card is-blue">
            <div class="lic-kpi-icon">
                <i class="bi bi-file-earmark-pdf-fill" aria-hidden="true"></i>
            </div>
            <div>
                <div class="lic-kpi-label">Contratos en PDF</div>
                <div class="lic-kpi-value">{{ $contractsCount }} / {{ $totalLicensesCount }}</div>
                <div class="lic-kpi-sub">
                    {{ $totalLicensesCount - $contractsCount }} pendientes por adjuntar contrato
                </div>
            </div>
        </div>
    </div>

    {{-- ── Main Table Card ──────────────────────────────────────── --}}
    <div class="client-table-card licenses-view">

        {{-- Search & Primary Action Bar --}}
        <div class="filter-bar">
            <search style="flex:1; min-width: 260px;">
                <form action="{{ route('licenses.index') }}" method="GET" class="search-wrapper" id="licenseSearchForm" style="display:flex; gap:12px; background:transparent; border:none; padding:0; max-width:none;">
                    <input type="hidden" name="billing_filter" id="billingFilterInput" value="{{ $billingFilter }}">

                    <div style="position:relative; flex-grow:1; display:flex; align-items:center;">
                        <label for="licenseSearchInput" class="visually-hidden">Buscar licencia por URL o cliente</label>
                        <i class="bi bi-search search-icon" id="licenseSearchIcon" aria-hidden="true" style="position:absolute; left:16px; transition:all 0.2s;"></i>
                        <input
                            type="search"
                            name="search"
                            id="licenseSearchInput"
                            class="search-input"
                            placeholder="Buscar por URL o nombre de cliente..."
                            value="{{ $search }}"
                            autocomplete="off"
                            autocorrect="off"
                            autocapitalize="none"
                            spellcheck="false"
                            style="width: 100%;"
                        >
                    </div>

                    <div style="display:flex; align-items:center; gap:8px; background:var(--surface-color); border:1px solid rgba(255, 255, 255, 0.08); border-radius:10px; padding:0 12px;">
                        <label for="per_page" style="color:var(--silver-light); font-size:13px; white-space:nowrap; font-weight:500;">Mostrar:</label>
                        <select name="per_page" id="per_page" style="background:transparent; border:none; color:var(--white); font-size:13px; outline:none; padding:8px 0; cursor:pointer;">
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }} style="color:#000;">10</option>
                            <option value="15" {{ request('per_page') == 15 ? 'selected' : '' }} style="color:#000;">15</option>
                            <option value="30" {{ request('per_page') == 30 ? 'selected' : '' }} style="color:#000;">30</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }} style="color:#000;">50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }} style="color:#000;">100</option>
                        </select>
                    </div>
                </form>
            </search>

            @php
                $currentUser = Auth::user();
                $canCreateOwnLicense = $clients->isNotEmpty() || !$currentUser->hasLinkedClient();
            @endphp
            @if($canCreateOwnLicense)
            <button type="button" class="btn-primary-action" id="btnOpenCreateModal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>Nueva Licencia</span>
            </button>
            @endif
        </div>

        {{-- Quick Filter Pills --}}
        <div class="lic-filter-pills" role="group" aria-label="Filtros rápidos de licencias">
            @php
                $filters = [
                    'all' => ['label' => 'Todas', 'icon' => 'bi-grid'],
                    'due' => ['label' => 'Por cobrar (' . $overdueLicensesCount . ')', 'icon' => 'bi-exclamation-circle'],
                    'up_to_date' => ['label' => 'Al día', 'icon' => 'bi-check-circle'],
                    'anual_total' => ['label' => 'Anual Total (' . $annualTotalCount . ')', 'icon' => 'bi-stars'],
                    'with_contract' => ['label' => 'Con contrato (' . $contractsCount . ')', 'icon' => 'bi-file-earmark-check'],
                    'no_contract' => ['label' => 'Sin contrato', 'icon' => 'bi-file-earmark-plus'],
                ];
            @endphp
            @foreach($filters as $fKey => $fMeta)
                <button
                    type="button"
                    class="lic-filter-pill {{ $fKey === 'due' ? 'is-due' : '' }} {{ $billingFilter === $fKey ? 'active' : '' }}"
                    data-filter="{{ $fKey }}"
                >
                    <i class="bi {{ $fMeta['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $fMeta['label'] }}</span>
                </button>
            @endforeach
        </div>

        {{-- Content Wrap for Table & Pagination --}}
        <div id="licensesContentWrap">
            <div tabindex="0" role="region" aria-labelledby="licenses-table-caption" class="table-responsive lic-table-wrapper">
                @if($licenses->count() > 0)
                    <table class="custom-table" id="licensesTable">
                        <caption id="licenses-table-caption" class="visually-hidden">
                            Listado de licencias de software con su plan, valor real a pagar, vencimientos y contrato PDF
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">Cliente y Sitio</th>
                                <th scope="col">Estado y Módulos</th>
                                <th scope="col">Plan y Tarifas</th>
                                <th scope="col">Total a Pagar</th>
                                <th scope="col">Vencimientos</th>
                                <th scope="col">Contrato PDF</th>
                                <th scope="col" style="text-align:center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($licenses as $license)
                                @php
                                    $breakdown = $license->billing_breakdown;
                                    $cachedMod = $modulesMap[$license->id] ?? null;
                                    $hasCache = !empty($cachedMod);
                                    $accEnabled = $hasCache ? (($cachedMod['accounting']['enabled'] ?? false) || ($cachedMod['accounting']['status'] ?? '') === 'enabled') : null;
                                    $ecomEnabled = $hasCache ? (($cachedMod['ecommerce']['enabled'] ?? false) || ($cachedMod['ecommerce']['status'] ?? '') === 'enabled') : null;
                                    $isSuper = $currentUser->isSuperAdmin();
                                    $isOwned = $license->isOwnedBy($currentUser);
                                    $isShared = $license->isSharedForUser($currentUser);
                                    $displayClientName = $license->displayClientNameFor($currentUser);
                                    $hasCustomName = $isShared && $displayClientName !== ($license->client->name ?? '');

                                    $licensePayload = [
                                        'id' => $license->id,
                                        'is_owned' => $isOwned,
                                        'is_shared' => $isShared,
                                        'client_id' => $license->client_id,
                                        'client_name' => $displayClientName,
                                        'original_client_name' => $license->client->name ?? '',
                                        'custom_client_name' => $hasCustomName ? $displayClientName : '',
                                        'url' => $license->url,
                                        'status' => $license->status,
                                        'billing_cycle' => $license->billing_cycle,
                                        'billing_cycle_label' => $license->billing_cycle_label,
                                        'is_annual_total' => $license->isAnnualTotal(),
                                        'projected_annual_total' => (float) $license->projected_annual_total,
                                        'standard_plan_summary' => $license->standard_plan_summary,
                                        'monthly_fee' => (float) $license->monthly_fee,
                                        'setup_fee' => (float) $license->setup_fee,
                                        'service_amount' => (float) $breakdown['service_amount'],
                                        'annual_fee' => (float) $breakdown['annual_fee'],
                                        'due_now_amount' => (float) $breakdown['due_now_amount'],
                                        'service_due' => $breakdown['service_due'],
                                        'annual_due' => $breakdown['annual_due'],
                                        'next_billing_date' => $license->next_billing_date,
                                        'next_setup_billing_date' => $license->next_setup_billing_date,
                                        'is_free' => (bool) $license->is_free,
                                        'has_contract' => $license->hasContract(),
                                        'contract_name' => $license->contract_original_name,
                                        'contract_uploaded_at' => $license->contract_uploaded_at?->format('d M Y H:i'),
                                        'contract_url' => $license->hasContract() ? route('licenses.contract.show', $license) : null,
                                        'contract_download_url' => $license->hasContract() ? route('licenses.contract.show', ['license' => $license->id, 'download' => 1]) : null,
                                        'contract_upload_url' => ($isOwned || ($isShared && !$license->hasContract())) ? route('licenses.contract.upload', $license) : null,
                                        'contract_delete_url' => $isOwned ? route('licenses.contract.destroy', $license) : null,
                                        'shared_preferences_url' => $isShared ? route('licenses.shared-preferences', $license) : null,
                                    ];
                                    if ($isSuper) {
                                        $licensePayload['block_token'] = $license->block_token;
                                    }
                                    $licenseJson = json_encode($licensePayload, JSON_UNESCAPED_UNICODE);
                                @endphp
                                <tr>
                                    {{-- 1. Cliente & Sitio --}}
                                    <td class="td-client">
                                        <div class="lic-client-name">
                                            {{ $displayClientName }}
                                            @if($isShared)
                                                <span class="lic-shared-badge" title="Licencia compartida">Compartida</span>
                                            @endif
                                        </div>
                                        <a href="{{ str_starts_with($license->url, 'http') ? $license->url : 'https://' . $license->url }}"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           class="lic-site-link">
                                            <span>{{ $license->url }}</span>
                                            <i class="bi bi-box-arrow-up-right" style="font-size:10px; opacity:0.65;" aria-hidden="true"></i>
                                        </a>
                                        @if($isSuper)
                                            <div class="lic-token-chip" title="Token API remoto">
                                                <i class="bi bi-key" aria-hidden="true"></i>
                                                <span>{{ $license->block_token ?: 'adminmikpos123' }}</span>
                                            </div>
                                        @endif
                                    </td>

                                    {{-- 2. Estado & Módulos --}}
                                    <td class="td-status">
                                        <span class="badge-status {{ $license->status }}">
                                            <i class="bi bi-circle-fill" style="font-size:7px;" aria-hidden="true"></i>
                                            {{ $license->status_label }}
                                        </span>

                                        <div class="license-modules-cell" id="modulesCell_{{ $license->id }}" data-license-id="{{ $license->id }}" style="margin-top:4px;">
                                            @if($isSuper)
                                            <button
                                                type="button"
                                                class="mod-table-badge {{ $accEnabled === true ? 'enabled' : ($accEnabled === false ? 'disabled' : 'loading') }} interactive"
                                                id="modBadge_{{ $license->id }}_accounting"
                                                data-module="accounting"
                                                data-action="toggle-module"
                                                data-license-id="{{ $license->id }}"
                                                aria-label="Alternar módulo Contabilidad para {{ $license->url }}"
                                            >
                                            @else
                                            <div
                                                class="mod-table-badge {{ $accEnabled === true ? 'enabled' : ($accEnabled === false ? 'disabled' : 'loading') }}"
                                                id="modBadge_{{ $license->id }}_accounting"
                                                data-module="accounting"
                                            >
                                            @endif
                                                <span class="mod-table-label">
                                                    <i class="bi bi-journal-bookmark-fill" aria-hidden="true"></i> Conta
                                                </span>
                                                <span class="mod-table-status" id="modStatusText_{{ $license->id }}_accounting">
                                                    @if($accEnabled === true)
                                                        <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Activo
                                                    @elseif($accEnabled === false)
                                                        <i class="bi bi-slash-circle-fill" aria-hidden="true"></i> Inactivo
                                                    @else
                                                        <span class="sys-spinner mini"></span>
                                                    @endif
                                                </span>
                                            @if($isSuper)
                                            </button>
                                            @else
                                            </div>
                                            @endif

                                            @if($isSuper)
                                            <button
                                                type="button"
                                                class="mod-table-badge {{ $ecomEnabled === true ? 'enabled' : ($ecomEnabled === false ? 'disabled' : 'loading') }} interactive"
                                                id="modBadge_{{ $license->id }}_ecommerce"
                                                data-module="ecommerce"
                                                data-action="toggle-module"
                                                data-license-id="{{ $license->id }}"
                                                aria-label="Alternar módulo Tienda para {{ $license->url }}"
                                            >
                                            @else
                                            <div
                                                class="mod-table-badge {{ $ecomEnabled === true ? 'enabled' : ($ecomEnabled === false ? 'disabled' : 'loading') }}"
                                                id="modBadge_{{ $license->id }}_ecommerce"
                                                data-module="ecommerce"
                                            >
                                            @endif
                                                <span class="mod-table-label">
                                                    <i class="bi bi-shop" aria-hidden="true"></i> Tienda
                                                </span>
                                                <span class="mod-table-status" id="modStatusText_{{ $license->id }}_ecommerce">
                                                    @if($ecomEnabled === true)
                                                        <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Activa
                                                    @elseif($ecomEnabled === false)
                                                        <i class="bi bi-slash-circle-fill" aria-hidden="true"></i> Inactiva
                                                    @else
                                                        <span class="sys-spinner mini"></span>
                                                    @endif
                                                </span>
                                            @if($isSuper)
                                            </button>
                                            @else
                                            </div>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- 3. Plan y Tarifas --}}
                                    <td class="td-plan">
                                        @if($license->is_free)
                                            <span class="badge-free">
                                                <i class="bi bi-gift-fill" aria-hidden="true"></i>
                                                Gratuita
                                            </span>
                                            <div class="lic-plan-lines">
                                                <span class="lic-plan-line">Ciclo: {{ $license->billing_cycle_label }}</span>
                                            </div>
                                        @elseif($license->isAnnualTotal())
                                            <span class="badge-cycle anual_total">
                                                <i class="bi bi-stars" aria-hidden="true"></i> Anual Total
                                            </span>
                                            <div class="lic-plan-lines">
                                                <span class="lic-plan-line">
                                                    Pago único anual: <strong>${{ number_format($breakdown['service_amount'], 0, '.', ',') }}</strong>
                                                </span>
                                                <span class="lic-plan-line" style="opacity:0.7;">Todo incluido · Sin anualidad aparte</span>
                                            </div>
                                        @else
                                            <span class="badge-cycle">{{ $license->billing_cycle_label }}</span>
                                            <div class="lic-plan-lines">
                                                <span class="lic-plan-line">
                                                    Servicio: <strong>${{ number_format($breakdown['service_amount'], 0, '.', ',') }}</strong>
                                                    @if($license->cycle_months > 1)
                                                        <span style="opacity:0.7;">(${{ number_format($license->monthly_fee, 0, '.', ',') }}/mes × {{ $license->cycle_months }})</span>
                                                    @endif
                                                </span>
                                                @if($breakdown['annual_fee'] > 0)
                                                    <span class="lic-plan-line">
                                                        Anualidad: <strong style="color:var(--salmon-light);">${{ number_format($breakdown['annual_fee'], 0, '.', ',') }}</strong>
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>

                                    {{-- 4. Total a Pagar (Transparent Breakdown) --}}
                                    <td class="td-fee">
                                        @if($license->is_free)
                                            <span class="badge-free">
                                                <i class="bi bi-gift-fill" aria-hidden="true"></i>
                                                $0.00
                                            </span>
                                        @else
                                            <div class="lic-due-box {{ $breakdown['display_state'] }}">
                                                <span class="lic-due-amount">${{ number_format($breakdown['display_amount'], 2) }}</span>
                                                <span class="lic-due-pill">{{ $breakdown['display_badge'] }}</span>
                                                <span class="lic-due-caption">{{ $breakdown['display_caption'] }}</span>
                                            </div>
                                        @endif
                                    </td>

                                    {{-- 5. Vencimientos --}}
                                    <td class="td-date">
                                        @if($license->is_free)
                                            <span style="opacity:0.5; font-size:13px;">N/A</span>
                                        @else
                                            @php
                                                $serviceClass = $breakdown['service_overdue'] ? 'is-overdue' : ($breakdown['service_due_today'] ? 'is-today' : ($breakdown['service_soon'] ? 'is-soon' : ''));
                                                $annualClass = $breakdown['annual_overdue'] ? 'is-overdue' : ($breakdown['annual_due_today'] ? 'is-today' : ($breakdown['annual_soon'] ? 'is-soon' : ($breakdown['annual_missing_date'] ? 'is-missing' : '')));
                                            @endphp
                                            <div class="lic-date-list">
                                                <div class="lic-date-item {{ $serviceClass }}">
                                                    <span class="lic-date-tag">{{ $license->isAnnualTotal() ? 'Renovación Anual' : 'Servicio' }}</span>
                                                    {{ \Carbon\Carbon::parse($license->next_billing_date)->format('d M Y') }}
                                                    @if($breakdown['service_overdue'])
                                                        <span>(Vencida)</span>
                                                    @elseif($breakdown['service_due_today'])
                                                        <span>(Vence hoy)</span>
                                                    @elseif($breakdown['service_soon'])
                                                        <span>(Próxima)</span>
                                                    @endif
                                                </div>

                                                @if($breakdown['annual_fee'] > 0)
                                                    <div class="lic-date-item {{ $annualClass }}">
                                                        <span class="lic-date-tag">Anualidad</span>
                                                        @if($license->next_setup_billing_date)
                                                            {{ \Carbon\Carbon::parse($license->next_setup_billing_date)->format('d M Y') }}
                                                            @if($breakdown['annual_overdue'])
                                                                <span>(Vencida)</span>
                                                            @elseif($breakdown['annual_due_today'])
                                                                <span>(Vence hoy)</span>
                                                            @elseif($breakdown['annual_soon'])
                                                                <span>(Próxima)</span>
                                                            @endif
                                                        @else
                                                            <span>Sin fecha asignada</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </td>

                                    {{-- 6. Contrato PDF (Opción dedicada) --}}
                                    <td class="td-contract">
                                        <div class="lic-contract-cell">
                                            @if($license->hasContract())
                                                <button
                                                    type="button"
                                                    class="lic-contract-btn has-file"
                                                    data-action="view-contract"
                                                    data-license="{{ $licenseJson }}"
                                                    aria-label="Ver contrato PDF de {{ $license->url }}"
                                                >
                                                    <i class="bi bi-file-earmark-pdf-fill" aria-hidden="true"></i>
                                                    <span>Ver PDF</span>
                                                </button>
                                                <span class="lic-contract-filename">{{ $license->contract_original_name ?: 'contrato.pdf' }}</span>
                                            @else
                                                <button
                                                    type="button"
                                                    class="lic-contract-btn no-file"
                                                    data-action="upload-contract"
                                                    data-license="{{ $licenseJson }}"
                                                    aria-label="Subir contrato PDF para {{ $license->url }}"
                                                >
                                                    <i class="bi bi-upload" aria-hidden="true"></i>
                                                    <span>Subir PDF</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- 7. Acciones --}}
                                    <td class="td-actions" style="text-align:center;">
                                        <div class="actions-cell" style="justify-content:center; gap:6px;">
                                            @if($isOwned)
                                                <button
                                                    type="button"
                                                    class="btn-action edit"
                                                    title="Registrar pago"
                                                    aria-label="Registrar pago para {{ $license->url }}"
                                                    style="background:rgba(72,199,142,0.1); color:#48c78e; border:1px solid rgba(72,199,142,0.25);"
                                                    data-action="pay-license"
                                                    data-license="{{ $licenseJson }}"
                                                >
                                                    <i class="bi bi-cash-stack" aria-hidden="true"></i>
                                                </button>

                                                <button
                                                    type="button"
                                                    class="btn-action edit"
                                                    title="Editar licencia"
                                                    aria-label="Editar licencia {{ $license->url }}"
                                                    data-action="edit-license"
                                                    data-license="{{ $licenseJson }}"
                                                >
                                                    <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                                                </button>

                                                @if($isSuper)
                                                <button
                                                    type="button"
                                                    class="btn-action power"
                                                    title="Control Remoto de Sistema y Módulos"
                                                    aria-label="Control remoto de {{ $license->url }}"
                                                    data-action="system-control"
                                                    data-license="{{ $licenseJson }}"
                                                >
                                                    <i class="bi bi-power" aria-hidden="true"></i>
                                                </button>
                                                @endif

                                                <button
                                                    type="button"
                                                    class="btn-action delete"
                                                    title="Eliminar licencia"
                                                    aria-label="Eliminar licencia {{ $license->url }}"
                                                    data-action="delete-license"
                                                    data-license="{{ $licenseJson }}"
                                                >
                                                    <i class="bi bi-trash-fill" aria-hidden="true"></i>
                                                </button>
                                            @else
                                                <button
                                                    type="button"
                                                    class="btn-action edit"
                                                    title="Cambiar nombre local o modalidad Anual Total"
                                                    aria-label="Personalizar servicio y nombre para {{ $license->url }}"
                                                    style="width:auto; padding:0 10px; gap:5px; background:rgba(196,113,74,0.12); color:var(--salmon-light); border:1px solid rgba(196,113,74,0.3); font-size:11.5px; font-weight:600;"
                                                    data-action="edit-shared"
                                                    data-license="{{ $licenseJson }}"
                                                >
                                                    <i class="bi bi-sliders" aria-hidden="true"></i>
                                                    <span>Configurar</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-key" aria-hidden="true"></i>
                        </div>
                        <h3 class="empty-state-title">No se encontraron licencias</h3>
                        <p class="empty-state-desc">
                            @if($search !== '' || $billingFilter !== 'all')
                                No hay resultados que coincidan con el filtro o búsqueda actual.
                            @else
                                Comienza registrando la primera licencia de uno de tus clientes haciendo clic en "Nueva Licencia".
                            @endif
                        </p>
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
                        {{ $licenses->links('vendor.pagination.mik') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ============================================================
     1. CREATE LICENSE DIALOG
     ============================================================ --}}
<dialog class="mik-dialog" id="createLicenseDialog" closedby="any" aria-labelledby="createLicenseTitle">
    <div class="modal-header">
        <h1 class="modal-title" id="createLicenseTitle">Nueva Licencia</h1>
        <button type="button" class="modal-close" data-close-dialog="createLicenseDialog" aria-label="Cerrar">&times;</button>
    </div>

    <div class="modal-note">
        <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
        <span>La licencia N.° 5, 10, 15, 20... de cada cliente se marcará automáticamente como <strong>Gratuita ($0.00)</strong>.</span>
    </div>

    <form action="{{ route('licenses.store') }}" method="POST" autocomplete="off">
        @csrf

        <div class="form-group">
            <label for="create_client_id" class="form-label">Cliente *</label>
            <select name="client_id" id="create_client_id" required autofocus>
                <option value="">— Selecciona un cliente —</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                        {{ $client->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="create_url" class="form-label">URL del Sitio *</label>
            <input
                type="text"
                name="url"
                id="create_url"
                class="form-input"
                placeholder="Ej. https://cliente.miksoftware.com"
                value="{{ old('url') }}"
                autocorrect="off"
                autocapitalize="none"
                spellcheck="false"
                required
            >
        </div>

        @if(Auth::user()->isSuperAdmin())
        <div class="form-group">
            <label for="create_block_token" class="form-label">
                Token para Bloqueo y Módulos (API)
                <span style="font-size: 11px; color: var(--salmon-light); font-weight: normal; margin-left: 6px;">(Por defecto: adminmikpos123)</span>
            </label>
            <input
                type="text"
                name="block_token"
                id="create_block_token"
                class="form-input"
                placeholder="adminmikpos123"
                value="{{ old('block_token', 'adminmikpos123') }}"
                autocorrect="off"
                autocapitalize="none"
                spellcheck="false"
            >
        </div>
        @endif

        <div class="lic-form-grid">
            <div class="form-group">
                <label for="create_status" class="form-label">Estado *</label>
                <select name="status" id="create_status" required>
                    <option value="activa"     {{ old('status', 'activa') == 'activa' ? 'selected' : '' }}>Activa</option>
                    <option value="suspendida" {{ old('status') == 'suspendida' ? 'selected' : '' }}>Suspendida</option>
                    <option value="vencida"    {{ old('status') == 'vencida' ? 'selected' : '' }}>Vencida</option>
                </select>
            </div>
            <div class="form-group">
                <label for="create_billing_cycle" class="form-label">Modalidad / Ciclo *</label>
                <select name="billing_cycle" id="create_billing_cycle" required>
                    <option value="mensual"     {{ old('billing_cycle') == 'mensual' ? 'selected' : '' }}>Mensual (cada mes)</option>
                    <option value="trimestral"  {{ old('billing_cycle') == 'trimestral' ? 'selected' : '' }}>Trimestral (cada 3 meses)</option>
                    <option value="semestral"   {{ old('billing_cycle') == 'semestral' ? 'selected' : '' }}>Semestral (cada 6 meses)</option>
                    <option value="anual"       {{ old('billing_cycle') == 'anual' ? 'selected' : '' }}>Anual (Servicio + Anualidad aparte)</option>
                    <option value="anual_total" {{ old('billing_cycle') == 'anual_total' ? 'selected' : '' }}>Pago Anual Total (Valor único todo incluido)</option>
                </select>
            </div>
        </div>

        <div class="lic-form-grid">
            <div class="form-group">
                <label for="create_monthly_fee" id="create_fee_label" class="form-label">Tarifa Mensual ($) *</label>
                <input
                    type="number"
                    name="monthly_fee"
                    id="create_monthly_fee"
                    class="form-input"
                    placeholder="50000"
                    step="0.01"
                    min="0"
                    inputmode="decimal"
                    aria-describedby="create_fee_hint"
                    value="{{ old('monthly_fee') }}"
                    required
                >
                <span id="create_fee_hint" class="lic-field-hint"></span>
            </div>

            <div class="form-group">
                <label for="create_next_billing_date" id="create_date_label" class="form-label">Próx. Cobro de Servicio *</label>
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

        <fieldset class="lic-annual-section" id="create_annual_section">
            <legend class="lic-annual-section-legend">Anualidad / Instalación (Opcional)</legend>
            <div class="lic-form-grid">
                <div class="form-group">
                    <label for="create_setup_fee" class="form-label">Valor Anualidad ($)</label>
                    <input
                        type="number"
                        name="setup_fee"
                        id="create_setup_fee"
                        class="form-input"
                        placeholder="0"
                        step="0.01"
                        min="0"
                        inputmode="decimal"
                        aria-describedby="create_setup_hint"
                        value="{{ old('setup_fee', 0) }}"
                        required
                    >
                    <span id="create_setup_hint" class="lic-field-hint">Déjalo en 0 si no cobra anualidad aparte.</span>
                </div>
                <div class="form-group">
                    <label for="create_next_setup_billing_date" class="form-label">Próx. Cobro Anualidad</label>
                    <input
                        type="date"
                        name="next_setup_billing_date"
                        id="create_next_setup_billing_date"
                        class="form-input"
                        value="{{ old('next_setup_billing_date') }}"
                    >
                </div>
            </div>
        </fieldset>

        <div class="lic-live-preview" id="create_billing_preview" aria-live="polite"></div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" data-close-dialog="createLicenseDialog">Cancelar</button>
            <button type="submit" class="btn-primary-action">
                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                <span>Guardar Licencia</span>
            </button>
        </div>
    </form>
</dialog>

{{-- ============================================================
     2. EDIT LICENSE DIALOG
     ============================================================ --}}
<dialog class="mik-dialog" id="editLicenseDialog" closedby="any" aria-labelledby="editLicenseTitle">
    <div class="modal-header">
        <h1 class="modal-title" id="editLicenseTitle">Editar Licencia</h1>
        <button type="button" class="modal-close" data-close-dialog="editLicenseDialog" aria-label="Cerrar">&times;</button>
    </div>

    <div class="modal-note" id="editFreeNote" style="display:none;">
        <i class="bi bi-gift-fill" aria-hidden="true"></i>
        <span>Esta es una licencia <strong>Gratuita</strong>. La tarifa se mantendrá en <strong>$0.00</strong> automáticamente.</span>
    </div>

    <form id="editLicenseForm" method="POST" autocomplete="off">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="edit_client_id" class="form-label">Cliente *</label>
            <select name="client_id" id="edit_client_id" required autofocus>
                <option value="">— Selecciona un cliente —</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="edit_url" class="form-label">URL del Sitio *</label>
            <input
                type="text"
                name="url"
                id="edit_url"
                class="form-input"
                placeholder="https://cliente.miksoftware.com"
                autocorrect="off"
                autocapitalize="none"
                spellcheck="false"
                required
            >
        </div>

        @if(Auth::user()->isSuperAdmin())
        <div class="form-group">
            <label for="edit_block_token" class="form-label">
                Token para Bloqueo y Módulos (API)
                <span style="font-size: 11px; color: var(--salmon-light); font-weight: normal; margin-left: 6px;">(Maestro: adminmikpos123)</span>
            </label>
            <input
                type="text"
                name="block_token"
                id="edit_block_token"
                class="form-input"
                placeholder="adminmikpos123"
                autocorrect="off"
                autocapitalize="none"
                spellcheck="false"
            >
        </div>
        @endif

        <div class="lic-form-grid">
            <div class="form-group">
                <label for="edit_status" class="form-label">Estado *</label>
                <select name="status" id="edit_status" required>
                    <option value="activa">Activa</option>
                    <option value="suspendida">Suspendida</option>
                    <option value="vencida">Vencida</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_billing_cycle" class="form-label">Modalidad / Ciclo *</label>
                <select name="billing_cycle" id="edit_billing_cycle" required>
                    <option value="mensual">Mensual (cada mes)</option>
                    <option value="trimestral">Trimestral (cada 3 meses)</option>
                    <option value="semestral">Semestral (cada 6 meses)</option>
                    <option value="anual">Anual (Servicio + Anualidad aparte)</option>
                    <option value="anual_total">Pago Anual Total (Valor único todo incluido)</option>
                </select>
            </div>
        </div>

        <div class="lic-form-grid">
            <div class="form-group">
                <label for="edit_monthly_fee" id="edit_fee_label" class="form-label">Tarifa Mensual ($) *</label>
                <input
                    type="number"
                    name="monthly_fee"
                    id="edit_monthly_fee"
                    class="form-input"
                    step="0.01"
                    min="0"
                    inputmode="decimal"
                    aria-describedby="edit_fee_hint"
                    required
                >
                <span id="edit_fee_hint" class="lic-field-hint"></span>
                <button type="button" id="edit_annual_suggestion_btn" class="lic-suggestion-btn" style="display:none;"></button>
            </div>

            <div class="form-group">
                <label for="edit_next_billing_date" id="edit_date_label" class="form-label">Próx. Cobro de Servicio *</label>
                <input type="date" name="next_billing_date" id="edit_next_billing_date" class="form-input" required>
            </div>
        </div>

        <fieldset class="lic-annual-section" id="edit_annual_section">
            <legend class="lic-annual-section-legend">Anualidad / Instalación (Opcional)</legend>
            <div class="lic-form-grid">
                <div class="form-group">
                    <label for="edit_setup_fee" class="form-label">Valor Anualidad ($)</label>
                    <input
                        type="number"
                        name="setup_fee"
                        id="edit_setup_fee"
                        class="form-input"
                        step="0.01"
                        min="0"
                        inputmode="decimal"
                        aria-describedby="edit_setup_hint"
                        required
                    >
                    <span id="edit_setup_hint" class="lic-field-hint">Déjalo en 0 si no cobra anualidad aparte.</span>
                </div>
                <div class="form-group">
                    <label for="edit_next_setup_billing_date" class="form-label">Próx. Cobro Anualidad</label>
                    <input type="date" name="next_setup_billing_date" id="edit_next_setup_billing_date" class="form-input">
                </div>
            </div>
        </fieldset>

        <div class="lic-live-preview" id="edit_billing_preview" aria-live="polite"></div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" data-close-dialog="editLicenseDialog">Cancelar</button>
            <button type="submit" class="btn-primary-action">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                <span>Actualizar Licencia</span>
            </button>
        </div>
    </form>
</dialog>

{{-- ============================================================
     3. DELETE LICENSE DIALOG
     ============================================================ --}}
<dialog class="mik-dialog mik-dialog--sm" id="deleteLicenseDialog" closedby="any" aria-labelledby="deleteLicenseTitle" style="text-align:center;">
    <div style="font-size:48px; color:#ff5252; margin-bottom:12px;">
        <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
    </div>

    <h1 class="modal-title" id="deleteLicenseTitle" style="margin-bottom:10px;">¿Eliminar Licencia?</h1>

    <p style="color:var(--silver-light); font-size:14px; line-height:1.6; margin-bottom:24px;">
        ¿Estás seguro de que deseas eliminar la licencia para
        <strong id="deleteLicenseUrl" style="color:var(--white); word-break:break-all;"></strong>?
        Esta acción no se puede deshacer.
    </p>

    <form id="deleteLicenseForm" method="POST">
        @csrf
        @method('DELETE')

        <div style="display:flex; justify-content:center; gap:12px;">
            <button type="button" class="btn-secondary" data-close-dialog="deleteLicenseDialog" style="flex:1;" autofocus>Cancelar</button>
            <button type="submit" class="btn-danger-action" style="flex:1;">Eliminar</button>
        </div>
    </form>
</dialog>

{{-- ============================================================
     4. CONTRACT PDF UPLOAD DIALOG
     ============================================================ --}}
<dialog class="mik-dialog" id="contractUploadDialog" closedby="any" aria-labelledby="contractUploadTitle">
    <div class="modal-header">
        <h1 class="modal-title" id="contractUploadTitle" style="display:flex; align-items:center; gap:9px;">
            <i class="bi bi-file-earmark-arrow-up-fill" style="color:var(--salmon-light);" aria-hidden="true"></i>
            <span>Subir Contrato (PDF)</span>
        </h1>
        <button type="button" class="modal-close" data-close-dialog="contractUploadDialog" aria-label="Cerrar">&times;</button>
    </div>

    <div style="padding:10px 14px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; margin-bottom:16px;">
        <div id="contractUploadClient" style="font-weight:700; font-size:14px; color:var(--white);"></div>
        <div id="contractUploadUrl" style="font-size:12px; color:#9dc5ff; word-break:break-all; margin-top:2px;"></div>
    </div>

    <div id="contractUploadError" class="sys-feedback failure" role="alert" style="display:none; margin-bottom:14px;"></div>

    <form id="contractUploadForm" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="form-group">
            <label for="contract_pdf_input" class="form-label">Archivo de Contrato en PDF *</label>
            <label for="contract_pdf_input" class="lic-pdf-dropzone" id="contractDropzone">
                <i class="bi bi-file-earmark-pdf" style="font-size:32px; color:var(--salmon-light);" aria-hidden="true"></i>
                <span style="font-weight:600; font-size:14px; color:var(--white);">Selecciona o arrastra el archivo PDF aquí</span>
                <span id="contractFileHint" class="lic-field-hint">Solo formato PDF (.pdf) · Máximo 10 MB</span>
                <input
                    type="file"
                    name="contract_pdf"
                    id="contract_pdf_input"
                    accept="application/pdf,.pdf"
                    aria-describedby="contractFileHint"
                    required
                    autofocus
                >
            </label>
            <div id="contractSelectedFileInfo" class="lic-field-hint" style="margin-top:6px; color:#81c784; display:none;"></div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" data-close-dialog="contractUploadDialog">Cancelar</button>
            <button type="submit" class="btn-primary-action" id="btnSubmitContractUpload">
                <i class="bi bi-cloud-arrow-up-fill" aria-hidden="true"></i>
                <span>Guardar y Ver Contrato</span>
            </button>
        </div>
    </form>
</dialog>

{{-- ============================================================
     5. CONTRACT PDF VIEWER DIALOG
     ============================================================ --}}
<dialog class="mik-dialog mik-dialog--wide" id="contractViewerDialog" closedby="any" aria-labelledby="contractViewerTitle">
    <div class="modal-header" style="margin-bottom:10px;">
        <div>
            <h1 class="modal-title" id="contractViewerTitle" style="display:flex; align-items:center; gap:9px;">
                <i class="bi bi-file-earmark-pdf-fill" style="color:#64b5f6;" aria-hidden="true"></i>
                <span>Contrato de Licencia</span>
            </h1>
            <div id="contractViewerSubtitle" style="font-size:12px; color:var(--silver-light); margin-top:4px;"></div>
        </div>
        <button type="button" class="modal-close" data-close-dialog="contractViewerDialog" aria-label="Cerrar">&times;</button>
    </div>

    <div class="lic-pdf-toolbar">
        <div id="contractViewerMeta" style="font-size:12px; color:rgba(255,255,255,0.7); display:flex; align-items:center; gap:8px;">
            <i class="bi bi-paperclip" aria-hidden="true"></i>
            <span id="contractViewerFilename" style="font-weight:600; color:var(--white);"></span>
            <span id="contractViewerDate" style="opacity:0.7;"></span>
        </div>

        <div class="lic-pdf-toolbar-actions">
            <button type="button" class="btn-secondary" id="btnReplaceContractFromViewer" style="padding:8px 12px; font-size:12px;">
                <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Reemplazar PDF
            </button>
            <a id="contractDownloadLink" href="#" class="btn-secondary" style="padding:8px 12px; font-size:12px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                <i class="bi bi-download" aria-hidden="true"></i> Descargar
            </a>
            <a id="contractExternalLink" href="#" target="_blank" rel="noopener noreferrer" class="btn-secondary" style="padding:8px 12px; font-size:12px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Pestaña
            </a>
            <button type="button" class="btn-danger-action" id="btnDeleteContractFromViewer" style="padding:8px 12px; font-size:12px;">
                <i class="bi bi-trash-fill" aria-hidden="true"></i> Eliminar PDF
            </button>
        </div>
    </div>

    <div class="lic-pdf-frame-wrap">
        <iframe id="contractPdfFrame" class="lic-pdf-frame" title="Vista previa del contrato PDF" src="about:blank"></iframe>
    </div>
</dialog>

{{-- ============================================================
     6. DELETE CONTRACT CONFIRMATION DIALOG
     ============================================================ --}}
<dialog class="mik-dialog mik-dialog--sm" id="deleteContractDialog" closedby="any" aria-labelledby="deleteContractTitle" style="text-align:center;">
    <div style="font-size:44px; color:#ff5252; margin-bottom:10px;">
        <i class="bi bi-file-earmark-x-fill" aria-hidden="true"></i>
    </div>
    <h1 class="modal-title" id="deleteContractTitle" style="margin-bottom:10px;">¿Eliminar Contrato PDF?</h1>
    <p style="color:var(--silver-light); font-size:14px; line-height:1.55; margin-bottom:22px;">
        Se eliminará el archivo <strong id="deleteContractFilename" style="color:var(--white); word-break:break-all;"></strong> de esta licencia.
    </p>
    <div id="deleteContractError" class="sys-feedback failure" role="alert" style="display:none; margin-bottom:14px;"></div>
    <div style="display:flex; justify-content:center; gap:12px;">
        <button type="button" class="btn-secondary" data-close-dialog="deleteContractDialog" style="flex:1;" autofocus>Cancelar</button>
        <button type="button" class="btn-danger-action" id="btnConfirmDeleteContract" style="flex:1;">Eliminar PDF</button>
    </div>
</dialog>

@if(Auth::user()->isSuperAdmin())
{{-- ============================================================
     7. SYSTEM CONTROL & MODULES DIALOG (Solo Superadministrador)
     ============================================================ --}}
<dialog class="mik-dialog" id="systemControlDialog" closedby="any" aria-labelledby="systemControlTitle">
    <div class="modal-header">
        <h1 class="modal-title" id="systemControlTitle" style="display:flex; align-items:center; gap:9px;">
            <i class="bi bi-shield-lock-fill" style="color:#D4855E; font-size:18px;" aria-hidden="true"></i>
            <span>Control de Licencia y Módulos</span>
        </h1>
        <button type="button" class="modal-close" data-close-dialog="systemControlDialog" aria-label="Cerrar">&times;</button>
    </div>

    <div style="display:flex; align-items:center; gap:8px; padding:10px 14px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:9px; margin-bottom:12px;">
        <i class="bi bi-link-45deg" style="color:rgba(255,255,255,0.35); font-size:15px; flex-shrink:0;" aria-hidden="true"></i>
        <span id="sysControlUrlText" style="font-size:12px; color:rgba(255,255,255,0.75); word-break:break-all; font-family:monospace;"></span>
    </div>

    <div class="sys-feedback" id="sysFeedback" role="status" style="display:none; margin-bottom:14px;"></div>

    <div style="margin-bottom: 18px;">
        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: var(--silver); margin-bottom: 8px; font-weight: 600;">
            1. Estado Global del Sistema (Bloqueo / Licencia)
        </div>
        <div class="sys-status-card loading" id="sysStatusCard">
            <div class="sys-status-icon" id="sysStatusIcon">
                <span class="sys-spinner"></span>
            </div>
            <div>
                <div class="sys-status-label" id="sysStatusLabel">Verificando estado…</div>
                <div class="sys-status-sub" id="sysStatusSub">Conectando con el sistema remoto</div>
            </div>
        </div>

        <div id="sysActionRow" style="display:none; gap:10px; margin-top:12px;">
            <button type="button" class="btn-sys-enable" id="btnSysEnable">
                <i class="bi bi-play-circle-fill" aria-hidden="true"></i>
                Habilitar Sistema
            </button>
            <button type="button" class="btn-sys-disable" id="btnSysDisable">
                <i class="bi bi-pause-circle-fill" aria-hidden="true"></i>
                Deshabilitar Sistema
            </button>
        </div>
    </div>

    <div style="margin-top: 18px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: var(--silver); font-weight: 600; display: flex; align-items: center; gap: 6px;">
                <i class="bi bi-boxes" style="color: #D4855E;" aria-hidden="true"></i>
                2. Módulos del Sistema Remoto
            </div>
            <div id="modulesSpinner" style="display: none; font-size: 11px; color: var(--silver-light);">
                <span class="sys-spinner" style="width: 12px; height: 12px; border-width: 2px; display: inline-block; vertical-align: middle;"></span> Consultando...
            </div>
        </div>

        <div id="sysModulesContainer" style="display: flex; flex-direction: column; gap: 10px;">
            <div style="padding: 14px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 10px; font-size: 12px; color: var(--silver); text-align: center;">
                Consultando módulos del sistema…
            </div>
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:18px; padding-top:14px; border-top:1px solid rgba(255,255,255,0.07);">
        <button type="button" class="btn-sys-refresh" id="btnSysRefresh" disabled>
            <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
            Actualizar todo
        </button>
        <button type="button" class="btn-secondary" data-close-dialog="systemControlDialog" style="min-width:90px;">Cerrar</button>
    </div>
</dialog>
@endif

{{-- ==============================================================
     8. PAYMENT SELECTOR DIALOG (Conceptos con montos claros)
     ============================================================== --}}
<dialog class="mik-dialog mik-dialog--sm" id="licensePaymentSelectorDialog" closedby="any" aria-labelledby="paymentSelectorTitle">
    <div class="modal-header">
        <h1 class="modal-title" id="paymentSelectorTitle" style="display:flex; align-items:center; gap:8px;">
            <i class="bi bi-cash-stack" style="color:#48c78e;" aria-hidden="true"></i>
            <span>¿Qué pago vas a registrar?</span>
        </h1>
        <button type="button" class="modal-close" data-close-dialog="licensePaymentSelectorDialog" aria-label="Cerrar">&times;</button>
    </div>
    <p id="paymentSelectorUrl" style="color:var(--silver-light); font-size:13px; margin:-6px 0 16px; word-break:break-all;"></p>

    <div style="display:flex; flex-direction:column; gap:10px;">
        <button type="button" id="btnPayServiceChoice" class="lic-payment-choice is-service" autofocus>
            <div>
                <div id="payChoiceServiceTitle" style="font-weight:700; font-size:14px;">Pago de Servicio</div>
                <div id="payChoiceServiceSub" style="font-size:12px; color:rgba(255,255,255,0.68); margin-top:2px;"></div>
            </div>
            <strong id="payChoiceServiceAmount" style="font-size:15px; color:#90caf9;">$0</strong>
        </button>

        <button type="button" id="btnPayAnnualChoice" class="lic-payment-choice is-annual">
            <div>
                <div style="font-weight:700; font-size:14px;">Pago de Anualidad</div>
                <div id="payChoiceAnnualSub" style="font-size:12px; color:rgba(255,255,255,0.68); margin-top:2px;"></div>
            </div>
            <strong id="payChoiceAnnualAmount" style="font-size:15px; color:#ffcc80;">$0</strong>
        </button>

        <button type="button" id="btnPayTotalChoice" class="lic-payment-choice is-recommended">
            <div>
                <div style="font-weight:700; font-size:14px;">Pago Total (Servicio + Anualidad)</div>
                <div id="payChoiceTotalSub" style="font-size:12px; color:#d1fae5; margin-top:2px;">Renueva ambos conceptos a la vez</div>
            </div>
            <strong id="payChoiceTotalAmount" style="font-size:15px; color:#6ee7b7;">$0</strong>
        </button>
    </div>
</dialog>

{{-- ==============================================================
     9. REGISTER LICENSE PAYMENT DIALOG
     ============================================================== --}}
<dialog class="mik-dialog" id="registerLicensePaymentDialog" closedby="any" aria-labelledby="registerPaymentTitle">
    <div class="modal-header">
        <h1 class="modal-title" id="registerPaymentTitle" style="display:flex; align-items:center; gap:8px;">
            <i class="bi bi-receipt" style="color:var(--salmon);" aria-hidden="true"></i>
            <span>Registrar Pago de Licencia</span>
        </h1>
        <button type="button" class="modal-close" data-close-dialog="registerLicensePaymentDialog" aria-label="Cerrar">&times;</button>
    </div>

    <p id="licensePaymentDesc" style="color:var(--silver-light); font-size:13px; margin:-4px 0 14px;">
        Registrar ingreso de dinero por licencia.
    </p>

    <div id="licensePaymentTargetNote" class="modal-note" style="margin-bottom:16px;">
        <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
        <span>Elige si este pago entra a una cuenta bancaria o si se abonará directamente a un crédito sin mover saldos bancarios.</span>
    </div>

    <form action="{{ route('payments.store') }}" method="POST" autocomplete="off">
        @csrf
        <input type="hidden" name="client_id" id="lp_client_id" value="{{ old('client_id') }}">
        <input type="hidden" name="license_id" id="lp_license_id" value="{{ old('license_id') }}">
        <input type="hidden" name="license_payment_type" id="lp_type" value="{{ old('license_payment_type') }}">
        <input type="hidden" name="method" value="transferencia">
        <input type="hidden" name="redirect_to" value="licenses">

        <div class="lic-form-grid">
            <div class="form-group">
                <label for="lp_amount" class="form-label">Monto a Recibir ($) *</label>
                <input type="number" name="amount" id="lp_amount" class="form-input" step="0.01" min="0.01" inputmode="decimal" value="{{ old('amount') }}" required autofocus>
            </div>
            <div class="form-group">
                <label for="lp_payment_target" class="form-label">Aplicar Pago a *</label>
                <select name="payment_target" id="lp_payment_target" class="form-input" required>
                    <option value="bank_account" {{ old('payment_target', 'bank_account') === 'bank_account' ? 'selected' : '' }}>Cuenta bancaria</option>
                    <option value="credit" {{ old('payment_target') === 'credit' ? 'selected' : '' }}>Abonar a crédito</option>
                </select>
            </div>
        </div>

        @php $bankAccounts = \App\Models\BankAccount::where('is_active', true)->get(); @endphp
        <div id="lpBankAccountGroup" class="form-group">
            <label for="lp_bank_account_id" class="form-label">Cuenta de Destino *</label>
            <select name="bank_account_id" id="lp_bank_account_id" class="form-input">
                <option value="">Seleccionar cuenta</option>
                @foreach($bankAccounts as $acc)
                    <option value="{{ $acc->id }}" {{ (string) old('bank_account_id') === (string) $acc->id ? 'selected' : '' }}>
                        {{ $acc->name }} (${{ number_format($acc->current_balance, 0) }})
                    </option>
                @endforeach
            </select>
        </div>

        <div id="lpCreditGroup" class="form-group" style="display:none;">
            <label for="lp_credit_id" class="form-label">Crédito a Abonar *</label>
            <select name="credit_id" id="lp_credit_id" class="form-input" aria-describedby="lpCreditHelp">
                <option value="">Seleccionar crédito</option>
            </select>
            <div id="lpCreditHelp" class="lic-field-hint">
                Solo se muestran créditos activos con saldo pendiente del cliente de esta licencia.
            </div>
        </div>

        <div class="form-group">
            <label for="lp_payment_date" class="form-label">Fecha del Pago *</label>
            <input type="date" name="payment_date" id="lp_payment_date" class="form-input" value="{{ old('payment_date', date('Y-m-d')) }}" required>
        </div>

        <div class="form-group">
            <label for="lp_notes" class="form-label">Referencia / Notas</label>
            <input type="text" name="notes" id="lp_notes" class="form-input" value="{{ old('notes') }}" placeholder="Ej. Pago anualidad / semestre">
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" data-close-dialog="registerLicensePaymentDialog">Cancelar</button>
            <button type="submit" class="btn-primary-action" id="lpSubmitButton">Confirmar Ingreso</button>
        </div>
    </form>
</dialog>

{{-- ==============================================================
     10. SHARED LICENSE PREFERENCES DIALOG (Usuario SaaS vinculado)
     ============================================================== --}}
<dialog class="mik-dialog" id="sharedLicenseDialog" closedby="any" aria-labelledby="sharedLicenseTitle">
    <div class="modal-header">
        <h1 class="modal-title" id="sharedLicenseTitle" style="display:flex; align-items:center; gap:9px;">
            <i class="bi bi-sliders" style="color:var(--salmon-light);" aria-hidden="true"></i>
            <span>Configurar Licencia Compartida</span>
        </h1>
        <button type="button" class="modal-close" data-close-dialog="sharedLicenseDialog" aria-label="Cerrar">&times;</button>
    </div>

    <div style="padding:10px 14px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; margin-bottom:14px;">
        <div style="font-size:11px; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.4px;">Sitio de la licencia</div>
        <div id="sharedLicenseUrl" style="font-size:13px; color:#9dc5ff; word-break:break-all; margin-top:2px;"></div>
    </div>

    <div class="modal-note" id="sharedFreeNote" style="display:none;">
        <i class="bi bi-gift-fill" aria-hidden="true"></i>
        <span>Esta es una licencia <strong>Gratuita</strong>. La tarifa se mantendrá en <strong>$0.00</strong> automáticamente.</span>
    </div>

    <form id="sharedLicenseForm" method="POST" autocomplete="off">
        @csrf
        @method('PATCH')

        <div class="form-group">
            <label for="shared_custom_client_name" class="form-label">Nombre del Cliente (solo para tu vista)</label>
            <input
                type="text"
                name="custom_client_name"
                id="shared_custom_client_name"
                class="form-input"
                placeholder="Ej. Nombre de tu cliente final"
                aria-describedby="shared_client_name_hint"
                autofocus
            >
            <span id="shared_client_name_hint" class="lic-field-hint">
                Si lo cambias, solo cambia en tu panel. El administrador seguirá viendo su nombre original (<span id="sharedOriginalClientName"></span>).
            </span>
        </div>

        <div class="modal-note" style="margin-bottom:14px;">
            <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
            <span>Los cambios en modalidad, valores y fechas de cobro se reflejan automáticamente para ambos (administrador y tú).</span>
        </div>

        <div class="form-group">
            <label for="shared_billing_cycle" class="form-label">Modalidad / Ciclo de Facturación *</label>
            <select name="billing_cycle" id="shared_billing_cycle" required>
                <option value="mensual">Mensual (cada mes)</option>
                <option value="trimestral">Trimestral (cada 3 meses)</option>
                <option value="semestral">Semestral (cada 6 meses)</option>
                <option value="anual">Anual (Servicio + Anualidad aparte)</option>
                <option value="anual_total">Pago Anual Total (Valor único todo incluido)</option>
            </select>
        </div>

        <div class="lic-form-grid">
            <div class="form-group">
                <label for="shared_monthly_fee" id="shared_fee_label" class="form-label">Tarifa Mensual ($) *</label>
                <input
                    type="number"
                    name="monthly_fee"
                    id="shared_monthly_fee"
                    class="form-input"
                    step="0.01"
                    min="0"
                    inputmode="decimal"
                    aria-describedby="shared_fee_hint"
                    required
                >
                <span id="shared_fee_hint" class="lic-field-hint"></span>
                <button type="button" id="shared_annual_suggestion_btn" class="lic-suggestion-btn" style="display:none;"></button>
            </div>

            <div class="form-group">
                <label for="shared_next_billing_date" id="shared_date_label" class="form-label">Próx. Cobro de Servicio *</label>
                <input type="date" name="next_billing_date" id="shared_next_billing_date" class="form-input" required>
            </div>
        </div>

        <fieldset class="lic-annual-section" id="shared_annual_section">
            <legend class="lic-annual-section-legend">Anualidad / Instalación (Opcional)</legend>
            <div class="lic-form-grid">
                <div class="form-group">
                    <label for="shared_setup_fee" class="form-label">Valor Anualidad ($)</label>
                    <input
                        type="number"
                        name="setup_fee"
                        id="shared_setup_fee"
                        class="form-input"
                        step="0.01"
                        min="0"
                        inputmode="decimal"
                        aria-describedby="shared_setup_hint"
                        required
                    >
                    <span id="shared_setup_hint" class="lic-field-hint">Déjalo en 0 si no cobra anualidad aparte.</span>
                </div>
                <div class="form-group">
                    <label for="shared_next_setup_billing_date" class="form-label">Próx. Cobro Anualidad</label>
                    <input type="date" name="next_setup_billing_date" id="shared_next_setup_billing_date" class="form-input">
                </div>
            </div>
        </fieldset>

        <div class="lic-live-preview" id="shared_billing_preview" aria-live="polite"></div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" data-close-dialog="sharedLicenseDialog">Cancelar</button>
            <button type="submit" class="btn-primary-action">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                <span>Guardar Cambios</span>
            </button>
        </div>
    </form>
</dialog>

@php
    $oldPaymentLicense = old('license_id') ? \App\Models\License::find(old('license_id')) : null;
@endphp

<script>
document.addEventListener('DOMContentLoaded', function () {
    const creditsByClient = @json($creditsByClient);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const formatMoney = (value, decimals = 2) => new Intl.NumberFormat('es-CO', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(Number(value || 0));

    // ── Light-dismiss fallback for browsers without <dialog closedby> ──
    if (!('closedBy' in HTMLDialogElement.prototype)) {
        const isOutside = (e, dialog) => {
            const r = dialog.getBoundingClientRect();
            return e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
        };
        document.querySelectorAll('dialog[closedby="any"]').forEach((dialog) => {
            let pressedOutside = false;
            dialog.addEventListener('pointerdown', (e) => { pressedOutside = isOutside(e, dialog); });
            dialog.addEventListener('pointerup', (e) => {
                if (pressedOutside && isOutside(e, dialog)) dialog.close();
            });
        });
    }

    // ── Generic close buttons for all <dialog> elements ──
    document.querySelectorAll('[data-close-dialog]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.closeDialog);
            if (target && target.open) target.close();
        });
    });

    // Clear PDF iframe on close so memory / stale content is released
    const contractViewerDialog = document.getElementById('contractViewerDialog');
    const contractPdfFrame = document.getElementById('contractPdfFrame');
    if (contractViewerDialog && contractPdfFrame) {
        contractViewerDialog.addEventListener('close', () => {
            contractPdfFrame.src = 'about:blank';
        });
    }

    // ── Toast notifications ──
    function showLicenseToast(type, message) {
        let toast = document.getElementById('licenseToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'licenseToast';
            toast.className = 'license-floating-toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            document.body.appendChild(toast);
        }
        const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
        toast.className = `license-floating-toast ${type}`;
        toast.innerHTML = `<i class="bi ${icon}" aria-hidden="true"></i><span></span>`;
        toast.querySelector('span').textContent = message;
        toast.style.display = 'flex';
        if (window._toastTimeout) clearTimeout(window._toastTimeout);
        window._toastTimeout = setTimeout(() => { toast.style.display = 'none'; }, 4200);
    }

    // ── Create / Edit License Dynamic Billing Form Behavior ──
    function setupLicenseFormBehavior(prefix) {
        const cycleEl = document.getElementById(`${prefix}_billing_cycle`);
        const feeEl = document.getElementById(`${prefix}_monthly_fee`);
        const feeLabelEl = document.getElementById(`${prefix}_fee_label`);
        const feeHintEl = document.getElementById(`${prefix}_fee_hint`);
        const dateLabelEl = document.getElementById(`${prefix}_date_label`);
        const dateEl = document.getElementById(`${prefix}_next_billing_date`);
        const annualSection = document.getElementById(`${prefix}_annual_section`);
        const setupFeeEl = document.getElementById(`${prefix}_setup_fee`);
        const setupDateEl = document.getElementById(`${prefix}_next_setup_billing_date`);
        const previewEl = document.getElementById(`${prefix}_billing_preview`);
        const suggestionBtn = document.getElementById(`${prefix}_annual_suggestion_btn`);

        if (!cycleEl || !feeEl) return null;

        let originalStandardAnnualEquivalent = 0;

        function updateUI() {
            const cycle = cycleEl.value;
            const isAnnualTotal = cycle === 'anual_total';
            const feeVal = parseFloat(feeEl.value) || 0;
            const setupVal = parseFloat(setupFeeEl?.value) || 0;

            const multipliers = { mensual: 1, trimestral: 3, semestral: 6, anual: 12, anual_total: 1 };
            const cycleNames = {
                mensual: 'al mes',
                trimestral: 'por trimestre (cada 3 meses)',
                semestral: 'por semestre (cada 6 meses)',
                anual: 'por servicio anual (12 meses)',
                anual_total: 'anual total todo incluido',
            };
            const paymentsPerYear = { mensual: 12, trimestral: 4, semestral: 2, anual: 1, anual_total: 1 };

            if (isAnnualTotal) {
                feeLabelEl.textContent = 'Valor Total Anual ($) *';
                feeEl.placeholder = '900000';
                feeHintEl.textContent = 'Valor único que se cobrará cada año (ej. 900000). No se multiplica por 12 ni tiene anualidad adicional.';
                dateLabelEl.textContent = 'Próx. Renovación Anual *';

                if (annualSection) annualSection.hidden = true;
                if (setupFeeEl) { setupFeeEl.disabled = true; setupFeeEl.required = false; }
                if (setupDateEl) { setupDateEl.disabled = true; }

                if (suggestionBtn && originalStandardAnnualEquivalent > 0 && Math.abs(feeVal - originalStandardAnnualEquivalent) > 0.01) {
                    suggestionBtn.style.display = 'inline-flex';
                    suggestionBtn.innerHTML = `<i class="bi bi-magic" aria-hidden="true"></i> Aplicar equivalente anual actual ($${formatMoney(originalStandardAnnualEquivalent, 0)})`;
                } else if (suggestionBtn) {
                    suggestionBtn.style.display = 'none';
                }

                previewEl.innerHTML = `
                    <strong>Resumen Pago Anual Total:</strong> Se cobrarán <strong>$${formatMoney(feeVal)}</strong> una vez al año${dateEl.value ? ` (próx: ${dateEl.value})` : ''}. Sin anualidades adicionales.
                `;
            } else {
                const cycleAmount = feeVal * (multipliers[cycle] || 1);
                feeLabelEl.textContent = 'Tarifa Mensual ($) *';
                feeEl.placeholder = '50000';
                feeHintEl.textContent = feeVal > 0
                    ? `Equivale a $${formatMoney(cycleAmount)} ${cycleNames[cycle] || ''}.`
                    : 'Ingresa la tarifa base mensual (el sistema la multiplica según el ciclo).';
                dateLabelEl.textContent = 'Próx. Cobro de Servicio *';

                if (annualSection) annualSection.hidden = false;
                if (setupFeeEl) { setupFeeEl.disabled = false; setupFeeEl.required = true; }
                if (setupDateEl) { setupDateEl.disabled = false; }
                if (suggestionBtn) { suggestionBtn.style.display = 'none'; }

                const yearlyServiceTotal = cycleAmount * (paymentsPerYear[cycle] || 1);
                const combinedYear = yearlyServiceTotal + setupVal;

                let html = `<strong>Resumen del plan:</strong> Servicio <strong>$${formatMoney(cycleAmount)}</strong> ${cycleNames[cycle] || ''}`;
                if (setupVal > 0) {
                    html += ` + Anualidad <strong>$${formatMoney(setupVal)}</strong> al año (Total anual combinado: <strong>$${formatMoney(combinedYear)}</strong>).`;
                    if (setupDateEl && !setupDateEl.value) {
                        html += ` <span style="color:#fcd34d;">· Recuerda definir la fecha de próxima anualidad.</span>`;
                    }
                } else {
                    html += ` · Sin cobro de anualidad aparte.`;
                }
                previewEl.innerHTML = html;
            }
        }

        cycleEl.addEventListener('change', updateUI);
        feeEl.addEventListener('input', updateUI);
        dateEl?.addEventListener('change', updateUI);
        setupFeeEl?.addEventListener('input', updateUI);
        setupDateEl?.addEventListener('change', updateUI);

        if (suggestionBtn) {
            suggestionBtn.addEventListener('click', () => {
                if (originalStandardAnnualEquivalent > 0) {
                    feeEl.value = originalStandardAnnualEquivalent;
                    updateUI();
                }
            });
        }

        updateUI();

        return {
            refresh: updateUI,
            setOriginalStandardAnnualEquivalent(val) {
                originalStandardAnnualEquivalent = Number(val || 0);
                updateUI();
            }
        };
    }

    const createFormCtrl = setupLicenseFormBehavior('create');
    const editFormCtrl = setupLicenseFormBehavior('edit');
    const sharedFormCtrl = setupLicenseFormBehavior('shared');

    const createDialog = document.getElementById('createLicenseDialog');

    @if(isset($errors) && $errors->any() && !old('license_id') && ($canCreateOwnLicense ?? true))
        createFormCtrl?.refresh();
        createDialog?.showModal();
    @endif

    // ── Edit License Dialog ──
    function openEditLicenseModal(lic) {
        const dialog = document.getElementById('editLicenseDialog');
        const form = document.getElementById('editLicenseForm');
        const freeNote = document.getElementById('editFreeNote');
        const feeInput = document.getElementById('edit_monthly_fee');

        document.getElementById('edit_client_id').value = lic.client_id;
        document.getElementById('edit_url').value = lic.url || '';
        const tokenInput = document.getElementById('edit_block_token');
        if (tokenInput) tokenInput.value = lic.block_token || '';
        document.getElementById('edit_status').value = lic.status || 'activa';
        document.getElementById('edit_billing_cycle').value = lic.billing_cycle || 'mensual';
        document.getElementById('edit_setup_fee').value = lic.setup_fee ?? 0;
        document.getElementById('edit_next_billing_date').value = lic.next_billing_date || '';
        document.getElementById('edit_next_setup_billing_date').value = lic.next_setup_billing_date || '';
        feeInput.value = lic.monthly_fee ?? 0;

        if (lic.is_free) {
            freeNote.style.display = 'flex';
            feeInput.value = '0.00';
            feeInput.readOnly = true;
            feeInput.style.opacity = '0.55';
        } else {
            freeNote.style.display = 'none';
            feeInput.readOnly = false;
            feeInput.style.opacity = '1';
        }

        const equivalent = lic.billing_cycle !== 'anual_total'
            ? ((Number(lic.monthly_fee || 0) * 12) + Number(lic.setup_fee || 0))
            : 0;
        editFormCtrl?.setOriginalStandardAnnualEquivalent(equivalent);

        form.action = `/licenses/${lic.id}`;
        dialog.showModal();
    }

    // ── Delete License Dialog ──
    function openDeleteLicenseModal(lic) {
        const dialog = document.getElementById('deleteLicenseDialog');
        document.getElementById('deleteLicenseUrl').textContent = lic.url;
        document.getElementById('deleteLicenseForm').action = `/licenses/${lic.id}`;
        dialog.showModal();
    }

    // ── Contract Upload & PDF Viewer Dialogs ──
    const contractUploadDialog = document.getElementById('contractUploadDialog');
    const contractUploadForm = document.getElementById('contractUploadForm');
    const contractPdfInput = document.getElementById('contract_pdf_input');
    const contractDropzone = document.getElementById('contractDropzone');
    const contractSelectedFileInfo = document.getElementById('contractSelectedFileInfo');
    const contractUploadError = document.getElementById('contractUploadError');
    const btnSubmitContractUpload = document.getElementById('btnSubmitContractUpload');
    let activeContractLicense = null;

    function openContractUploadModal(lic) {
        activeContractLicense = lic;
        document.getElementById('contractUploadClient').textContent = lic.client_name || 'Cliente';
        document.getElementById('contractUploadUrl').textContent = lic.url || '';
        contractUploadForm.action = lic.contract_upload_url;
        contractPdfInput.value = '';
        contractSelectedFileInfo.style.display = 'none';
        contractSelectedFileInfo.textContent = '';
        contractUploadError.style.display = 'none';
        contractUploadError.textContent = '';
        btnSubmitContractUpload.disabled = false;
        btnSubmitContractUpload.innerHTML = '<i class="bi bi-cloud-arrow-up-fill" aria-hidden="true"></i><span>Guardar y Ver Contrato</span>';
        contractUploadDialog.showModal();
    }

    function openContractViewerModal(lic) {
        activeContractLicense = lic;
        document.getElementById('contractViewerSubtitle').textContent = `${lic.client_name || 'Cliente'} — ${lic.url || ''}`;
        document.getElementById('contractViewerFilename').textContent = lic.contract_name || 'Contrato.pdf';
        document.getElementById('contractViewerDate').textContent = lic.contract_uploaded_at ? `(${lic.contract_uploaded_at})` : '';
        document.getElementById('contractDownloadLink').href = lic.contract_download_url || `${lic.contract_url}?download=1`;
        document.getElementById('contractExternalLink').href = lic.contract_url;

        const btnReplace = document.getElementById('btnReplaceContractFromViewer');
        const btnDelete = document.getElementById('btnDeleteContractFromViewer');
        if (btnReplace) btnReplace.hidden = !lic.is_owned;
        if (btnDelete) btnDelete.hidden = !lic.is_owned;

        contractPdfFrame.src = `${lic.contract_url}?v=${Date.now()}#view=FitH`;
        contractViewerDialog.showModal();
    }

    function openSharedLicenseModal(lic) {
        const dialog = document.getElementById('sharedLicenseDialog');
        const form = document.getElementById('sharedLicenseForm');
        const freeNote = document.getElementById('sharedFreeNote');
        const feeInput = document.getElementById('shared_monthly_fee');

        document.getElementById('sharedLicenseUrl').textContent = lic.url || '';
        document.getElementById('sharedOriginalClientName').textContent = lic.original_client_name || '';
        document.getElementById('shared_custom_client_name').value = lic.custom_client_name || lic.client_name || '';
        document.getElementById('shared_billing_cycle').value = lic.billing_cycle || 'mensual';
        document.getElementById('shared_setup_fee').value = lic.setup_fee ?? 0;
        document.getElementById('shared_next_billing_date').value = lic.next_billing_date || '';
        document.getElementById('shared_next_setup_billing_date').value = lic.next_setup_billing_date || '';
        feeInput.value = lic.monthly_fee ?? 0;

        if (lic.is_free) {
            freeNote.style.display = 'flex';
            feeInput.value = '0.00';
            feeInput.readOnly = true;
            feeInput.style.opacity = '0.55';
        } else {
            freeNote.style.display = 'none';
            feeInput.readOnly = false;
            feeInput.style.opacity = '1';
        }

        const equivalent = lic.billing_cycle !== 'anual_total'
            ? ((Number(lic.monthly_fee || 0) * 12) + Number(lic.setup_fee || 0))
            : 0;
        sharedFormCtrl?.setOriginalStandardAnnualEquivalent(equivalent);

        form.action = lic.shared_preferences_url;
        dialog.showModal();
    }

    function updateSelectedFileUI() {
        const file = contractPdfInput.files?.[0];
        if (!file) {
            contractSelectedFileInfo.style.display = 'none';
            return;
        }
        const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
        contractSelectedFileInfo.textContent = `Archivo listo: ${file.name} (${sizeMb} MB)`;
        contractSelectedFileInfo.style.display = 'block';
        contractUploadError.style.display = 'none';
    }

    contractPdfInput?.addEventListener('change', updateSelectedFileUI);

    if (contractDropzone && contractPdfInput) {
        ['dragenter', 'dragover'].forEach((evt) => {
            contractDropzone.addEventListener(evt, (e) => {
                e.preventDefault();
                contractDropzone.classList.add('is-dragover');
            });
        });
        ['dragleave', 'drop'].forEach((evt) => {
            contractDropzone.addEventListener(evt, (e) => {
                e.preventDefault();
                contractDropzone.classList.remove('is-dragover');
            });
        });
        contractDropzone.addEventListener('drop', (e) => {
            const files = e.dataTransfer?.files;
            if (files && files.length > 0) {
                contractPdfInput.files = files;
                updateSelectedFileUI();
            }
        });
    }

    contractUploadForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!activeContractLicense) return;

        const file = contractPdfInput.files?.[0];
        if (!file) {
            contractUploadError.style.display = 'flex';
            contractUploadError.innerHTML = '<i class="bi bi-x-circle-fill" aria-hidden="true"></i><span>Selecciona un archivo PDF primero.</span>';
            return;
        }

        btnSubmitContractUpload.disabled = true;
        btnSubmitContractUpload.innerHTML = '<span class="sys-spinner mini"></span><span>Subiendo PDF...</span>';
        contractUploadError.style.display = 'none';

        try {
            const formData = new FormData(contractUploadForm);
            const res = await fetch(activeContractLicense.contract_upload_url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
                const errMsg = data?.errors?.contract_pdf?.[0] || data?.message || 'No se pudo subir el archivo PDF.';
                contractUploadError.style.display = 'flex';
                contractUploadError.innerHTML = '<i class="bi bi-x-circle-fill" aria-hidden="true"></i><span></span>';
                contractUploadError.querySelector('span').textContent = errMsg;
                btnSubmitContractUpload.disabled = false;
                btnSubmitContractUpload.innerHTML = '<i class="bi bi-cloud-arrow-up-fill" aria-hidden="true"></i><span>Guardar y Ver Contrato</span>';
                return;
            }

            const updatedLic = {
                ...activeContractLicense,
                has_contract: true,
                contract_name: data.contract_name,
                contract_url: data.contract_url,
                contract_download_url: `${data.contract_url}?download=1`,
                contract_uploaded_at: data.uploaded_at
            };

            contractUploadDialog.close();
            showLicenseToast('success', data.message || 'Contrato PDF cargado correctamente.');
            await fetchAndSwapTable(window.location.href, false);
            openContractViewerModal(updatedLic);
        } catch (err) {
            contractUploadError.style.display = 'flex';
            contractUploadError.innerHTML = '<i class="bi bi-x-circle-fill" aria-hidden="true"></i><span>Error de red al subir el contrato PDF.</span>';
            btnSubmitContractUpload.disabled = false;
            btnSubmitContractUpload.innerHTML = '<i class="bi bi-cloud-arrow-up-fill" aria-hidden="true"></i><span>Guardar y Ver Contrato</span>';
        }
    });

    document.getElementById('btnReplaceContractFromViewer')?.addEventListener('click', () => {
        if (!activeContractLicense) return;
        const target = { ...activeContractLicense };
        contractViewerDialog.close();
        openContractUploadModal(target);
    });

    const deleteContractDialog = document.getElementById('deleteContractDialog');
    const deleteContractError = document.getElementById('deleteContractError');
    const btnConfirmDeleteContract = document.getElementById('btnConfirmDeleteContract');

    document.getElementById('btnDeleteContractFromViewer')?.addEventListener('click', () => {
        if (!activeContractLicense) return;
        document.getElementById('deleteContractFilename').textContent = activeContractLicense.contract_name || 'contrato.pdf';
        deleteContractError.style.display = 'none';
        btnConfirmDeleteContract.disabled = false;
        deleteContractDialog.showModal();
    });

    btnConfirmDeleteContract?.addEventListener('click', async () => {
        if (!activeContractLicense) return;
        btnConfirmDeleteContract.disabled = true;
        deleteContractError.style.display = 'none';

        try {
            const res = await fetch(activeContractLicense.contract_delete_url, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                deleteContractError.style.display = 'flex';
                deleteContractError.innerHTML = '<i class="bi bi-x-circle-fill" aria-hidden="true"></i><span></span>';
                deleteContractError.querySelector('span').textContent = data?.message || 'No se pudo eliminar el contrato.';
                btnConfirmDeleteContract.disabled = false;
                return;
            }
            deleteContractDialog.close();
            if (contractViewerDialog.open) contractViewerDialog.close();
            showLicenseToast('success', data.message || 'Contrato PDF eliminado.');
            await fetchAndSwapTable(window.location.href, false);
        } catch (err) {
            deleteContractError.style.display = 'flex';
            deleteContractError.innerHTML = '<i class="bi bi-x-circle-fill" aria-hidden="true"></i><span>Error de red al eliminar el contrato.</span>';
            btnConfirmDeleteContract.disabled = false;
        }
    });

    // ── Payment Selector & Payment Form ──
    const paymentSelectorDialog = document.getElementById('licensePaymentSelectorDialog');
    const registerPaymentDialog = document.getElementById('registerLicensePaymentDialog');
    const paymentTarget = document.getElementById('lp_payment_target');
    const bankAccountGroup = document.getElementById('lpBankAccountGroup');
    const creditGroup = document.getElementById('lpCreditGroup');
    const bankAccountSelect = document.getElementById('lp_bank_account_id');
    const creditSelect = document.getElementById('lp_credit_id');
    const paymentDesc = document.getElementById('licensePaymentDesc');
    const paymentNote = document.getElementById('licensePaymentTargetNote');
    const submitButton = document.getElementById('lpSubmitButton');
    let currentLicenseForPayment = null;

    function renderCreditOptions(clientId, selectedCreditId = '') {
        const credits = creditsByClient[String(clientId)] || creditsByClient[clientId] || [];
        const options = ['<option value="">Seleccionar crédito</option>'];
        if (credits.length === 0) {
            options.push('<option value="" disabled>Sin créditos activos disponibles</option>');
        } else {
            credits.forEach((credit) => {
                const selected = String(selectedCreditId) === String(credit.id) ? 'selected' : '';
                const label = `${credit.description} - ${credit.creditor_name} (Saldo: $${formatMoney(credit.balance)})`;
                options.push(`<option value="${credit.id}" ${selected}>${label}</option>`);
            });
        }
        creditSelect.innerHTML = options.join('');
    }

    function syncPaymentTarget() {
        const target = paymentTarget.value || 'bank_account';
        const isCredit = target === 'credit';

        bankAccountGroup.style.display = isCredit ? 'none' : 'block';
        creditGroup.style.display = isCredit ? 'block' : 'none';
        bankAccountSelect.required = !isCredit;
        creditSelect.required = isCredit;

        if (isCredit) {
            bankAccountSelect.value = '';
            submitButton.textContent = 'Confirmar Abono';
            paymentNote.innerHTML = '<i class="bi bi-info-circle-fill" aria-hidden="true"></i><span>Este pago se registrará como abono a un crédito y no generará movimiento en tus cuentas bancarias.</span>';
            if (currentLicenseForPayment?.client_id) {
                renderCreditOptions(currentLicenseForPayment.client_id, @json(old('credit_id')));
            }
        } else {
            creditSelect.value = '';
            submitButton.textContent = 'Confirmar Ingreso';
            paymentNote.innerHTML = '<i class="bi bi-info-circle-fill" aria-hidden="true"></i><span>Este pago ingresará a la cuenta bancaria seleccionada y actualizará su saldo.</span>';
        }

        if (currentLicenseForPayment?.url) {
            const currentType = document.getElementById('lp_type').value;
            const typeLabel = {
                instalacion: 'Anualidad',
                total: 'Servicio + Anualidad',
                anual_total: 'Pago Anual Total',
                mensualidad: `Servicio (${currentLicenseForPayment.billing_cycle_label || 'Ciclo'})`
            }[currentType] || 'Servicio';

            paymentDesc.textContent = isCredit
                ? `Aplicar pago de ${typeLabel} de ${currentLicenseForPayment.url} a un crédito del cliente`
                : `Registrar pago de ${typeLabel} para ${currentLicenseForPayment.url}`;
        }
    }

    function openRegisterLicensePaymentForm(license, type) {
        let amount = 0;
        if (type === 'anual_total') {
            amount = Number(license.service_amount || license.monthly_fee || 0);
        } else if (type === 'instalacion') {
            amount = Number(license.annual_fee || license.setup_fee || 0);
        } else if (type === 'total') {
            amount = Number(license.service_amount || 0) + Number(license.annual_fee || license.setup_fee || 0);
        } else {
            amount = Number(license.service_amount || 0);
        }

        const typeLabel = {
            instalacion: 'Anualidad',
            total: 'Servicio + Anualidad',
            anual_total: 'Pago Anual Total',
            mensualidad: `Servicio (${license.billing_cycle_label || 'Ciclo'})`
        }[type] || 'Servicio';

        currentLicenseForPayment = license;
        document.getElementById('lp_client_id').value = license.client_id;
        document.getElementById('lp_license_id').value = license.id;
        document.getElementById('lp_type').value = type;
        document.getElementById('lp_amount').value = amount;
        document.getElementById('lp_notes').value = `Pago ${typeLabel} - ${license.url}`;
        paymentTarget.value = 'bank_account';

        if (bankAccountSelect.options.length > 1 && !bankAccountSelect.value) {
            bankAccountSelect.selectedIndex = 1;
        }

        renderCreditOptions(license.client_id);
        syncPaymentTarget();
        registerPaymentDialog.showModal();
    }

    function openLicensePaymentFlow(license) {
        currentLicenseForPayment = license;

        if (license.billing_cycle === 'anual_total') {
            openRegisterLicensePaymentForm(license, 'anual_total');
            return;
        }

        const annualFee = Number(license.annual_fee || license.setup_fee || 0);
        if (annualFee <= 0) {
            openRegisterLicensePaymentForm(license, 'mensualidad');
            return;
        }

        const serviceAmount = Number(license.service_amount || 0);
        const totalBoth = serviceAmount + annualFee;

        document.getElementById('paymentSelectorUrl').textContent = `${license.client_name} — ${license.url}`;
        document.getElementById('payChoiceServiceTitle').textContent = `Servicio (${license.billing_cycle_label})`;
        document.getElementById('payChoiceServiceSub').textContent = license.service_due
            ? `Vencido / vence hoy (${license.next_billing_date})`
            : `Próximo cobro: ${license.next_billing_date}`;
        document.getElementById('payChoiceServiceAmount').textContent = `$${formatMoney(serviceAmount)}`;

        document.getElementById('payChoiceAnnualSub').textContent = license.annual_due
            ? `Vencida / vence hoy (${license.next_setup_billing_date || 'sin fecha'})`
            : `Próxima anualidad: ${license.next_setup_billing_date || 'sin fecha'}`;
        document.getElementById('payChoiceAnnualAmount').textContent = `$${formatMoney(annualFee)}`;

        document.getElementById('payChoiceTotalAmount').textContent = `$${formatMoney(totalBoth)}`;
        const btnTotal = document.getElementById('btnPayTotalChoice');
        if (license.service_due && license.annual_due) {
            btnTotal.classList.add('is-recommended');
            document.getElementById('payChoiceTotalSub').textContent = 'Recomendado: ambos conceptos están vencidos';
        } else {
            document.getElementById('payChoiceTotalSub').textContent = 'Renueva servicio y anualidad a la vez';
        }

        paymentSelectorDialog.showModal();
    }

    document.getElementById('btnPayServiceChoice')?.addEventListener('click', () => {
        paymentSelectorDialog.close();
        openRegisterLicensePaymentForm(currentLicenseForPayment, 'mensualidad');
    });
    document.getElementById('btnPayAnnualChoice')?.addEventListener('click', () => {
        paymentSelectorDialog.close();
        openRegisterLicensePaymentForm(currentLicenseForPayment, 'instalacion');
    });
    document.getElementById('btnPayTotalChoice')?.addEventListener('click', () => {
        paymentSelectorDialog.close();
        openRegisterLicensePaymentForm(currentLicenseForPayment, 'total');
    });
    paymentTarget?.addEventListener('change', syncPaymentTarget);

    @if(old('license_id'))
        currentLicenseForPayment = {
            id: @json(old('license_id')),
            client_id: @json(old('client_id')),
            url: @json(optional($oldPaymentLicense)->url),
            billing_cycle: @json(optional($oldPaymentLicense)->billing_cycle),
            billing_cycle_label: @json(optional($oldPaymentLicense)->billing_cycle_label),
            setup_fee: @json(optional($oldPaymentLicense)->setup_fee),
            monthly_fee: @json(optional($oldPaymentLicense)->monthly_fee),
            service_amount: @json(optional($oldPaymentLicense)->service_amount),
        };
        renderCreditOptions(@json(old('client_id')), @json(old('credit_id')));
        syncPaymentTarget();
        registerPaymentDialog.showModal();
    @endif

    // ── System Control Dialog & Remote Modules ──
    let _sysLicenseId = null;
    let _sysBusy = false;
    const systemControlDialog = document.getElementById('systemControlDialog');

    function openSystemControlModal(lic) {
        if (!systemControlDialog) return;
        _sysLicenseId = lic.id;
        _sysBusy = false;
        document.getElementById('sysControlUrlText').textContent = lic.url;
        const fb = document.getElementById('sysFeedback');
        fb.className = 'sys-feedback';
        fb.style.display = 'none';
        fb.innerHTML = '';
        systemControlDialog.showModal();
        _sysSetState('loading');
        _sysFetchStatus();
    }

    function _sysSetState(state) {
        const card = document.getElementById('sysStatusCard');
        const icon = document.getElementById('sysStatusIcon');
        const label = document.getElementById('sysStatusLabel');
        const sub = document.getElementById('sysStatusSub');
        const btnRow = document.getElementById('sysActionRow');
        const btnEn = document.getElementById('btnSysEnable');
        const btnDis = document.getElementById('btnSysDisable');
        const btnRef = document.getElementById('btnSysRefresh');

        card.className = 'sys-status-card ' + state;

        if (state === 'loading') {
            icon.innerHTML = '<span class="sys-spinner"></span>';
            label.textContent = 'Verificando estado…';
            sub.textContent = 'Conectando con el sistema remoto';
            btnRow.style.display = 'none';
            btnRef.disabled = true;
        } else if (state === 'enabled') {
            icon.innerHTML = '<i class="bi bi-check-circle-fill" aria-hidden="true"></i>';
            label.textContent = 'Sistema Habilitado';
            sub.textContent = 'El sistema está operando con normalidad';
            btnRow.style.display = 'flex';
            btnEn.disabled = true;
            btnDis.disabled = false;
            btnRef.disabled = false;
        } else if (state === 'disabled') {
            icon.innerHTML = '<i class="bi bi-slash-circle-fill" aria-hidden="true"></i>';
            label.textContent = 'Sistema Deshabilitado';
            sub.textContent = 'El sistema muestra la página de mantenimiento';
            btnRow.style.display = 'flex';
            btnEn.disabled = false;
            btnDis.disabled = true;
            btnRef.disabled = false;
        } else if (state === 'unauthorized') {
            icon.innerHTML = '<i class="bi bi-shield-lock-fill" aria-hidden="true"></i>';
            label.textContent = 'Token no autorizado';
            sub.textContent = 'El token no coincide con el configurado en el sistema remoto';
            btnRow.style.display = 'none';
            btnRef.disabled = false;
        } else {
            icon.innerHTML = '<i class="bi bi-wifi-off" aria-hidden="true"></i>';
            label.textContent = 'Sin conexión';
            sub.textContent = 'No se pudo establecer comunicación con el sistema remoto';
            btnRow.style.display = 'none';
            btnRef.disabled = false;
        }
    }

    function _sysShowFeedback(type, msg) {
        const fb = document.getElementById('sysFeedback');
        fb.className = 'sys-feedback ' + type;
        fb.style.display = 'flex';
        const ico = type === 'success' ? 'bi-check-circle-fill' : 'bi-x-circle-fill';
        fb.innerHTML = `<i class="bi ${ico}" aria-hidden="true"></i><span></span>`;
        fb.querySelector('span').textContent = msg;
    }

    async function _sysFetchStatus() {
        _sysBusy = true;
        try {
            const res = await fetch(`/licenses/${_sysLicenseId}/system-status`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (data.success && data.status) {
                _sysSetState(data.status === 'enabled' ? 'enabled' : 'disabled');
            } else if (res.status === 401 || res.status === 403) {
                _sysSetState('unauthorized');
            } else {
                _sysSetState('error');
                _sysShowFeedback('failure', data.message ?? 'No se pudo conectar con el sistema remoto.');
            }
        } catch (e) {
            _sysSetState('error');
            _sysShowFeedback('failure', 'Error de red al conectar con el servidor.');
        }
        _sysBusy = false;
        _sysFetchModules();
    }

    async function _sysFetchModules() {
        const container = document.getElementById('sysModulesContainer');
        const spinner = document.getElementById('modulesSpinner');
        if (!container) return;
        if (spinner) spinner.style.display = 'inline-block';

        try {
            const res = await fetch(`/licenses/${_sysLicenseId}/system-modules`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (data.success && data.modules) {
                _renderModulesList(data.modules);
            } else {
                _renderModulesList({
                    accounting: { key: 'accounting', name: 'Contabilidad', description: 'Plan de cuentas PUC, comprobantes, asientos y reportes contables oficiales.', enabled: false, status: 'disabled' },
                    ecommerce: { key: 'ecommerce', name: 'Tienda Virtual', description: 'Módulo de tienda en línea / e-commerce.', enabled: false, status: 'disabled' }
                });
            }
        } catch (e) {
            container.innerHTML = `<div style="padding:12px; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25); border-radius:8px; font-size:12px; color:#ef4444; text-align:center;">No se pudo consultar el estado de los módulos en el sistema remoto.</div>`;
        } finally {
            if (spinner) spinner.style.display = 'none';
        }
    }

    function _renderModulesList(modulesObj) {
        const container = document.getElementById('sysModulesContainer');
        if (!container) return;
        const moduleKeys = Object.keys(modulesObj);
        if (moduleKeys.length === 0) {
            container.innerHTML = `<div style="padding:12px; background:rgba(255,255,255,0.03); border-radius:8px; font-size:12px; color:var(--silver); text-align:center;">No hay módulos adicionales registrados.</div>`;
            return;
        }

        container.innerHTML = moduleKeys.map((key) => {
            const mod = modulesObj[key];
            const isEnabled = mod.enabled === true || mod.status === 'enabled';
            const isEcom = ['ecommerce', 'tienda', 'shop', 'tiendas'].includes(key.toLowerCase());
            const isAcc = ['accounting', 'contabilidad'].includes(key.toLowerCase());
            const name = mod.name || (isAcc ? 'Contabilidad' : (isEcom ? 'Tienda Virtual' : key));
            const desc = mod.description || 'Módulo funcional del sistema.';
            const icon = isAcc ? 'bi-journal-bookmark-fill' : (isEcom ? 'bi-shop' : 'bi-puzzle-fill');

            return `
                <div style="background:rgba(255,255,255,0.04); border:1px solid ${isEnabled ? 'rgba(16,185,129,0.3)' : 'rgba(255,255,255,0.08)'}; border-radius:10px; padding:12px 14px; display:flex; align-items:center; justify-content:space-between; gap:12px;">
                    <div style="display:flex; align-items:flex-start; gap:10px; flex:1;">
                        <div style="width:32px; height:32px; border-radius:8px; background:${isEnabled ? 'rgba(16,185,129,0.2)' : 'rgba(255,255,255,0.06)'}; color:${isEnabled ? '#10b981' : 'var(--silver)'}; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; margin-top:2px;">
                            <i class="bi ${icon}" aria-hidden="true"></i>
                        </div>
                        <div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="font-size:13px; font-weight:700; color:#fff;">${name}</span>
                                <span style="font-size:10px; padding:2px 7px; border-radius:6px; font-weight:600; background:${isEnabled ? 'rgba(16,185,129,0.2)' : 'rgba(239,68,68,0.2)'}; color:${isEnabled ? '#10b981' : '#ef4444'};">${isEnabled ? 'Habilitado' : 'Deshabilitado'}</span>
                            </div>
                            <p style="margin:3px 0 0; font-size:11px; color:var(--silver-light); line-height:1.35;">${desc}</p>
                        </div>
                    </div>
                    <button type="button" id="btnMod_${key}" data-sys-module="${key}" data-sys-action="${isEnabled ? 'disable' : 'enable'}"
                        style="cursor:pointer; border-radius:8px; padding:6px 12px; font-size:12px; font-weight:600; white-space:nowrap; flex-shrink:0; display:inline-flex; align-items:center; gap:6px; border:1px solid ${isEnabled ? 'rgba(239,68,68,0.4)' : 'rgba(16,185,129,0.4)'}; background:${isEnabled ? 'rgba(239,68,68,0.15)' : 'rgba(16,185,129,0.15)'}; color:${isEnabled ? '#ef4444' : '#10b981'};">
                        <i class="bi ${isEnabled ? 'bi-toggle-on' : 'bi-toggle-off'}" aria-hidden="true"></i>
                        <span>${isEnabled ? 'Desactivar' : 'Activar'}</span>
                    </button>
                </div>
            `;
        }).join('');
    }

    document.getElementById('sysModulesContainer')?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-sys-module]');
        if (btn) doModuleToggle(btn.dataset.sysModule, btn.dataset.sysAction);
    });
    document.getElementById('btnSysEnable')?.addEventListener('click', () => doSystemToggle('enable'));
    document.getElementById('btnSysDisable')?.addEventListener('click', () => doSystemToggle('disable'));
    document.getElementById('btnSysRefresh')?.addEventListener('click', () => {
        _sysSetState('loading');
        _sysFetchStatus();
    });

    async function doModuleToggle(moduleKey, action) {
        if (_sysBusy) return;
        _sysBusy = true;
        const btn = document.getElementById(`btnMod_${moduleKey}`);
        const originalContent = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="sys-spinner" style="width:12px; height:12px; border-width:2px; display:inline-block;"></span>';
        }
        const fb = document.getElementById('sysFeedback');
        if (fb) fb.style.display = 'none';

        try {
            const res = await fetch(`/licenses/${_sysLicenseId}/system-modules/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ module: moduleKey, action })
            });
            const data = await res.json();
            if (data.success) {
                _sysShowFeedback('success', data.message ?? `Módulo '${moduleKey}' actualizado.`);
                const isNowEnabled = data.enabled === true || data.status === 'enabled';
                _updateTableModuleBadge(_sysLicenseId, moduleKey, isNowEnabled);
                showLicenseToast('success', data.message ?? 'Módulo actualizado.');
                await _sysFetchModules();
            } else {
                _sysShowFeedback('failure', data.message ?? 'No se pudo actualizar el módulo.');
                if (btn) { btn.disabled = false; btn.innerHTML = originalContent; }
            }
        } catch (e) {
            _sysShowFeedback('failure', 'Error de red al actualizar el módulo.');
            if (btn) { btn.disabled = false; btn.innerHTML = originalContent; }
        }
        _sysBusy = false;
    }

    async function doSystemToggle(action) {
        if (_sysBusy) return;
        _sysBusy = true;
        document.getElementById('btnSysEnable').disabled = true;
        document.getElementById('btnSysDisable').disabled = true;
        document.getElementById('btnSysRefresh').disabled = true;
        const fb = document.getElementById('sysFeedback');
        fb.style.display = 'none';

        try {
            const res = await fetch(`/licenses/${_sysLicenseId}/system-toggle`, {
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
                await _sysFetchStatus();
                _sysShowFeedback('failure', data.message ?? 'Error al ejecutar la acción.');
            }
        } catch (e) {
            _sysShowFeedback('failure', 'Error de red al ejecutar la acción.');
            document.getElementById('btnSysEnable').disabled = false;
            document.getElementById('btnSysDisable').disabled = false;
            document.getElementById('btnSysRefresh').disabled = false;
        }
        _sysBusy = false;
    }

    // ── Table Module Status Badges ──
    function _updateTableModuleBadge(licenseId, moduleKey, isEnabled) {
        const normKey = ['ecommerce', 'tienda', 'shop', 'tiendas'].includes(moduleKey.toLowerCase()) ? 'ecommerce' : 'accounting';
        const badge = document.getElementById(`modBadge_${licenseId}_${normKey}`);
        const statusText = document.getElementById(`modStatusText_${licenseId}_${normKey}`);
        if (!badge || !statusText) return;

        badge.classList.remove('loading', 'offline', 'enabled', 'disabled');
        badge.classList.add(isEnabled ? 'enabled' : 'disabled');
        const label = normKey === 'ecommerce' ? (isEnabled ? 'Activa' : 'Inactiva') : (isEnabled ? 'Activo' : 'Inactivo');
        statusText.innerHTML = isEnabled
            ? `<i class="bi bi-check-circle-fill" aria-hidden="true"></i> ${label}`
            : `<i class="bi bi-slash-circle-fill" aria-hidden="true"></i> ${label}`;
    }

    function _markTableModuleOffline(licenseId) {
        ['accounting', 'ecommerce'].forEach((key) => {
            const badge = document.getElementById(`modBadge_${licenseId}_${key}`);
            const statusText = document.getElementById(`modStatusText_${licenseId}_${key}`);
            if (badge && badge.classList.contains('loading')) {
                badge.classList.remove('loading');
                badge.classList.add('offline');
                if (statusText) statusText.innerHTML = '<i class="bi bi-wifi-off" aria-hidden="true"></i> N/D';
            }
        });
    }

    async function loadTableModuleStatuses() {
        const cells = document.querySelectorAll('.license-modules-cell');
        cells.forEach(async (cell) => {
            const licenseId = cell.dataset.licenseId;
            if (cell.querySelectorAll('.mod-table-badge.loading').length === 0) return;
            try {
                const res = await fetch(`/licenses/${licenseId}/system-modules`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.success && data.modules) {
                    const acc = data.modules.accounting;
                    const ecom = data.modules.ecommerce || data.modules.shop || data.modules.tienda;
                    if (acc) _updateTableModuleBadge(licenseId, 'accounting', acc.enabled === true || acc.status === 'enabled');
                    if (ecom) _updateTableModuleBadge(licenseId, 'ecommerce', ecom.enabled === true || ecom.status === 'enabled');
                } else {
                    _markTableModuleOffline(licenseId);
                }
            } catch (e) {
                _markTableModuleOffline(licenseId);
            }
        });
    }

    async function quickToggleModule(licenseId, moduleKey) {
        const normKey = ['ecommerce', 'tienda', 'shop', 'tiendas'].includes(moduleKey.toLowerCase()) ? 'ecommerce' : 'accounting';
        const badge = document.getElementById(`modBadge_${licenseId}_${normKey}`);
        const statusText = document.getElementById(`modStatusText_${licenseId}_${normKey}`);
        if (!badge || badge.classList.contains('processing')) return;

        const isCurrentlyEnabled = badge.classList.contains('enabled');
        const newAction = isCurrentlyEnabled ? 'disable' : 'enable';
        badge.classList.add('processing');
        const previousHtml = statusText ? statusText.innerHTML : '';
        if (statusText) statusText.innerHTML = '<span class="sys-spinner mini"></span>';

        try {
            const res = await fetch(`/licenses/${licenseId}/system-modules/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ module: normKey, action: newAction })
            });
            const data = await res.json();
            if (data.success) {
                _updateTableModuleBadge(licenseId, normKey, data.enabled === true || data.status === 'enabled');
                showLicenseToast('success', data.message || 'Módulo actualizado.');
            } else {
                if (statusText) statusText.innerHTML = previousHtml;
                showLicenseToast('error', data.message || 'No se pudo actualizar el módulo.');
            }
        } catch (e) {
            if (statusText) statusText.innerHTML = previousHtml;
            showLicenseToast('error', 'Error de red al actualizar el módulo.');
        } finally {
            badge.classList.remove('processing');
        }
    }

    // ── Delegated Event Listeners inside #licensesRefreshArea ──
    const refreshArea = document.getElementById('licensesRefreshArea');
    refreshArea?.addEventListener('click', (e) => {
        const actionBtn = e.target.closest('[data-action]');
        if (actionBtn) {
            const action = actionBtn.dataset.action;
            if (action === 'toggle-module') {
                quickToggleModule(actionBtn.dataset.licenseId, actionBtn.dataset.module);
                return;
            }
            const lic = JSON.parse(actionBtn.dataset.license || '{}');
            if (action === 'pay-license') openLicensePaymentFlow(lic);
            else if (action === 'edit-license') openEditLicenseModal(lic);
            else if (action === 'edit-shared') openSharedLicenseModal(lic);
            else if (action === 'delete-license') openDeleteLicenseModal(lic);
            else if (action === 'system-control') openSystemControlModal(lic);
            else if (action === 'upload-contract') openContractUploadModal(lic);
            else if (action === 'view-contract') openContractViewerModal(lic);
            return;
        }

        const filterPill = e.target.closest('.lic-filter-pill[data-filter]');
        if (filterPill) {
            const filterInput = document.getElementById('billingFilterInput');
            if (filterInput) filterInput.value = filterPill.dataset.filter;
            performLiveSearch();
            return;
        }

        const pagLink = e.target.closest('.pagination-wrapper a');
        if (pagLink && pagLink.href) {
            e.preventDefault();
            fetchAndSwapTable(pagLink.href, true);
        }
    });

    // Re-bind btnOpenCreateModal if replaced inside #licensesRefreshArea
    refreshArea?.addEventListener('click', (e) => {
        if (e.target.closest('#btnOpenCreateModal')) {
            createFormCtrl?.refresh();
            createDialog?.showModal();
        }
    });

    // ── Live Search & Filtering ──
    let _searchDebounceTimer = null;
    let _searchAbortController = null;

    refreshArea?.addEventListener('input', (e) => {
        if (e.target.id !== 'licenseSearchInput') return;
        const query = e.target.value.trim();
        const rows = document.querySelectorAll('#licensesTable tbody tr');
        const qLower = query.toLowerCase();
        rows.forEach((row) => {
            row.style.display = (!qLower || row.innerText.toLowerCase().includes(qLower)) ? '' : 'none';
        });

        clearTimeout(_searchDebounceTimer);
        const searchIcon = document.getElementById('licenseSearchIcon');
        if (searchIcon) searchIcon.classList.add('spinning');
        _searchDebounceTimer = setTimeout(() => performLiveSearch(), 300);
    });

    refreshArea?.addEventListener('keydown', (e) => {
        if (e.target.id === 'licenseSearchInput' && e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(_searchDebounceTimer);
            performLiveSearch();
        }
    });

    refreshArea?.addEventListener('change', (e) => {
        if (e.target.id === 'per_page') {
            performLiveSearch();
        }
    });

    refreshArea?.addEventListener('submit', (e) => {
        if (e.target.id === 'licenseSearchForm') {
            e.preventDefault();
            performLiveSearch();
        }
    });

    async function performLiveSearch() {
        const searchInput = document.getElementById('licenseSearchInput');
        const perPageEl = document.getElementById('per_page');
        const filterEl = document.getElementById('billingFilterInput');

        const query = searchInput ? searchInput.value.trim() : '';
        const perPage = perPageEl ? perPageEl.value : '10';
        const billingFilter = filterEl ? filterEl.value : 'all';

        const url = new URL(window.location.origin + window.location.pathname);
        if (query) url.searchParams.set('search', query);
        if (billingFilter && billingFilter !== 'all') url.searchParams.set('billing_filter', billingFilter);
        url.searchParams.set('per_page', perPage);

        await fetchAndSwapTable(url.toString(), true);
    }

    async function fetchAndSwapTable(url, updateHistory = true) {
        const searchIcon = document.getElementById('licenseSearchIcon');
        if (searchIcon) searchIcon.classList.add('spinning');

        if (_searchAbortController) _searchAbortController.abort();
        _searchAbortController = new AbortController();

        try {
            const activeSearch = document.getElementById('licenseSearchInput');
            const hadFocus = document.activeElement === activeSearch;
            const selStart = activeSearch?.selectionStart ?? null;
            const selEnd = activeSearch?.selectionEnd ?? null;

            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: _searchAbortController.signal
            });
            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');

            const newArea = doc.getElementById('licensesRefreshArea');
            const currentArea = document.getElementById('licensesRefreshArea');
            if (newArea && currentArea) {
                currentArea.innerHTML = newArea.innerHTML;
            }

            if (updateHistory) {
                window.history.replaceState(null, '', url);
            }

            if (hadFocus) {
                const nextSearch = document.getElementById('licenseSearchInput');
                if (nextSearch) {
                    nextSearch.focus();
                    if (selStart !== null && selEnd !== null) {
                        nextSearch.setSelectionRange(selStart, selEnd);
                    }
                }
            }

            loadTableModuleStatuses();
        } catch (e) {
            if (e.name !== 'AbortError') {
                console.error('Error al actualizar tabla:', e);
            }
        } finally {
            const newSearchIcon = document.getElementById('licenseSearchIcon');
            if (newSearchIcon) newSearchIcon.classList.remove('spinning');
        }
    }

    loadTableModuleStatuses();
});
</script>

@endsection
