<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tickets PDF</title>
    <style>
        /* Reset para compatibilidad con PDF */
        body { margin: 0; padding: 0; }
        * { box-sizing: border-box; }

        /* Contenedor principal */
        .page {
            width: 100%;
            padding: 2mm;
            page-break-after: always;
        }

        /* Tabla simulada con divs (más compatible que CSS Grid/Flex en PDF) */
        .grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        .row {
            display: table-row;
        }

    .cell {
        display: table-cell;
        width: calc(100% / {{ $columns }});
        height: 120mm; /* puedes ajustar esto según el espacio que necesites */
        text-align: center;
        background-image: url("{{ public_path('arte-bistro.jpeg') }}");
        background-size: cover; /* hace que ocupe todo el espacio */
        background-repeat: no-repeat;
        background-position: center;
        vertical-align: top;
        border: 1px solid #eee;
        padding: 0; /* quitar el padding para que el fondo sea total */
        position: relative;
    }

   .content {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        padding: 10mm;
        display: grid;
        grid-template-columns: 1fr 1fr;
        align-items: start; /* Cambiado de center a start */
        justify-items: center;
        gap: 5mm;
    }

    .qr-code {
        display: flex;
        justify-content: center;
        align-items: center;
        width: {{ $qrSize }}px !important;
        height: {{ $qrSize }}px !important;
        margin-top: 50mm; /* Eliminado el margen superior */
    }

    .text-content {
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: flex-start;
        margin-top: -15mm; /* Ajusta este valor para subir el texto */
    }

    .product-name {
        font-size: 12pt;
        font-weight: bold;
        margin-bottom: 2mm;
        margin-left: 30mm;
    }

    .price {
        font-size: 11pt;
        color: #f50707;
        margin-left: 30mm;
        font-weight: bold;
    }

    .id-ticket {
        font-size: 12pt;
        margin-left: 30mm;
        margin-bottom: 2mm;
        font-weight: bold;
    }

    </style>
</head>
<body>
    <div class="page">
        <div class="grid">
            @foreach(array_chunk($data, $columns) as $row)
            <div class="row">
                @foreach($row as $ticket)
                <div class="cell">
                <div class="content">
                    {{-- Columna izquierda (QR) --}}
                    <div class="qr-code">
                        {!! DNS2D::getBarcodeHTML($ticket['uuid'], 'QRCODE', 5, 5) !!}
                    </div>

                    {{-- Columna derecha (Texto) --}}
                    <div class="text-content">
                        <div class="id-ticket">{{ $ticket['uuid'] }}</div>
                        <div class="product-name">{{ $ticket['product_name'] }}</div>
                        <div class="price">CORTESIA</div>
                    </div>
                </div>
            </div>
                @endforeach
            </div>
            @endforeach
        </div>
    </div>
</body>
</html>
