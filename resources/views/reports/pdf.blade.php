<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Cuenta - {{ $client->name }}</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.4;
            font-size: 11px;
        }
        .header {
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
        }
        .brand {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .doc-title {
            text-align: right;
            font-size: 18px;
            color: #7f8c8d;
            text-transform: uppercase;
        }
        .client-info {
            margin-bottom: 30px;
        }
        .client-info table {
            width: 100%;
        }
        .client-info td {
            vertical-align: top;
        }
        .section-title {
            background-color: #f8f9fa;
            padding: 8px 12px;
            font-weight: bold;
            color: #2c3e50;
            border-left: 4px solid #2c3e50;
            margin: 20px 0 10px 0;
            text-transform: uppercase;
            font-size: 12px;
        }
        .summary-box {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-item {
            display: table-cell;
            width: 33.33%;
            padding: 10px;
            text-align: center;
            border: 1px solid #eee;
        }
        .summary-label {
            font-size: 10px;
            color: #7f8c8d;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .summary-value {
            font-size: 16px;
            font-weight: bold;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #2c3e50;
            color: white;
            text-align: left;
            padding: 8px;
            font-size: 10px;
        }
        .table td {
            border-bottom: 1px solid #eee;
            padding: 8px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            color: white;
        }
        .bg-success { background-color: #27ae60; }
        .bg-warning { background-color: #f39c12; }
        .bg-danger { background-color: #e74c3c; }
        .bg-info { background-color: #3498db; }
        
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #bdc3c7;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .credit-payment-detail {
            font-size: 9px;
            color: #7f8c8d;
            margin-top: 4px;
            padding-left: 10px;
            border-left: 1px solid #eee;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td class="brand">MIK SOFTWARE</td>
                <td class="doc-title">Estado de Cuenta</td>
            </tr>
        </table>
    </div>

    <div class="client-info">
        <table>
            <tr>
                <td style="width: 60%;">
                    <div style="font-size: 14px; font-weight: bold;">{{ $client->name }}</div>
                    <div>{{ $client->email }}</div>
                    <div>{{ $client->phone }}</div>
                </td>
                <td style="text-align: right;">
                    <div><strong>Fecha de Emisión:</strong> {{ date('d/m/Y') }}</div>
                    <div><strong>Estado:</strong> <span style="color: {{ $balance > 0 ? '#e74c3c' : '#27ae60' }};">{{ $balance > 0 ? 'Con Saldo Pendiente' : 'Al Día' }}</span></div>
                </td>
            </tr>
        </table>
    </div>

    <div class="summary-box">
        <div class="summary-item" style="border-right: none;">
            <div class="summary-label">Total Deuda</div>
            <div class="summary-value" style="color: #e74c3c;">${{ number_format($totalDebt, 2) }}</div>
        </div>
        <div class="summary-item" style="border-right: none;">
            <div class="summary-label">Total Abonos</div>
            <div class="summary-value" style="color: #27ae60;">${{ number_format($totalPaid, 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Saldo Pendiente</div>
            <div class="summary-value" style="color: #f39c12;">${{ number_format($balance, 2) }}</div>
        </div>
    </div>

    @if($developments->isNotEmpty())
        <div class="section-title">Detalle de Requerimientos (Deuda)</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Concepto</th>
                    <th>Estado</th>
                    <th class="text-right">Valor</th>
                    <th class="text-right">Abonado</th>
                    <th class="text-right">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($developments as $dev)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($dev->created_at)->format('d/m/Y') }}</td>
                        <td>{{ ucfirst($dev->type) }}</td>
                        <td>{{ $dev->title }}</td>
                        <td>{{ $dev->status_label }}</td>
                        <td class="text-right">${{ number_format($dev->amount, 2) }}</td>
                        <td class="text-right">${{ number_format($dev->paid_toward, 2) }}</td>
                        <td class="text-right font-bold">${{ number_format($dev->dev_balance, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="4">SUBTOTAL REQUERIMIENTOS</td>
                    <td class="text-right">${{ number_format($devsTotalValue, 2) }}</td>
                    <td class="text-right">${{ number_format($devsTotalPaid, 2) }}</td>
                    <td class="text-right">${{ number_format($devsTotalPending, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    @if($loans->isNotEmpty())
        <div class="section-title">Detalle de Préstamos</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                @php $loansPending = 0; @endphp
                @foreach($loans as $loan)
                    @php if($loan->status === 'pendiente') { $loansPending += ($loan->type === 'entregado' ? $loan->amount : -$loan->amount); } @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($loan->loan_date)->format('d/m/Y') }}</td>
                        <td>{{ $loan->type_label }}</td>
                        <td>{{ $loan->description }}</td>
                        <td>{{ $loan->status_label }}</td>
                        <td class="text-right" style="color: {{ $loan->type === 'recibido' ? '#27ae60' : '#e74c3c' }};">
                            {{ $loan->type === 'recibido' ? '+' : '-' }}${{ number_format($loan->amount, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($credits->isNotEmpty())
        <div class="section-title">Créditos Vinculados (Mis Deudas con {{ $client->name }})</div>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 80px;">Fecha</th>
                    <th>Acreedor / Detalle de Abonos</th>
                    <th style="width: 80px;">Estado</th>
                    <th class="text-right" style="width: 80px;">Total</th>
                    <th class="text-right" style="width: 80px;">Abonado</th>
                    <th class="text-right" style="width: 80px;">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($credits as $credit)
                    <tr>
                        <td style="vertical-align: top;">{{ \Carbon\Carbon::parse($credit->credit_date)->format('d/m/Y') }}</td>
                        <td>
                            <div style="font-weight: bold;">{{ $credit->description }}</div>
                            <div style="font-size: 9px; color: #7f8c8d; margin-bottom: 5px;">Acreedor: {{ $credit->creditor_name }}</div>
                            
                            @if($credit->payments->isNotEmpty())
                                @foreach($credit->payments as $cp)
                                    <div class="credit-payment-detail">
                                        • {{ \Carbon\Carbon::parse($cp->payment_date)->format('d/m/Y') }}: 
                                        <strong>${{ number_format($cp->amount, 2) }}</strong> 
                                        ({{ $cp->concept }})
                                    </div>
                                @endforeach
                            @endif
                        </td>
                        <td style="vertical-align: top;">{{ $credit->status_label }}</td>
                        <td class="text-right" style="vertical-align: top;">${{ number_format($credit->total_amount, 2) }}</td>
                        <td class="text-right" style="vertical-align: top; color: #27ae60;">${{ number_format($credit->payments_sum_amount ?? 0, 2) }}</td>
                        <td class="text-right font-bold" style="vertical-align: top; color: #e74c3c;">${{ number_format($credit->total_amount - ($credit->payments_sum_amount ?? 0), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($payments->isNotEmpty())
        <div class="section-title">Historial de Pagos y Abonos (Recibidos de {{ $client->name }})</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Concepto / Referencia</th>
                    <th>Método</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $p)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($p->payment_date)->format('d/m/Y') }}</td>
                        <td>
                            <div style="font-weight: bold;">{{ $p->development ? 'Desarrollo: ' . $p->development->title : 'Abono general' }}</div>
                            @if($p->notes) <div style="font-size: 9px; color: #7f8c8d;">{{ $p->notes }}</div> @endif
                            @if($p->reference) <div style="font-size: 9px; color: #7f8c8d;">Ref: {{ $p->reference }}</div> @endif
                        </td>
                        <td>{{ ucfirst($p->method) }}</td>
                        <td class="text-right font-bold" style="color: #27ae60;">${{ number_format($p->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3">TOTAL PAGOS RECIBIDOS</td>
                    <td class="text-right">${{ number_format($payments->sum('amount'), 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="footer">
        Generado automáticamente por MIK Software Control — {{ date('d/m/Y H:i') }}
    </div>

</body>
</html>
