<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Cierre de Terminal</title>

</head>
<style>
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 12px;
        color: #000;
    }

    /* ================================
       ENCABEZADO SIN BORDES
       ================================ */
    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .header-table td {
        border: none !important;
        /* Oculta bordes */
        vertical-align: middle;
        padding: 0px;
    }

    /* Logo izquierdo */
    .header-left img {
        width: 150px;
    }

    /* Texto centrado */
    .header-center {
        text-align: center;
        font-weight: bold;
        line-height: 1.3;
    }

    .header-center h2 {
        margin: 0;
        font-size: 17px;
        font-weight: bold;
    }

    .header-center small {
        font-size: 11px;
        margin-top: 4px;
        display: flex;
    }

    /* Logo derecho */
    .header-right img {
        width: 110px;
    }

    /* ================================
       SECCIONES Y TABLAS
       ================================ */

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

    th,
    td {
        border: 1px solid #444;
        padding: 6px;
        text-align: left;
    }

    th {
        background: #f0f0f0;
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

    /* ================================
       FIRMAS
       ================================ */
    .signatures-table {
        width: 100%;
        table-layout: fixed;
        margin-top: 50px;
    }

    .signatures-table td {
        width: 33.33%;
        padding-top: 60px;
        text-align: center;
        vertical-align: bottom;
    }

    .line {
        border-top: 1px solid #000;
        width: 80%;
        margin: 0 auto;
        padding-top: 5px;
        font-size: 11px;
    }
</style>

<body>

    <table class="header-table">
        <tr>
            <td class="header-left">
                <img src="{{ public_path('Logo-Cifco.png') }}" alt="Logo Cifco">
            </td>

            <td class="header-center">
                <h2>CENTRO INTERNACIONAL DE FERIAS Y CONVENCIONES</h2>
                <h2>Cierre de Caja</h2>
                <small>Fecha de generación: {{ now()->format('d/m/Y H:i') }}</small>
            </td>

            <td class="header-right" style="text-align: right;">
                <img src="{{ public_path('Logo-ElSalvador.png') }}" alt="Logo El Salvador">
            </td>
        </tr>
    </table>



    <!-- INFORMACIÓN PRINCIPAL -->
    <div class="section-title">Información de la Caja</div>
    <table>
        <tr>
            <th>Caja</th>
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
                <th class="text-right">Cantidad</th>
                <th class="text-right">Precio unitario</th>
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

    <!-- FIRMAS -->
    <table class="signatures-table">
        <tr>
            <td>
                <div class="line">Cajero</div>
            </td>

            <td>
                <div class="line">Encargado de Cajas</div>
            </td>

            <td>
                <div class="line">Jefe</div>
            </td>
        </tr>
    </table>

</body>

</html>
