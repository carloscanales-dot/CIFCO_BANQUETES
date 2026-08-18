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

class ReportesEmpleadosExport implements WithMultipleSheets
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
            new TransaccionesEmpleadosSheet($this->filters),
            new DetalleProductosEmpleadosSheet($this->filters),
            new EstadisticasEmpleadosSheet($this->filters),
        ];
    }
}

/**
 * Hoja 1: Resumen de Transacciones de Empleados
 */
class TransaccionesEmpleadosSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
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
            ->leftJoin('employee as e', 't.employee_id', '=', 'e.employee_id')
            ->leftJoin('payment_method as pm', 't.payment_method_id', '=', 'pm.payment_method_id')
            ->select(
                't.id',
                't.transaction_date',
                't.amount',
                's.station_name',
                'u.name as user_name',
                'e.employee_name',
                'pm.payment_method'
            )
            ->where('t.status_id', 1)
            ->where('t.transaction_type_id', 2); // Solo créditos a empleados

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

        if (!empty($this->filters['employee_id'])) {
            $query->where('t.employee_id', $this->filters['employee_id']);
        }

        return $query->orderBy('t.transaction_date', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'ID Transacción',
            'Fecha',
            'Empleado',
            'Estación',
            'Cajero',
            'Método de Pago',
            'Monto Total'
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->id,
            date('d/m/Y H:i', strtotime($transaction->transaction_date)),
            $transaction->employee_name ?? 'N/A',
            $transaction->station_name ?? 'N/A',
            $transaction->user_name ?? 'N/A',
            $transaction->payment_method ?? 'N/A',
            '$' . number_format($transaction->amount, 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FFC107']]
            ],
        ];
    }

    public function title(): string
    {
        return 'Transacciones Empleados';
    }
}

/**
 * Hoja 2: Detalle de Productos por Transacción
 */
class DetalleProductosEmpleadosSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        // Obtener IDs de transacciones filtradas
        $query = DB::table('transactions as t')
            ->select('t.id')
            ->where('t.status_id', 1)
            ->where('t.transaction_type_id', 2);

        if (!empty($this->filters['fecha_inicio'])) {
            $query->where('t.jornada', '>=', $this->filters['fecha_inicio']);
        }

        if (!empty($this->filters['fecha_fin'])) {
            $query->where('t.jornada', '<=', $this->filters['fecha_fin']);
        }

        if (!empty($this->filters['station_id'])) {
            $query->where('t.station_id', $this->filters['station_id']);
        }

        if (!empty($this->filters['employee_id'])) {
            $query->where('t.employee_id', $this->filters['employee_id']);
        }

        $transactionIds = $query->pluck('id');

        // Obtener detalles de productos
        $details = DB::table('transaction_detail as td')
            ->join('transactions as t', 'td.transaction_id', '=', 't.id')
            ->leftJoin('products as p', 'td.product_id', '=', 'p.id')
            ->leftJoin('employee as e', 't.employee_id', '=', 'e.employee_id')
            ->whereIn('td.transaction_id', $transactionIds)
            ->select(
                't.id as transaction_id',
                't.transaction_date',
                'e.employee_name',
                'p.product_name',
                'td.quantity',
                'td.unit_price',
                'td.total'
            )
            ->orderBy('t.transaction_date', 'desc')
            ->get();

        // Formatear para Excel
        return $details->map(function ($item) {
            return [
                $item->transaction_id,
                date('d/m/Y H:i', strtotime($item->transaction_date)),
                $item->employee_name ?? 'N/A',
                $item->product_name ?? 'N/A',
                $item->quantity,
                '$' . number_format($item->unit_price, 2),
                '$' . number_format($item->total, 2),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID Transacción',
            'Fecha',
            'Empleado',
            'Producto',
            'Cantidad',
            'Precio Unitario',
            'Subtotal'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FFC107']]
            ],
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
class EstadisticasEmpleadosSheet implements FromCollection, WithTitle
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        // Obtener IDs de transacciones filtradas
        $query = DB::table('transactions as t')
            ->select('t.id')
            ->where('t.status_id', 1)
            ->where('t.transaction_type_id', 2);

        if (!empty($this->filters['fecha_inicio'])) {
            $query->where('t.jornada', '>=', $this->filters['fecha_inicio']);
        }

        if (!empty($this->filters['fecha_fin'])) {
            $query->where('t.jornada', '<=', $this->filters['fecha_fin']);
        }

        if (!empty($this->filters['station_id'])) {
            $query->where('t.station_id', $this->filters['station_id']);
        }

        if (!empty($this->filters['employee_id'])) {
            $query->where('t.employee_id', $this->filters['employee_id']);
        }

        $transactionIds = $query->pluck('id');

        $data = collect();

        // ===== SECCIÓN 1: Top 10 Productos Más Vendidos =====
        $data->push(['📊 TOP 10 PRODUCTOS MÁS VENDIDOS A EMPLEADOS']);
        $data->push(['Posición', 'Producto', 'Cantidad Vendida', 'Total en Ventas']);

        $topProducts = DB::table('transaction_detail as td')
            ->leftJoin('products as p', 'td.product_id', '=', 'p.id')
            ->whereIn('td.transaction_id', $transactionIds)
            ->select(
                'p.product_name',
                DB::raw('SUM(td.quantity) as cantidad'),
                DB::raw('SUM(td.total) as total')
            )
            ->groupBy('p.product_name')
            ->orderByDesc('cantidad')
            ->limit(10)
            ->get();

        foreach ($topProducts as $index => $product) {
            $data->push([
                $index + 1,
                $product->product_name ?? 'Sin nombre',
                number_format($product->cantidad),
                '$' . number_format($product->total, 2)
            ]);
        }

        $data->push(['TOTAL', '', number_format($topProducts->sum('cantidad')), '$' . number_format($topProducts->sum('total'), 2)]);
        $data->push([]); // Línea en blanco

        // ===== SECCIÓN 2: Créditos por Empleado =====
        $data->push(['👤 ANÁLISIS DE CRÉDITOS POR EMPLEADO']);
        $data->push(['Empleado', 'Transacciones', 'Total Créditos', '% del Total']);

        $creditosPorEmpleado = DB::table('transactions as t')
            ->leftJoin('employee as e', 't.employee_id', '=', 'e.employee_id')
            ->whereIn('t.id', $transactionIds)
            ->select(
                'e.employee_name',
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(t.amount) as total')
            )
            ->groupBy('e.employee_name')
            ->orderByDesc('total')
            ->get();

        $totalGeneral = $creditosPorEmpleado->sum('total');

        foreach ($creditosPorEmpleado as $empleado) {
            $porcentaje = $totalGeneral > 0 ? ($empleado->total / $totalGeneral) * 100 : 0;
            $data->push([
                $empleado->employee_name ?? 'Sin especificar',
                number_format($empleado->cantidad),
                '$' . number_format($empleado->total, 2),
                number_format($porcentaje, 1) . '%'
            ]);
        }

        $data->push([
            'TOTAL',
            number_format($creditosPorEmpleado->sum('cantidad')),
            '$' . number_format($creditosPorEmpleado->sum('total'), 2),
            '100%'
        ]);
        $data->push([]); // Línea en blanco

        // ===== SECCIÓN 3: Créditos por Estación =====
        $data->push(['🏪 DISTRIBUCIÓN POR ESTACIÓN']);
        $data->push(['Estación', 'Transacciones', 'Total Créditos', '% del Total']);

        $creditosPorEstacion = DB::table('transactions as t')
            ->leftJoin('stations as s', 't.station_id', '=', 's.id')
            ->whereIn('t.id', $transactionIds)
            ->select(
                's.station_name',
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(t.amount) as total')
            )
            ->groupBy('s.station_name')
            ->orderByDesc('total')
            ->get();

        foreach ($creditosPorEstacion as $estacion) {
            $porcentaje = $totalGeneral > 0 ? ($estacion->total / $totalGeneral) * 100 : 0;
            $data->push([
                $estacion->station_name ?? 'Sin especificar',
                number_format($estacion->cantidad),
                '$' . number_format($estacion->total, 2),
                number_format($porcentaje, 1) . '%'
            ]);
        }

        $data->push([
            'TOTAL',
            number_format($creditosPorEstacion->sum('cantidad')),
            '$' . number_format($creditosPorEstacion->sum('total'), 2),
            '100%'
        ]);

        return $data;
    }

    public function title(): string
    {
        return 'Estadísticas y Análisis';
    }
}
