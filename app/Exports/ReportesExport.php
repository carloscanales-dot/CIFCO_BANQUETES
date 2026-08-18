<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportesExport implements WithMultipleSheets
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    /**
     * Retorna múltiples hojas
     */
    public function sheets(): array
    {
        return [
            new TransaccionesSheet($this->filters),
            new DetalleProductosSheet($this->filters),
            new EstadisticasSheet($this->filters),
        ];
    }
}

/**
 * Hoja 1: Resumen de Transacciones
 */
class TransaccionesSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = DB::table('transactions as t')
            ->leftJoin('stations as s', 't.station_id', '=', 's.id')
            ->leftJoin('users as u', 't.user_id', '=', 'u.id')
            ->leftJoin('payment_method as pm', 't.payment_method_id', '=', 'pm.payment_method_id')
            ->select(
                't.id',
                't.transaction_date',
                't.amount',
                's.station_name',
                'u.name as user_name',
                'pm.payment_method'
            )
            ->where('t.status_id', 1)
            ->where('t.transaction_type_id', 1); // Solo ventas normales

        // Aplicar filtros
        if (!empty($this->filters['fecha_inicio'])) {
            $query->where('t.jornada', '>=', $this->filters['fecha_inicio']);
        }

        if (!empty($this->filters['fecha_fin'])) {
            $query->where('t.jornada', '<=', $this->filters['fecha_fin']);
        }

        if (!empty($this->filters['station_id'])) {
            $query->where('t.station_id', $this->filters['station_id']);
        }

        if (!empty($this->filters['product_id'])) {
            $query->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('transaction_detail')
                    ->whereColumn('transaction_detail.transaction_id', 't.id')
                    ->where('transaction_detail.product_id', $this->filters['product_id']);
            });
        }

        return $query->orderBy('t.transaction_date', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'ID Transacción',
            'Fecha y Hora',
            'Monto Total',
            'Estación',
            'Cajero',
            'Método de Pago'
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->id,
            date('d/m/Y H:i', strtotime($transaction->transaction_date)),
            '$' . number_format($transaction->amount, 2),
            $transaction->station_name ?? 'N/A',
            $transaction->user_name ?? 'N/A',
            $transaction->payment_method ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function title(): string
    {
        return 'Resumen Transacciones';
    }
}

/**
 * Hoja 2: Detalle de Productos por Transacción
 */
class DetalleProductosSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        // Primero obtener las transacciones con filtros
        $query = DB::table('transactions as t')
            ->select('t.id')
            ->where('t.status_id', 1)
            ->where('t.transaction_type_id', 1);

        // Aplicar filtros
        if (!empty($this->filters['fecha_inicio'])) {
            $query->where('t.jornada', '>=', $this->filters['fecha_inicio']);
        }

        if (!empty($this->filters['fecha_fin'])) {
            $query->where('t.jornada', '<=', $this->filters['fecha_fin']);
        }

        if (!empty($this->filters['station_id'])) {
            $query->where('t.station_id', $this->filters['station_id']);
        }

        if (!empty($this->filters['product_id'])) {
            $query->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('transaction_detail')
                    ->whereColumn('transaction_detail.transaction_id', 't.id')
                    ->where('transaction_detail.product_id', $this->filters['product_id']);
            });
        }

        $transactionIds = $query->pluck('id');

        // Ahora obtener los detalles de esas transacciones
        return DB::table('transaction_detail as td')
            ->leftJoin('transactions as t', 'td.transaction_id', '=', 't.id')
            ->leftJoin('products as p', 'td.product_id', '=', 'p.id')
            ->leftJoin('stations as s', 't.station_id', '=', 's.id')
            ->select(
                't.id as transaction_id',
                't.transaction_date',
                's.station_name',
                'p.product_name',
                'td.quantity',
                'td.unit_price',
                'td.total'
            )
            ->whereIn('td.transaction_id', $transactionIds)
            ->orderBy('t.transaction_date', 'desc')
            ->orderBy('td.transaction_id', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID Transacción',
            'Fecha',
            'Estación',
            'Producto',
            'Cantidad',
            'Precio Unitario',
            'Subtotal'
        ];
    }

    public function map($detail): array
    {
        return [
            $detail->transaction_id,
            date('d/m/Y H:i', strtotime($detail->transaction_date)),
            $detail->station_name ?? 'N/A',
            $detail->product_name ?? 'N/A',
            $detail->quantity,
            '$' . number_format($detail->unit_price, 2),
            '$' . number_format($detail->total, 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function title(): string
    {
        return 'Detalle de Productos';
    }
}

/**
 * Hoja 3: Estadísticas y Análisis
 */
class EstadisticasSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        // Obtener transacciones filtradas
        $query = DB::table('transactions as t')
            ->select('t.id', 't.payment_method_id', 't.station_id', 't.amount')
            ->where('t.status_id', 1)
            ->where('t.transaction_type_id', 1);

        if (!empty($this->filters['fecha_inicio'])) {
            $query->where('t.jornada', '>=', $this->filters['fecha_inicio']);
        }
        if (!empty($this->filters['fecha_fin'])) {
            $query->where('t.jornada', '<=', $this->filters['fecha_fin']);
        }
        if (!empty($this->filters['station_id'])) {
            $query->where('t.station_id', $this->filters['station_id']);
        }

        $transactionIds = $query->pluck('id');

        // Productos más vendidos
        $topProducts = DB::table('transaction_detail as td')
            ->leftJoin('products as p', 'td.product_id', '=', 'p.id')
            ->select(
                'p.product_name',
                DB::raw('SUM(td.quantity) as total_cantidad'),
                DB::raw('SUM(td.total) as total_ventas')
            )
            ->whereIn('td.transaction_id', $transactionIds)
            ->groupBy('p.product_name')
            ->orderByDesc('total_cantidad')
            ->limit(10)
            ->get();

        // Ventas por método de pago. Las mixtas (método 4) se reparten en su
        // parte efectivo y su parte tarjeta (igual que el arqueo de caja).
        $txForPago = DB::table('transactions')
            ->whereIn('id', $transactionIds)
            ->select('payment_method_id', 'amount', 'amount_cash', 'amount_card')
            ->get();

        $cashTotal  = $txForPago->where('payment_method_id', 1)->sum('amount')
                    + $txForPago->where('payment_method_id', 4)->sum('amount_cash');
        $cardTotal  = $txForPago->where('payment_method_id', 2)->sum('amount')
                    + $txForPago->where('payment_method_id', 4)->sum('amount_card');
        $chivoTotal = $txForPago->where('payment_method_id', 3)->sum('amount');

        $cashCount  = $txForPago->where('payment_method_id', 1)->count()
                    + $txForPago->where('payment_method_id', 4)->where('amount_cash', '>', 0)->count();
        $cardCount  = $txForPago->where('payment_method_id', 2)->count()
                    + $txForPago->where('payment_method_id', 4)->where('amount_card', '>', 0)->count();
        $chivoCount = $txForPago->where('payment_method_id', 3)->count();

        $ventasPorMetodo = collect([
            (object) ['payment_method' => 'EFECTIVO', 'total_transacciones' => $cashCount,  'total_monto' => round($cashTotal, 2)],
            (object) ['payment_method' => 'TARJETA',  'total_transacciones' => $cardCount,  'total_monto' => round($cardTotal, 2)],
            (object) ['payment_method' => 'CHIVO',    'total_transacciones' => $chivoCount, 'total_monto' => round($chivoTotal, 2)],
        ])->filter(fn($r) => $r->total_monto > 0 || $r->total_transacciones > 0)->values();

        // Ventas por estación
        $ventasPorEstacion = DB::table('transactions as t')
            ->leftJoin('stations as s', 't.station_id', '=', 's.id')
            ->select(
                's.station_name',
                DB::raw('COUNT(*) as total_transacciones'),
                DB::raw('SUM(t.amount) as total_monto')
            )
            ->whereIn('t.id', $transactionIds)
            ->groupBy('s.station_name')
            ->orderByDesc('total_monto')
            ->get();

        // Construir datos para el Excel
        $data = collect();

        // Sección: Productos Más Vendidos
        $data->push(['PRODUCTOS MÁS VENDIDOS']);
        $data->push(['Producto', 'Cantidad Vendida', 'Total en Ventas']);
        foreach ($topProducts as $product) {
            $data->push([
                $product->product_name ?? 'N/A',
                $product->total_cantidad,
                '$' . number_format($product->total_ventas, 2)
            ]);
        }

        $data->push(['']); // Espacio

        // Sección: Ventas por Método de Pago
        $data->push(['VENTAS POR MÉTODO DE PAGO']);
        $data->push(['Método de Pago', 'Transacciones', 'Total Monto']);
        foreach ($ventasPorMetodo as $metodo) {
            $data->push([
                $metodo->payment_method ?? 'N/A',
                $metodo->total_transacciones,
                '$' . number_format($metodo->total_monto, 2)
            ]);
        }

        $data->push(['']); // Espacio

        // Sección: Ventas por Estación
        $data->push(['VENTAS POR ESTACIÓN']);
        $data->push(['Estación', 'Transacciones', 'Total Monto']);
        foreach ($ventasPorEstacion as $estacion) {
            $data->push([
                $estacion->station_name ?? 'N/A',
                $estacion->total_transacciones,
                '$' . number_format($estacion->total_monto, 2)
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return []; // Sin encabezados porque usamos títulos personalizados
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }

    public function title(): string
    {
        return 'Estadísticas y Análisis';
    }
}
