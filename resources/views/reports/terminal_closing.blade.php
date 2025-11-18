<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cierre de Terminal</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            width: 120px;
            margin-bottom: 10px;
        }

        h2 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 15px;
        }

        th, td {
            border: 1px solid #444;
            padding: 6px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
            font-weight: bold;
        }

        .totals-table td {
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .summary-box {
            border: 1px solid #444;
            padding: 10px;
            margin-top: 15px;
        }

    </style>
</head>
<body>

    <!-- ENCABEZADO -->
    <div class="header">
        <!-- 👇 INSERTA AQUÍ TU LOGO -->
        {{-- <img src="{{ public_path('ruta/a/tu/logo.png') }}" alt="Logo"> --}}

        <h2>Cierre de Terminal</h2>
        <small>Fecha de generación: {{ now()->format('d/m/Y H:i') }}</small>
    </div>

    <!-- INFORMACIÓN PRINCIPAL -->
    <div class="section-title">Información de la Terminal</div>
    <table>
        <tr>
            <th>Terminal</th>
            <td>{{ $terminal->terminal_name }}</td>

            <th>Estación</th>
            <td>{{ $station->station_name }}</td>
        </tr>

        <tr>
            <th>Cajero</th>
            <td>{{ $cashier->name ?? 'N/A' }}</td>

            <th>ID Apertura</th>
            <td>{{ $opening->id }}</td>
        </tr>

        <tr>
            <th>Fecha Apertura</th>
            <td>{{ $opening->opening_date->format('d/m/Y H:i') }}</td>

            <th>Fecha Cierre</th>
            <td>{{ $closing->closing_date->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <!-- TOTALES POR MÉTODO DE PAGO -->
    <div class="section-title">Totales por Método de Pago</div>
    <table class="totals-table">
        <tr>
            <th>Método</th>
            <th class="text-right">Total</th>
        </tr>
        <tr>
            <td>Efectivo</td>
            <td class="text-right">${{ number_format($totalCash, 2) }}</td>
        </tr>
        <tr>
            <td>Tarjeta</td>
            <td class="text-right">${{ number_format($totalCard, 2) }}</td>
        </tr>
        <tr>
            <td>Chivo Wallet</td>
            <td class="text-right">${{ number_format($totalChivo, 2) }}</td>
        </tr>
        <tr>
            <th>Total General</th>
            <th class="text-right">${{ number_format($totalTransacted, 2) }}</th>
        </tr>
    </table>

    <!-- DETALLES DE PRODUCTOS -->
    <div class="section-title">Detalle de Productos Vendidos</div>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th class="text-right">Cant.</th>
                <th class="text-right">P/U</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($details as $item)
                <tr>
                    <td>{{ $item['product_name'] }}</td>
                    <td class="text-right">{{ $item['quantity'] }}</td>
                    <td class="text-right">${{ number_format($item['unit_price'], 2) }}</td>
                    <td class="text-right">${{ number_format($item['total'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- RESUMEN DEL CIERRE -->
    <div class="section-title">Resumen del Cierre</div>
    <div class="summary-box">
        <p><strong>Monto Esperado:</strong> ${{ number_format($closing->expected_amount, 2) }}</p>
        <p><strong>Monto Real:</strong> ${{ number_format($closing->real_amount, 2) }}</p>
        <p><strong>Diferencia:</strong> ${{ number_format($closing->closing_balance, 2) }}</p>

        @if ($closing->notes)
        <p><strong>Observaciones:</strong> {{ $closing->notes }}</p>
        @endif
    </div>

</body>
</html>
