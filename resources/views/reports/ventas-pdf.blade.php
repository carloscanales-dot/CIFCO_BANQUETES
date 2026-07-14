<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de Ventas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
            color: #333;
            padding: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #000;
        }

        .header h1 {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 9pt;
            color: #666;
        }

        .info-section {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 3px;
        }

        .info-section p {
            margin: 4px 0;
            font-size: 8pt;
        }

        .info-section strong {
            font-weight: bold;
        }

        .summary {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .summary-item {
            display: table-cell;
            width: 33.33%;
            padding: 8px;
            text-align: center;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .summary-item .label {
            font-size: 7pt;
            color: #666;
            margin-bottom: 3px;
        }

        .summary-item .value {
            font-size: 12pt;
            font-weight: bold;
            color: #000;
        }

        .transaction-card {
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .transaction-header {
            background-color: #000;
            color: white;
            padding: 6px 10px;
            font-size: 8pt;
            display: table;
            width: 100%;
        }

        .transaction-header .left {
            display: table-cell;
            width: 50%;
        }

        .transaction-header .right {
            display: table-cell;
            width: 50%;
            text-align: right;
        }

        .transaction-info {
            padding: 8px 10px;
            background-color: #f9f9f9;
            font-size: 8pt;
        }

        .transaction-info .info-row {
            margin: 3px 0;
        }

        .transaction-info .info-row strong {
            display: inline-block;
            width: 80px;
            font-weight: bold;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .products-table thead {
            background-color: #e9ecef;
        }

        .products-table thead th {
            padding: 6px 8px;
            text-align: left;
            font-size: 7pt;
            font-weight: bold;
            border-bottom: 2px solid #dee2e6;
        }

        .products-table tbody td {
            padding: 5px 8px;
            font-size: 8pt;
            border-bottom: 1px solid #f0f0f0;
        }

        .products-table thead th:last-child,
        .products-table tbody td:last-child {
            text-align: right;
        }

        .transaction-total {
            padding: 8px 10px;
            background-color: #f0f0f0;
            text-align: right;
            font-weight: bold;
            font-size: 9pt;
            border-top: 2px solid #000;
        }

        .grand-total {
            margin-top: 20px;
            padding: 12px;
            background-color: #000;
            color: white;
            text-align: right;
            font-size: 11pt;
            font-weight: bold;
            border-radius: 3px;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #000;
            text-align: center;
            font-size: 7pt;
            color: #666;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            font-size: 10pt;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DETALLADO DE VENTAS</h1>
        <p>Sistema de Gestión de Banquetes - CIFCO</p>
    </div>

    <div class="info-section">
        <p><strong>Fecha de Generación:</strong> {{ $fecha_generacion }}</p>
        @if($fecha_inicio && $fecha_fin)
            <p><strong>Período:</strong> {{ date('d/m/Y', strtotime($fecha_inicio)) }} - {{ date('d/m/Y', strtotime($fecha_fin)) }}</p>
        @endif
        @if(isset($filtros['estacion']))
            <p><strong>Estación:</strong> {{ $filtros['estacion'] }}</p>
        @endif
        @if(isset($filtros['producto']))
            <p><strong>Producto Filtrado:</strong> {{ $filtros['producto'] }}</p>
        @endif
    </div>

    <div class="summary">
        <div class="summary-item">
            <div class="label">Total en Ventas</div>
            <div class="value">${{ number_format($total_monto, 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Transacciones</div>
            <div class="value">{{ $total_transacciones }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Productos Vendidos</div>
            <div class="value">{{ $productos_vendidos ?? 0 }}</div>
        </div>
    </div>

    @forelse($transactions as $transaction)
    <div class="transaction-card">
        <div class="transaction-header">
            <div class="left">
                <strong>TRANSACCIÓN #{{ $transaction->id }}</strong>
            </div>
            <div class="right">
                {{ date('d/m/Y H:i', strtotime($transaction->transaction_date)) }}
            </div>
        </div>

        <div class="transaction-info">
            <div class="info-row">
                <strong>Estación:</strong> {{ $transaction->station_name ?? 'N/A' }}
            </div>
            <div class="info-row">
                <strong>Cajero:</strong> {{ $transaction->user_name ?? 'N/A' }}
            </div>
            <div class="info-row">
                <strong>Método Pago:</strong> {{ $transaction->payment_method ?? 'N/A' }}
            </div>
        </div>

        @if($transaction->details && count($transaction->details) > 0)
        <table class="products-table">
            <thead>
                <tr>
                    <th>PRODUCTO</th>
                    <th style="text-align: center; width: 60px;">CANT.</th>
                    <th style="text-align: right; width: 70px;">P. UNIT.</th>
                    <th style="text-align: right; width: 70px;">SUBTOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->details as $detail)
                <tr>
                    <td>{{ $detail->product_name }}</td>
                    <td style="text-align: center;">{{ $detail->quantity }}</td>
                    <td class="text-right">${{ number_format($detail->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format($detail->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="transaction-total">
            TOTAL: ${{ number_format($transaction->amount, 2) }}
        </div>
    </div>
    @empty
    <div class="no-data">
        No hay transacciones para mostrar en el período seleccionado
    </div>
    @endforelse

    @if($transactions->count() > 0)
    <div class="grand-total">
        TOTAL GENERAL: ${{ number_format($total_monto, 2) }}
    </div>

    <!-- SECCIÓN DE ESTADÍSTICAS Y ANÁLISIS -->
    <div style="margin-top: 30px; page-break-before: always;">
        <h2 style="font-size: 14pt; font-weight: bold; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 5px;">
            ESTADÍSTICAS Y ANÁLISIS DE VENTAS
        </h2>

        <!-- Top 10 Productos Más Vendidos -->
        @if(isset($productos_mas_vendidos) && count($productos_mas_vendidos) > 0)
        <div style="margin-bottom: 20px;">
            <h3 style="font-size: 11pt; font-weight: bold; margin-bottom: 10px; background-color: #f0f0f0; padding: 5px;">
                📊 Top 10 Productos Más Vendidos
            </h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background-color: #e9ecef;">
                    <tr>
                        <th style="padding: 6px 8px; text-align: left; font-size: 8pt; border: 1px solid #dee2e6; width: 50px;">POSICIÓN</th>
                        <th style="padding: 6px 8px; text-align: left; font-size: 8pt; border: 1px solid #dee2e6;">PRODUCTO</th>
                        <th style="padding: 6px 8px; text-align: right; font-size: 8pt; border: 1px solid #dee2e6; width: 100px;">CANTIDAD</th>
                        <th style="padding: 6px 8px; text-align: right; font-size: 8pt; border: 1px solid #dee2e6; width: 100px;">VENTAS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productos_mas_vendidos as $index => $producto)
                    <tr>
                        <td style="padding: 5px 8px; font-size: 8pt; border: 1px solid #dee2e6; text-align: center; font-weight: bold;">{{ $index + 1 }}</td>
                        <td style="padding: 5px 8px; font-size: 8pt; border: 1px solid #dee2e6;">{{ $producto->product_name ?? 'Sin nombre' }}</td>
                        <td style="padding: 5px 8px; font-size: 8pt; border: 1px solid #dee2e6; text-align: right;">{{ number_format($producto->cantidad_vendida) }}</td>
                        <td style="padding: 5px 8px; font-size: 8pt; border: 1px solid #dee2e6; text-align: right;">${{ number_format($producto->total_vendido, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr style="background-color: #f8f9fa; font-weight: bold;">
                        <td colspan="2" style="padding: 6px 8px; font-size: 8pt; border: 1px solid #dee2e6;">TOTAL</td>
                        <td style="padding: 6px 8px; font-size: 8pt; border: 1px solid #dee2e6; text-align: right;">{{ number_format($productos_mas_vendidos->sum('cantidad_vendida')) }}</td>
                        <td style="padding: 6px 8px; font-size: 8pt; border: 1px solid #dee2e6; text-align: right;">${{ number_format($productos_mas_vendidos->sum('total_vendido'), 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        <!-- Ventas por Método de Pago -->
        @if(isset($ventas_por_metodo) && count($ventas_por_metodo) > 0)
        <div style="margin-bottom: 20px;">
            <h3 style="font-size: 11pt; font-weight: bold; margin-bottom: 10px; background-color: #f0f0f0; padding: 5px;">
                💳 Análisis por Método de Pago
            </h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background-color: #e9ecef;">
                    <tr>
                        <th style="padding: 6px 8px; text-align: left; font-size: 8pt; border: 1px solid #dee2e6;">MÉTODO DE PAGO</th>
                        <th style="padding: 6px 8px; text-align: right; font-size: 8pt; border: 1px solid #dee2e6; width: 120px;">TRANSACCIONES</th>
                        <th style="padding: 6px 8px; text-align: right; font-size: 8pt; border: 1px solid #dee2e6; width: 120px;">TOTAL</th>
                        <th style="padding: 6px 8px; text-align: right; font-size: 8pt; border: 1px solid #dee2e6; width: 80px;">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ventas_por_metodo as $metodo)
                    <tr>
                        <td style="padding: 5px 8px; font-size: 8pt; border: 1px solid #dee2e6;">{{ $metodo->payment_method ?? 'Sin especificar' }}</td>
                        <td style="padding: 5px 8px; font-size: 8pt; border: 1px solid #dee2e6; text-align: right;">{{ number_format($metodo->cantidad) }}</td>
                        <td style="padding: 5px 8px; font-size: 8pt; border: 1px solid #dee2e6; text-align: right;">${{ number_format($metodo->total, 2) }}</td>
                        <td style="padding: 5px 8px; font-size: 8pt; border: 1px solid #dee2e6; text-align: right;">{{ number_format(($metodo->total / $total_monto) * 100, 1) }}%</td>
                    </tr>
                    @endforeach
                    <tr style="background-color: #f8f9fa; font-weight: bold;">
                        <td style="padding: 6px 8px; font-size: 8pt; border: 1px solid #dee2e6;">TOTAL</td>
                        <td style="padding: 6px 8px; font-size: 8pt; border: 1px solid #dee2e6; text-align: right;">{{ number_format($ventas_por_metodo->sum('cantidad')) }}</td>
                        <td style="padding: 6px 8px; font-size: 8pt; border: 1px solid #dee2e6; text-align: right;">${{ number_format($ventas_por_metodo->sum('total'), 2) }}</td>
                        <td style="padding: 6px 8px; font-size: 8pt; border: 1px solid #dee2e6; text-align: right;">100%</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        <!-- Ventas por Estación -->
        @if(isset($ventas_por_estacion) && count($ventas_por_estacion) > 0)
        <div style="margin-bottom: 20px;">
            <h3 style="font-size: 11pt; font-weight: bold; margin-bottom: 10px; background-color: #f0f0f0; padding: 5px;">
                🏪 Rendimiento por Estación
            </h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background-color: #e9ecef;">
                    <tr>
                        <th style="padding: 6px 8px; text-align: left; font-size: 8pt; border: 1px solid #dee2e6;">ESTACIÓN</th>
                        <th style="padding: 6px 8px; text-align: right; font-size: 8pt; border: 1px solid #dee2e6; width: 120px;">TRANSACCIONES</th>
                        <th style="padding: 6px 8px; text-align: right; font-size: 8pt; border: 1px solid #dee2e6; width: 120px;">TOTAL</th>
                        <th style="padding: 6px 8px; text-align: right; font-size: 8pt; border: 1px solid #dee2e6; width: 80px;">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ventas_por_estacion as $estacion)
                    <tr>
                        <td style="padding: 5px 8px; font-size: 8pt; border: 1px solid #dee2e6;">{{ $estacion->station_name ?? 'Sin especificar' }}</td>
                        <td style="padding: 5px 8px; font-size: 8pt; border: 1px solid #dee2e6; text-align: right;">{{ number_format($estacion->cantidad) }}</td>
                        <td style="padding: 5px 8px; font-size: 8pt; border: 1px solid #dee2e6; text-align: right;">${{ number_format($estacion->total, 2) }}</td>
                        <td style="padding: 5px 8px; font-size: 8pt; border: 1px solid #dee2e6; text-align: right;">{{ number_format(($estacion->total / $total_monto) * 100, 1) }}%</td>
                    </tr>
                    @endforeach
                    <tr style="background-color: #f8f9fa; font-weight: bold;">
                        <td style="padding: 6px 8px; font-size: 8pt; border: 1px solid #dee2e6;">TOTAL</td>
                        <td style="padding: 6px 8px; font-size: 8pt; border: 1px solid #dee2e6; text-align: right;">{{ number_format($ventas_por_estacion->sum('cantidad')) }}</td>
                        <td style="padding: 6px 8px; font-size: 8pt; border: 1px solid #dee2e6; text-align: right;">${{ number_format($ventas_por_estacion->sum('total'), 2) }}</td>
                        <td style="padding: 6px 8px; font-size: 8pt; border: 1px solid #dee2e6; text-align: right;">100%</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endif

    <div class="footer">
        <p>Documento generado automáticamente por el Sistema de Gestión de Banquetes CIFCO</p>
        <p>{{ $fecha_generacion }}</p>
    </div>
</body>
</html>
