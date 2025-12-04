<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Acta de Cierre de Caja</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 14px;
        }

        /* HEADER */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .header-table td {
            border: none;
            vertical-align: middle;
            padding: 0;
        }

        .header-left img {
            width: 110px;
        }

        .header-right img {
            width: 80px;
            float: right;
        }

        .header-center {
            text-align: center;
            font-weight: bold;
            line-height: 1.15;
        }

        .header-center h3 {
            margin: 0;
            font-size: 13px;
            font-weight: bold;
        }

        /* SECTION TITLES */
        .section-title {
            font-size: 11px;
            font-weight: bold;
            margin: 8px 0 3px 0;
            text-transform: uppercase;
        }

        /* TABLES */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        th,
        td {
            border: 1px solid #000;
            font-size: 10px;
            padding: 3px 4px;
        }

        th {
            background-color: #f2f2f2;
            text-align: left;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        /* SIGNATURES */
        .signatures {
            width: 100%;
            margin-top: 20px;
            table-layout: fixed;
        }

        .signatures td {
            text-align: center;
            padding-top: 25px;
        }

        .sign-line {
            border-top: 1px solid #000;
            width: 65%;
            margin: 0 auto;
            padding-top: 2px;
            font-size: 9px;
        }
    </style>
</head>

<body>

    <!-- HEADER ORIGINAL PERO COMPACTADO -->
    <table class="header-table">
        <tr>
            <td class="header-left">
                <img src="{{ public_path('Logo-Cifco.png') }}" alt="Logo CIFCO">
            </td>

            <td class="header-center">
                <h3>CENTRO INTERNACIONAL DE FERIAS Y CONVENCIONES DE EL SALVADOR</h3>
                <h3>{{ $station->fair->fair_name ?? '' }}</h3>
                <h3>Acta de cierre de caja</h3>
            </td>

            <td class="header-right">
                <img src="{{ public_path('Logo-ElSalvador.png') }}" alt="Logo El Salvador">
            </td>
        </tr>
    </table>

    <!-- INFORMACIÓN GENERAL -->
    <div class="section-title">Información General</div>
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
        <tr>
            <th colspan ="2">Fondo de cambio</th>
            <td colspan="2" class="text-right"><strong>${{ number_format($opening->change_fund, 2) }}</strong></td>
        </tr>


    </table>


    <!-- PRODUCTOS -->
    <div class="section-title">Detalle de Productos Vendidos</div>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th class="text-right">Total de la venta</th>
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

    <!-- MÉTODOS DE PAGO -->
    <table>
        <tr>
            <th>PAGOS EN EFECTIVO</th>
            <td class="text-right">${{ number_format($totalCash, 2) }}</td>
        </tr>
        <tr>
            <th>PAGOS EN POS</th>
            <td class="text-right">${{ number_format($totalCard, 2) }}</td>
        </tr>
        <tr>
            <th>PAGOS EN CHIVO WALLET</th>
            <td class="text-right">${{ number_format($totalChivo, 2) }}</td>
        </tr>
        <tr>
            <th>TOTAL VENTA</th>
            <th class="text-right">${{ number_format($totalTransacted, 2) }}</th>
        </tr>
    </table>

    <!-- RESUMEN CIERRE -->
    <div class="section-title">Resumen Cierre</div>
    <table>
        <tr>
            <th>Método</th>
            <th class="text-right">Totales</th>
        </tr>

        <tr>
            <td>Total efectivo recibido</td>
            <td class="text-right">${{ number_format($closing->real_amount, 2) }}</td>
        </tr>

        <tr>
            <td>Total vouchers recibido</td>
            <td class="text-right">${{ number_format($closing->pos_real_amount, 2) }}</td>
        </tr>

        <tr>
            <td>Total Bitcoin</td>
            <td class="text-right">${{ number_format($totalChivo, 2) }}</td>
        </tr>

        <tr>
            <th>Total General</th>
            <th class="text-right">
                ${{ number_format($closing->real_amount + $closing->pos_real_amount + $totalChivo, 2) }}
            </th>
        </tr>

        <tr>
            <th>Diferencia</th>
            <th class="text-right">
                ${{ number_format(abs($closing->closing_balance), 2) }}
            </th>
        </tr>


        @if ($closing->notes)
            <tr>
                <th>Observaciones</th>
                <td>{{ $closing->notes }}</td>
            </tr>
        @endif

    </table>

    <!-- FIRMAS -->
    <table class="signatures">
        <tr>
            <td>
                <div class="sign-line"></div>
                Nombre y firma<br>Cajero/a
            </td>

            <td>
                <div class="sign-line"></div>
                Nombre y firma<br>Supervisor de Cajas
            </td>

            <td>
                <div class="sign-line"></div>
                Nombre y firma<br>Oficial de Colecturía
            </td>
        </tr>
    </table>

</body>

</html>
