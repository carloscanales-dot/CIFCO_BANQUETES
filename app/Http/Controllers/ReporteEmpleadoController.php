<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportesEmpleadosExport;

class ReporteEmpleadoController extends Controller
{
    /**
     * Muestra la vista principal de reportes de empleados
     */
    public function index()
    {
        return Inertia::render('Reportes/Empleados/Index', [
            'stations' => $this->getStations(),
            'employees' => $this->getEmployees(),
        ]);
    }

    /**
     * Obtiene los datos del reporte de empleados con filtros
     */
    public function data(Request $request)
    {
        $query = DB::table('transactions as t')
            ->leftJoin('stations as s', 't.station_id', '=', 's.id')
            ->leftJoin('users as u', 't.user_id', '=', 'u.id')
            ->leftJoin('employee as e', 't.employee_id', '=', 'e.employee_id')
            ->select(
                't.id',
                't.transaction_date',
                't.amount',
                't.status_id',
                's.station_name',
                'u.name as user_name',
                'e.employee_name',
                'e.employee_id'
            )
            ->where('t.status_id', 1) // Solo transacciones completadas
            ->where('t.transaction_type_id', 2); // Solo créditos a empleados

        // Aplicar filtros
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('t.transaction_date', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('t.transaction_date', '<=', $request->fecha_fin);
        }

        if ($request->filled('station_id')) {
            $query->where('t.station_id', $request->station_id);
        }

        if ($request->filled('employee_id')) {
            $query->where('t.employee_id', $request->employee_id);
        }

        $transactions = $query->orderBy('t.transaction_date', 'desc')->get();

        // Cargar detalles de productos para cada transacción
        foreach ($transactions as $transaction) {
            $transaction->details = DB::table('transaction_detail as td')
                ->leftJoin('products as p', 'td.product_id', '=', 'p.id')
                ->where('td.transaction_id', $transaction->id)
                ->select(
                    'p.product_name',
                    'td.quantity',
                    'td.unit_price',
                    'td.total'
                )
                ->get();
        }

        // Calcular resumen
        $totalMonto = $transactions->sum('amount');
        $totalTransacciones = $transactions->count();
        $empleadosUnicos = $transactions->unique('employee_id')->count();
        $productosVendidos = DB::table('transaction_detail')
            ->whereIn('transaction_id', $transactions->pluck('id'))
            ->sum('quantity');

        return response()->json([
            'transactions' => $transactions,
            'total_monto' => $totalMonto,
            'total_transacciones' => $totalTransacciones,
            'empleados_unicos' => $empleadosUnicos,
            'productos_vendidos' => $productosVendidos,
            'promedio_ticket' => $totalTransacciones > 0 ? $totalMonto / $totalTransacciones : 0,
        ]);
    }

    /**
     * Descarga el reporte de empleados en formato PDF
     */
    public function downloadPDF(Request $request)
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
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('t.transaction_date', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('t.transaction_date', '<=', $request->fecha_fin);
        }

        if ($request->filled('station_id')) {
            $query->where('t.station_id', $request->station_id);
        }

        if ($request->filled('employee_id')) {
            $query->where('t.employee_id', $request->employee_id);
        }

        $transactions = $query->orderBy('t.transaction_date', 'desc')->get();

        // Cargar detalles de productos para cada transacción
        foreach ($transactions as $transaction) {
            $transaction->details = DB::table('transaction_detail as td')
                ->leftJoin('products as p', 'td.product_id', '=', 'p.id')
                ->where('td.transaction_id', $transaction->id)
                ->select(
                    'p.product_name',
                    'td.quantity',
                    'td.unit_price',
                    'td.total'
                )
                ->get();
        }

        // Calcular totales
        $totalMonto = $transactions->sum('amount');
        $totalTransacciones = $transactions->count();
        $productosVendidos = DB::table('transaction_detail')
            ->whereIn('transaction_id', $transactions->pluck('id'))
            ->sum('quantity');

        // Productos más vendidos a empleados
        $productosMasVendidos = DB::table('transaction_detail as td')
            ->leftJoin('products as p', 'td.product_id', '=', 'p.id')
            ->whereIn('td.transaction_id', $transactions->pluck('id'))
            ->select(
                'p.product_name',
                DB::raw('SUM(td.quantity) as cantidad_vendida'),
                DB::raw('SUM(td.total) as total_vendido')
            )
            ->groupBy('p.product_name')
            ->orderByDesc('cantidad_vendida')
            ->limit(10)
            ->get();

        // Créditos por empleado
        $creditosPorEmpleado = DB::table('transactions as t')
            ->leftJoin('employee as e', 't.employee_id', '=', 'e.employee_id')
            ->whereIn('t.id', $transactions->pluck('id'))
            ->select(
                'e.employee_name',
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(t.amount) as total')
            )
            ->groupBy('e.employee_name')
            ->orderByDesc('total')
            ->get();

        // Créditos por estación
        $creditosPorEstacion = DB::table('transactions as t')
            ->leftJoin('stations as s', 't.station_id', '=', 's.id')
            ->whereIn('t.id', $transactions->pluck('id'))
            ->select(
                's.station_name',
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(t.amount) as total')
            )
            ->groupBy('s.station_name')
            ->orderByDesc('total')
            ->get();

        // Obtener nombres de filtros aplicados
        $filtros = [];
        if ($request->filled('station_id')) {
            $station = DB::table('stations')->find($request->station_id);
            $filtros['estacion'] = $station->station_name ?? 'N/A';
        }
        if ($request->filled('employee_id')) {
            $employee = DB::table('employee')->where('employee_id', $request->employee_id)->first();
            $filtros['empleado'] = $employee->employee_name ?? 'N/A';
        }

        $data = [
            'transactions' => $transactions,
            'total_monto' => $totalMonto,
            'total_transacciones' => $totalTransacciones,
            'productos_vendidos' => $productosVendidos,
            'productos_mas_vendidos' => $productosMasVendidos,
            'creditos_por_empleado' => $creditosPorEmpleado,
            'creditos_por_estacion' => $creditosPorEstacion,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'filtros' => $filtros,
        ];

        $pdf = Pdf::loadView('reports.empleados-pdf', $data);
        $pdf->setPaper('letter', 'portrait');

        return $pdf->download('reporte_empleados_' . now()->format('YmdHis') . '.pdf');
    }

    /**
     * Descarga el reporte de empleados en formato Excel
     */
    public function downloadExcel(Request $request)
    {
        $filters = $request->only(['fecha_inicio', 'fecha_fin', 'station_id', 'employee_id']);

        return Excel::download(
            new ReportesEmpleadosExport($filters),
            'reporte_empleados_' . now()->format('YmdHis') . '.xlsx'
        );
    }

    /**
     * Obtiene la lista de estaciones
     */
    private function getStations()
    {
        return DB::table('stations')
            ->select('id', 'station_name')
            ->orderBy('station_name')
            ->get();
    }

    /**
     * Obtiene la lista de empleados
     */
    private function getEmployees()
    {
        return DB::table('employee')
            ->select(
                'employee_id as id',
                'employee_name as name'
            )
            ->orderBy('employee_name')
            ->get();
    }
}
