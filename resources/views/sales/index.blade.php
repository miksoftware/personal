@extends('layouts.app')

@section('title', 'Ventas de Equipos y Otros - MIK Software Control')
@section('page_title', 'Ventas de Equipos y Otros')
@section('page_subtitle', 'Control y registro de ventas de hardware, equipos POS, periféricos, accesorios y servicios.')

@section('content')

{{-- ── Status / Error Alerts ────────────────────────────── --}}
@if(session('status'))
    <div class="alert-banner-success" id="sale-status-alert" style="margin-bottom:25px;">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('status') }}</span>
    </div>
    <script>
        setTimeout(() => {
            const el = document.getElementById('sale-status-alert');
            if (el) el.style.display = 'none';
        }, 7000);
    </script>
@endif

@if($errors->any())
    <div class="alert-banner" id="sale-error-alert" style="margin-bottom:25px;">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

{{-- ── Summary Cards ────────────────────────────────────── --}}
<div style="display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
    <!-- Card 1: Ventas del Mes -->
    <div style="flex:1; min-width:180px; padding:14px 18px; background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.25); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <div style="width:42px; height:42px; border-radius:10px; background:rgba(16,185,129,0.15); display:flex; align-items:center; justify-content:center;">
            <i class="bi bi-cart-check" style="font-size:22px; color:#10b981;"></i>
        </div>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Ventas del Mes</div>
            <div style="font-size:18px; font-weight:700; color:#10b981;">${{ number_format($totalSalesMonth, 2) }}</div>
        </div>
    </div>

    <!-- Card 2: Ventas del Año -->
    <div style="flex:1; min-width:180px; padding:14px 18px; background:rgba(99,102,241,0.08); border:1px solid rgba(99,102,241,0.25); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <div style="width:42px; height:42px; border-radius:10px; background:rgba(99,102,241,0.15); display:flex; align-items:center; justify-content:center;">
            <i class="bi bi-graph-up-arrow" style="font-size:20px; color:#6366f1;"></i>
        </div>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Ventas del Año</div>
            <div style="font-size:18px; font-weight:700; color:#818cf8;">${{ number_format($totalSalesYear, 2) }}</div>
        </div>
    </div>

    <!-- Card 3: Saldo Pendiente por Cobrar -->
    <div style="flex:1; min-width:180px; padding:14px 18px; background:rgba(239,83,80,0.08); border:1px solid rgba(239,83,80,0.25); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <div style="width:42px; height:42px; border-radius:10px; background:rgba(239,83,80,0.15); display:flex; align-items:center; justify-content:center;">
            <i class="bi bi-clock-history" style="font-size:22px; color:#ef5350;"></i>
        </div>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Pendiente por Cobrar</div>
            <div style="font-size:18px; font-weight:700; color:#ef5350;">${{ number_format($pendingSalesAmount, 2) }}</div>
        </div>
    </div>

    <!-- Card 4: Ventas Cobradas -->
    <div style="flex:1; min-width:180px; padding:14px 18px; background:rgba(6,182,212,0.08); border:1px solid rgba(6,182,212,0.25); border-radius:12px; display:flex; align-items:center; gap:12px;">
        <div style="width:42px; height:42px; border-radius:10px; background:rgba(6,182,212,0.15); display:flex; align-items:center; justify-content:center;">
            <i class="bi bi-box-seam" style="font-size:20px; color:#06b6d4;"></i>
        </div>
        <div>
            <div style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;">Ventas Pagadas</div>
            <div style="font-size:18px; font-weight:700; color:#06b6d4;">{{ $paidSalesCount }}</div>
        </div>
    </div>
</div>

{{-- ── Main Table Card ──────────────────────────────────── --}}
<div class="client-table-card">

    {{-- Filter Bar --}}
    <div class="filter-bar" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <form action="{{ route('sales.index') }}" method="GET" style="display:flex; gap:10px; flex:1; max-width:680px; flex-wrap:wrap;">
            <div class="search-wrapper" style="flex:2; min-width:200px;">
                <i class="bi bi-search search-icon"></i>
                <input type="text" name="search" class="search-input"
                    placeholder="Buscar por equipo, cliente, serial..."
                    value="{{ $search }}" autocomplete="off">
            </div>

            {{-- Filtro Categoría --}}
            <select name="category" class="form-input" style="width:auto; padding:6px 12px; font-size:13px;" onchange="this.form.submit()">
                <option value="all" {{ $category === 'all' ? 'selected' : '' }}>Todas las categorías</option>
                <option value="equipo" {{ $category === 'equipo' ? 'selected' : '' }}>Equipos / Hardware</option>
                <option value="accesorio" {{ $category === 'accesorio' ? 'selected' : '' }}>Accesorios / Suministros</option>
                <option value="servicio" {{ $category === 'servicio' ? 'selected' : '' }}>Servicios / Instalación</option>
                <option value="otro" {{ $category === 'otro' ? 'selected' : '' }}>Otros</option>
            </select>

            {{-- Filtro Estado --}}
            <select name="status" class="form-input" style="width:auto; padding:6px 12px; font-size:13px;" onchange="this.form.submit()">
                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Todos los estados</option>
                <option value="pagado" {{ $status === 'pagado' ? 'selected' : '' }}>Pagados</option>
                <option value="pendiente" {{ $status === 'pendiente' ? 'selected' : '' }}>Pendientes</option>
            </select>
        </form>

        <button class="btn-primary-action" id="btnOpenCreateSale">
            <i class="bi bi-plus-lg"></i>
            <span>Nueva Venta</span>
        </button>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        @if($sales->count() > 0)
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Categoría</th>
                        <th>Equipo / Producto</th>
                        <th>Detalle</th>
                        <th style="text-align:right;">Total</th>
                        <th style="text-align:center;">Estado</th>
                        <th style="width:120px; text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sales as $sale)
                        <tr>
                            <td style="color:var(--silver-light); font-size:13px; white-space:nowrap;">
                                {{ \Carbon\Carbon::parse($sale->sale_date)->format('d/m/Y') }}
                            </td>
                            <td>
                                <div style="font-weight:600; color:var(--white);">{{ $sale->client->name }}</div>
                                <div style="font-size:11px; color:rgba(255,255,255,0.4);">{{ $sale->client->model_label }}</div>
                            </td>
                            <td>
                                <span style="font-size:11px; font-weight:600; padding:3px 8px; border-radius:6px; background:rgba(255,255,255,0.06); color:{{ $sale->category_badge_color }}; border:1px solid {{ $sale->category_badge_color }}33;">
                                    {{ $sale->category_label }}
                                </span>
                            </td>
                            <td>
                                <div style="font-weight:600; color:var(--white); font-size:14px;">{{ $sale->item_name }}</div>
                                <div style="display:flex; gap:6px; margin-top:3px; flex-wrap:wrap;">
                                    @if($sale->serial_number)
                                        <span style="font-size:10px; color:#cbd5e1; background:rgba(255,255,255,0.08); padding:1px 6px; border-radius:4px;">
                                            <i class="bi bi-upc-scan"></i> S/N: {{ $sale->serial_number }}
                                        </span>
                                    @endif
                                    @if($sale->warranty)
                                        <span style="font-size:10px; color:#facc15; background:rgba(250,204,21,0.1); padding:1px 6px; border-radius:4px; border:1px solid rgba(250,204,21,0.2);">
                                            <i class="bi bi-shield-check"></i> {{ $sale->warranty }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td style="font-size:13px; color:var(--silver-light); white-space:nowrap;">
                                {{ $sale->quantity }} x ${{ number_format($sale->unit_price, 2) }}
                            </td>
                            <td style="text-align:right; font-weight:700; color:#10b981; font-size:15px; white-space:nowrap;">
                                ${{ number_format($sale->total_amount, 2) }}
                            </td>
                            <td style="text-align:center;">
                                @if($sale->status === 'pagado')
                                    <span class="status-badge completed" title="{{ $sale->bankAccount?->name }} • {{ $sale->payment_method_label }}">
                                        <i class="bi bi-check2"></i> Pagado
                                    </span>
                                    @if($sale->bankAccount)
                                        <div style="font-size:10px; color:rgba(255,255,255,0.4); margin-top:2px;">
                                            {{ $sale->bankAccount->name }} ({{ $sale->payment_method_label }})
                                        </div>
                                    @endif
                                @else
                                    <span class="status-badge pending" style="background:rgba(239,83,80,0.15); color:#ef5350; border:1px solid rgba(239,83,80,0.3);">
                                        <i class="bi bi-hourglass-split"></i> Pendiente
                                    </span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                <div class="actions-cell" style="justify-content:center; gap:6px;">
                                    @if($sale->status === 'pendiente')
                                        <button type="button" class="btn-action" title="Registrar Cobro / Pago"
                                            style="color:#10b981; border-color:rgba(16,185,129,0.3); background:rgba(16,185,129,0.1);"
                                            onclick="openPaySaleModal({{ json_encode($sale) }})">
                                            <i class="bi bi-cash-coin"></i>
                                        </button>
                                    @endif
                                    <button type="button" class="btn-action edit" title="Editar venta"
                                        onclick="openEditSaleModal({{ json_encode($sale) }})">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button type="button" class="btn-action delete" title="Eliminar venta"
                                        onclick="openDeleteSaleModal({{ $sale->id }}, '{{ addslashes($sale->item_name) }}', '{{ number_format($sale->total_amount, 2) }}')">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Pagination --}}
            <div style="margin-top:20px;">
                {{ $sales->withQueryString()->links() }}
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-box-seam"></i></div>
                <h3 class="empty-state-title">No hay ventas registradas</h3>
                <p class="empty-state-desc">Registra tus ventas de equipos, hardware, periféricos o servicios especiales.</p>
                <button type="button" class="btn-primary-action" onclick="document.getElementById('btnOpenCreateSale').click()">
                    <i class="bi bi-plus-lg"></i>
                    <span>Registrar Primera Venta</span>
                </button>
            </div>
        @endif
    </div>
</div>

{{-- ==============================================================
     MODAL — NUEVA VENTA
     ============================================================== --}}
<div class="modal" id="createSaleModal">
    <div class="modal-backdrop" id="createSaleBackdrop"></div>
    <div class="modal-content" style="max-width:620px;">
        <div class="modal-header">
            <h3 class="modal-title">Registrar Venta de Equipo / Producto</h3>
            <button type="button" class="btn-close-modal" id="btnCloseCreateSale">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form action="{{ route('sales.index') }}" method="POST" autocomplete="off" id="formCreateSale">
            @csrf

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                {{-- Cliente --}}
                <div class="form-group">
                    <label for="cr_client_id" class="form-label">Cliente *</label>
                    <select name="client_id" id="cr_client_id" class="form-input" required>
                        <option value="">Seleccionar cliente...</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->name }} ({{ $client->model_label }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Categoría --}}
                <div class="form-group">
                    <label for="cr_category" class="form-label">Categoría *</label>
                    <select name="category" id="cr_category" class="form-input" required>
                        <option value="equipo" {{ old('category') === 'equipo' ? 'selected' : '' }}>Equipo / Hardware (Computador, POS, Impresora)</option>
                        <option value="accesorio" {{ old('category') === 'accesorio' ? 'selected' : '' }}>Accesorio / Suministro (Cables, Rollos, etc.)</option>
                        <option value="servicio" {{ old('category') === 'servicio' ? 'selected' : '' }}>Servicio / Instalación física</option>
                        <option value="otro" {{ old('category') === 'otro' ? 'selected' : '' }}>Otro</option>
                    </select>
                </div>
            </div>

            {{-- Nombre del Equipo / Producto --}}
            <div class="form-group">
                <label for="cr_item_name" class="form-label">Nombre del Equipo o Producto *</label>
                <input type="text" name="item_name" id="cr_item_name" class="form-input"
                    placeholder="Ej: Impresora Térmica 80mm USB+Red, Laptop HP ProBook..."
                    value="{{ old('item_name') }}" required>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                {{-- Número de Serial --}}
                <div class="form-group">
                    <label for="cr_serial_number" class="form-label">Número de Serie (S/N)</label>
                    <input type="text" name="serial_number" id="cr_serial_number" class="form-input"
                        placeholder="Ej: POS80-2026-X892" value="{{ old('serial_number') }}">
                </div>

                {{-- Garantía --}}
                <div class="form-group">
                    <label for="cr_warranty" class="form-label">Tiempo de Garantía</label>
                    <input type="text" name="warranty" id="cr_warranty" class="form-input"
                        placeholder="Ej: 6 meses, 1 año, Sin garantía..." value="{{ old('warranty') }}">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr 1.2fr; gap:16px;">
                {{-- Cantidad --}}
                <div class="form-group">
                    <label for="cr_quantity" class="form-label">Cantidad *</label>
                    <input type="number" name="quantity" id="cr_quantity" class="form-input"
                        min="1" value="{{ old('quantity', 1) }}" required>
                </div>

                {{-- Precio Unitario --}}
                <div class="form-group">
                    <label for="cr_unit_price" class="form-label">Precio Unitario ($) *</label>
                    <input type="number" name="unit_price" id="cr_unit_price" class="form-input"
                        step="0.01" min="0" placeholder="0.00" value="{{ old('unit_price') }}" required>
                </div>

                {{-- Total --}}
                <div class="form-group">
                    <label for="cr_total_amount" class="form-label">Total a Cobrar ($) *</label>
                    <input type="number" name="total_amount" id="cr_total_amount" class="form-input"
                        step="0.01" min="0" placeholder="0.00" value="{{ old('total_amount') }}" required style="font-weight:700; color:#10b981;">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                {{-- Fecha de Venta --}}
                <div class="form-group">
                    <label for="cr_sale_date" class="form-label">Fecha de Venta *</label>
                    <input type="date" name="sale_date" id="cr_sale_date" class="form-input"
                        value="{{ old('sale_date', date('Y-m-d')) }}" required>
                </div>

                {{-- Estado de Pago --}}
                <div class="form-group">
                    <label for="cr_status" class="form-label">Estado de Pago *</label>
                    <select name="status" id="cr_status" class="form-input" required>
                        <option value="pagado" {{ old('status') === 'pagado' ? 'selected' : '' }}>Pagado de Contado</option>
                        <option value="pendiente" {{ old('status', 'pendiente') === 'pendiente' ? 'selected' : '' }}>Pendiente de Pago (Crédito)</option>
                    </select>
                </div>
            </div>

            {{-- Bloque de Cuenta y Método si se paga de contado --}}
            <div id="cr_payment_fields" style="display:none; background:rgba(16,185,129,0.05); border:1px solid rgba(16,185,129,0.2); border-radius:10px; padding:14px; margin-bottom:16px;">
                <div style="font-size:12px; font-weight:600; color:#10b981; margin-bottom:10px;">
                    <i class="bi bi-wallet2"></i> Datos de Cobro Inmediato
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="cr_bank_account_id" class="form-label">Cuenta de Destino *</label>
                        <select name="bank_account_id" id="cr_bank_account_id" class="form-input">
                            <option value="">Seleccionar cuenta...</option>
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('bank_account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->name }} (${{ number_format($acc->current_balance, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label for="cr_payment_method" class="form-label">Método de Pago *</label>
                        <select name="payment_method" id="cr_payment_method" class="form-input">
                            <option value="efectivo" {{ old('payment_method') === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                            <option value="nequi" {{ old('payment_method') === 'nequi' ? 'selected' : '' }}>Nequi</option>
                            <option value="bancolombia" {{ old('payment_method') === 'bancolombia' ? 'selected' : '' }}>Bancolombia</option>
                            <option value="transferencia" {{ old('payment_method') === 'transferencia' ? 'selected' : '' }}>Transferencia</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Descripción / Notas --}}
            <div class="form-group">
                <label for="cr_description" class="form-label">Descripción / Observaciones</label>
                <textarea name="description" id="cr_description" rows="2" class="form-input"
                    placeholder="Detalles de entrega, accesorios incluidos, especificaciones...">{{ old('description') }}</textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelCreateSale">Cancelar</button>
                <button type="submit" class="btn-primary-action">Registrar Venta</button>
            </div>
        </form>
    </div>
</div>

{{-- ==============================================================
     MODAL — EDITAR VENTA
     ============================================================== --}}
<div class="modal" id="editSaleModal">
    <div class="modal-backdrop" id="editSaleBackdrop"></div>
    <div class="modal-content" style="max-width:620px;">
        <div class="modal-header">
            <h3 class="modal-title">Editar Venta</h3>
            <button type="button" class="btn-close-modal" id="btnCloseEditSale">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="formEditSale" method="POST" autocomplete="off">
            @csrf
            @method('PUT')

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="ed_client_id" class="form-label">Cliente *</label>
                    <select name="client_id" id="ed_client_id" class="form-input" required>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">
                                {{ $client->name }} ({{ $client->model_label }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="ed_category" class="form-label">Categoría *</label>
                    <select name="category" id="ed_category" class="form-input" required>
                        <option value="equipo">Equipo / Hardware</option>
                        <option value="accesorio">Accesorio / Suministro</option>
                        <option value="servicio">Servicio / Instalación</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="ed_item_name" class="form-label">Nombre del Equipo o Producto *</label>
                <input type="text" name="item_name" id="ed_item_name" class="form-input" required>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="ed_serial_number" class="form-label">Número de Serie (S/N)</label>
                    <input type="text" name="serial_number" id="ed_serial_number" class="form-input">
                </div>

                <div class="form-group">
                    <label for="ed_warranty" class="form-label">Tiempo de Garantía</label>
                    <input type="text" name="warranty" id="ed_warranty" class="form-input">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr 1.2fr; gap:16px;">
                <div class="form-group">
                    <label for="ed_quantity" class="form-label">Cantidad *</label>
                    <input type="number" name="quantity" id="ed_quantity" class="form-input" min="1" required>
                </div>

                <div class="form-group">
                    <label for="ed_unit_price" class="form-label">Precio Unitario ($) *</label>
                    <input type="number" name="unit_price" id="ed_unit_price" class="form-input" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="ed_total_amount" class="form-label">Total ($) *</label>
                    <input type="number" name="total_amount" id="ed_total_amount" class="form-input" step="0.01" min="0" required style="font-weight:700; color:#10b981;">
                </div>
            </div>

            <div class="form-group">
                <label for="ed_sale_date" class="form-label">Fecha de Venta *</label>
                <input type="date" name="sale_date" id="ed_sale_date" class="form-input" required>
            </div>

            <div class="form-group">
                <label for="ed_description" class="form-label">Descripción / Notas</label>
                <textarea name="description" id="ed_description" rows="2" class="form-input"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelEditSale">Cancelar</button>
                <button type="submit" class="btn-primary-action">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

{{-- ==============================================================
     MODAL — REGISTRAR PAGO DE VENTA PENDIENTE
     ============================================================== --}}
<div class="modal" id="paySaleModal">
    <div class="modal-backdrop" id="paySaleBackdrop"></div>
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3 class="modal-title">Registrar Cobro de Venta</h3>
            <button type="button" class="btn-close-modal" id="btnClosePaySale">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="formPaySale" method="POST" autocomplete="off">
            @csrf

            <div style="background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.25); border-radius:10px; padding:14px; margin-bottom:16px;">
                <div style="font-size:12px; color:rgba(255,255,255,0.45); margin-bottom:2px;">Cobro por venta de:</div>
                <div id="pay_item_title" style="font-size:15px; font-weight:700; color:var(--white); margin-bottom:4px;"></div>
                <div style="font-size:13px; color:var(--silver-light);">Cliente: <strong id="pay_client_name" style="color:var(--white);"></strong></div>
                <div style="font-size:20px; font-weight:800; color:#10b981; margin-top:8px;" id="pay_total_display"></div>
            </div>

            <div class="form-group">
                <label for="py_bank_account_id" class="form-label">Cuenta Bancaria de Destino *</label>
                <select name="bank_account_id" id="py_bank_account_id" class="form-input" required>
                    <option value="">Seleccionar cuenta donde ingresa el dinero...</option>
                    @foreach($bankAccounts as $account)
                        <option value="{{ $account->id }}">
                            {{ $account->name }} (${{ number_format($account->current_balance, 2) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="py_payment_method" class="form-label">Método de Pago *</label>
                    <select name="payment_method" id="py_payment_method" class="form-input" required>
                        <option value="efectivo">Efectivo</option>
                        <option value="nequi">Nequi</option>
                        <option value="bancolombia">Bancolombia</option>
                        <option value="transferencia">Transferencia</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="py_payment_date" class="form-label">Fecha del Pago *</label>
                    <input type="date" name="payment_date" id="py_payment_date" class="form-input"
                        value="{{ date('Y-m-d') }}" required>
                </div>
            </div>

            <div class="form-group">
                <label for="py_reference" class="form-label">Número de Comprobante / Referencia</label>
                <input type="text" name="reference" id="py_reference" class="form-input"
                    placeholder="Ej: Transacción #8293">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="btnCancelPaySale">Cancelar</button>
                <button type="submit" class="btn-primary-action" style="background:#10b981; border-color:#10b981;">
                    <i class="bi bi-check-circle"></i> Confirmar Cobro
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==============================================================
     MODAL — ELIMINAR VENTA
     ============================================================== --}}
<div class="modal" id="deleteSaleModal">
    <div class="modal-backdrop" id="deleteSaleBackdrop"></div>
    <div class="modal-content" style="max-width:400px; text-align:center;">
        <div style="font-size:48px; color:#ff5252; margin-bottom:15px;">
            <i class="bi bi-exclamation-circle"></i>
        </div>
        <h3 class="modal-title" style="margin-bottom:10px; display:inline-block;">¿Eliminar Venta?</h3>
        <p style="color:var(--silver-light); font-size:14px; line-height:1.6; margin-bottom:25px;">
            Se eliminará la venta de <strong id="deleteSaleDesc" style="color:var(--white);"></strong>
            por <strong id="deleteSaleAmount" style="color:#ef5350;"></strong>.
            Si tenía pagos asociados, los saldos bancarios serán revertidos.
        </p>
        <form id="deleteSaleForm" method="POST">
            @csrf
            @method('DELETE')
            <div style="display:flex; justify-content:center; gap:12px;">
                <button type="button" class="btn-secondary" id="btnCancelDeleteSale" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn-danger-action" style="flex:1;">Eliminar</button>
            </div>
        </form>
    </div>
</div>

{{-- ── JavaScript Controller ────────────────────────────── --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Create Modal Handlers ─────────────────────────────
    const createModal = document.getElementById('createSaleModal');
    const openCreate  = () => createModal.classList.add('open');
    const closeCreate = () => createModal.classList.remove('open');

    document.getElementById('btnOpenCreateSale').addEventListener('click', openCreate);
    document.getElementById('btnCloseCreateSale').addEventListener('click', closeCreate);
    document.getElementById('btnCancelCreateSale').addEventListener('click', closeCreate);
    document.getElementById('createSaleBackdrop').addEventListener('click', closeCreate);

    // Auto-calculate total amount on quantity or unit price change
    const crQty   = document.getElementById('cr_quantity');
    const crPrice = document.getElementById('cr_unit_price');
    const crTotal = document.getElementById('cr_total_amount');

    const updateCreateTotal = () => {
        const q = parseFloat(crQty.value) || 0;
        const p = parseFloat(crPrice.value) || 0;
        crTotal.value = (q * p).toFixed(2);
    };
    crQty.addEventListener('input', updateCreateTotal);
    crPrice.addEventListener('input', updateCreateTotal);

    // Toggle payment fields in create modal
    const crStatus = document.getElementById('cr_status');
    const crPayFields = document.getElementById('cr_payment_fields');
    const crBankAcc = document.getElementById('cr_bank_account_id');

    const togglePaymentFields = () => {
        if (crStatus.value === 'pagado') {
            crPayFields.style.display = 'block';
            crBankAcc.required = true;
        } else {
            crPayFields.style.display = 'none';
            crBankAcc.required = false;
        }
    };
    crStatus.addEventListener('change', togglePaymentFields);
    togglePaymentFields();

    // ── Edit Modal Handlers ───────────────────────────────
    const editModal = document.getElementById('editSaleModal');
    const closeEdit = () => editModal.classList.remove('open');

    document.getElementById('btnCloseEditSale').addEventListener('click', closeEdit);
    document.getElementById('btnCancelEditSale').addEventListener('click', closeEdit);
    document.getElementById('editSaleBackdrop').addEventListener('click', closeEdit);

    const edQty   = document.getElementById('ed_quantity');
    const edPrice = document.getElementById('ed_unit_price');
    const edTotal = document.getElementById('ed_total_amount');

    const updateEditTotal = () => {
        const q = parseFloat(edQty.value) || 0;
        const p = parseFloat(edPrice.value) || 0;
        edTotal.value = (q * p).toFixed(2);
    };
    edQty.addEventListener('input', updateEditTotal);
    edPrice.addEventListener('input', updateEditTotal);

    window.openEditSaleModal = function (sale) {
        const form = document.getElementById('formEditSale');
        form.action = '/sales/' + sale.id;

        document.getElementById('ed_client_id').value     = sale.client_id;
        document.getElementById('ed_category').value      = sale.category;
        document.getElementById('ed_item_name').value     = sale.item_name;
        document.getElementById('ed_serial_number').value = sale.serial_number || '';
        document.getElementById('ed_warranty').value      = sale.warranty || '';
        document.getElementById('ed_quantity').value      = sale.quantity;
        document.getElementById('ed_unit_price').value    = sale.unit_price;
        document.getElementById('ed_total_amount').value  = sale.total_amount;
        document.getElementById('ed_sale_date').value     = sale.sale_date;
        document.getElementById('ed_description').value   = sale.description || '';

        editModal.classList.add('open');
    };

    // ── Pay Modal Handlers ────────────────────────────────
    const payModal  = document.getElementById('paySaleModal');
    const closePay  = () => payModal.classList.remove('open');

    document.getElementById('btnClosePaySale').addEventListener('click', closePay);
    document.getElementById('btnCancelPaySale').addEventListener('click', closePay);
    document.getElementById('paySaleBackdrop').addEventListener('click', closePay);

    window.openPaySaleModal = function (sale) {
        const form = document.getElementById('formPaySale');
        form.action = '/sales/' + sale.id + '/pay';

        document.getElementById('pay_item_title').textContent  = sale.item_name;
        document.getElementById('pay_client_name').textContent = sale.client ? sale.client.name : 'Cliente';
        document.getElementById('pay_total_display').textContent = '$' + Number(sale.total_amount).toLocaleString('es-CO', { minimumFractionDigits: 2 });

        payModal.classList.add('open');
    };

    // ── Delete Modal Handlers ─────────────────────────────
    const deleteModal = document.getElementById('deleteSaleModal');
    const closeDelete = () => deleteModal.classList.remove('open');

    document.getElementById('btnCancelDeleteSale').addEventListener('click', closeDelete);
    document.getElementById('deleteSaleBackdrop').addEventListener('click', closeDelete);

    window.openDeleteSaleModal = function (id, desc, amount) {
        document.getElementById('deleteSaleForm').action = '/sales/' + id;
        document.getElementById('deleteSaleDesc').textContent   = desc;
        document.getElementById('deleteSaleAmount').textContent = '$' + amount;
        deleteModal.classList.add('open');
    };

    // ESC key closes any open modal
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeCreate();
            closeEdit();
            closePay();
            closeDelete();
        }
    });

});
</script>

@endsection
