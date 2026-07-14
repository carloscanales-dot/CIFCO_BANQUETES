<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportesExport;

class ReporteController extends Controller
{
    /**
     * Muestra la vista principal de reportes
     */
    public function index()
    {
        return Inertia::render('Reportes/Ventas/Index', [
            'stations' => $this->getStations(),
            'products' => $this->getProducts(),
        ]);
    }

    /**
     * Obtiene los datos del reporte con filtros
     */
    public function data(Request $request)
    {
        $query = DB::table('transactions as t')
            ->leftJoin('stations as s', 't.station_id', '=', 's.id')
            ->leftJoin('users as u', 't.user_id', '=', 'u.id')
            ->select(
                't.id',
                't.transaction_date',
                't.amount',
                't.status_id',
                't.payment_method_id',
                's.station_name',
                'u.name as user_name'
            )
            ->where('t.status_id', 1) // Solo transacciones completadas
            ->where('t.transaction_type_id', 1); // Solo ventas normales

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

        if ($request->filled('product_id')) {
            $query->whereExists(function ($subQuery) use ($request) {
                $subQuery->select(DB::raw(1))
                    ->from('transaction_detail')
                    ->whereColumn('transaction_detail.transaction_id', 't.id')
                    ->where('transaction_detail.product_id', $request->product_id);
            });
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

        // Calcular resumen general
        $totalMonto = $transactions->sum('amount');
        $totalTransacciones = $transactions->count();
        $productosVendidos = DB::table('transaction_detail')
            ->whereIn('transaction_id', $transactions->pluck('id'))
            ->sum('quantity');

        // Estadísticas por producto (productos más vendidos)
        $productosMasVendidos = DB::table('transaction_detail as td')
            ->leftJoin('products as p', 'td.product_id', '=', 'p.id')
            ->whereIn('td.transaction_id', $transactions->pluck('id'))
            ->select(
                'p.product_name',
                DB::raw('SUM(td.quantity) as cantidad_vendida'),
                DB::raw('SUM(td.total) as total_vendido'),
                DB::raw('AVG(td.unit_price) as precio_promedio')
            )
            ->groupBy('p.product_name')
            ->orderByDesc('cantidad_vendida')
            ->limit(10)
            ->get();

        // Totales por método de pago
        $totalesPorMetodoPago = DB::table('transactions as t')
            ->leftJoin('payment_method as pm', 't.payment_method_id', '=', 'pm.payment_method_id')
            ->whereIn('t.id', $transactions->pluck('id'))
            ->select(
                'pm.payment_method',
                DB::raw('COUNT(*) as cantidad_transacciones'),
                DB::raw('SUM(t.amount) as total')
            )
            ->groupBy('pm.payment_method')
            ->get();

        // Ventas por estación
        $ventasPorEstacion = DB::table('transactions as t')
            ->leftJoin('stations as s', 't.station_id', '=', 's.id')
            ->whereIn('t.id', $transactions->pluck('id'))
            ->select(
                's.station_name',
                DB::raw('COUNT(*) as cantidad_transacciones'),
                DB::raw('SUM(t.amount) as total')
            )
            ->groupBy('s.station_name')
            ->orderByDesc('total')
            ->get();

        // Ventas por día (últimos 7 días o período seleccionado)
        $ventasPorDia = DB::table('transactions as t')
            ->whereIn('t.id', $transactions->pluck('id'))
            ->select(
                DB::raw('DATE(t.transaction_date) as fecha'),
                DB::raw('COUNT(*) as cantidad_transacciones'),
                DB::raw('SUM(t.amount) as total')
            )
            ->groupBy(DB::raw('DATE(t.transaction_date)'))
            ->orderBy('fecha', 'desc')
            ->limit(30)
            ->get();

        return response()->json([
            'transactions' => $transactions,
            'total_monto' => $totalMonto,
            'total_transacciones' => $totalTransacciones,
            'productos_vendidos' => $productosVendidos,
            'promedio_ticket' => $totalTransacciones > 0 ? $totalMonto / $totalTransacciones : 0,
            'productos_mas_vendidos' => $productosMasVendidos,
            'totales_por_metodo_pago' => $totalesPorMetodoPago,
            'ventas_por_estacion' => $ventasPorEstacion,
            'ventas_por_dia' => $ventasPorDia,
        ]);
    }

    /**
     * Descarga el reporte en formato PDF
     */
    public function downloadPDF(Request $request)
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
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('t.transaction_date', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('t.transaction_date', '<=', $request->fecha_fin);
        }

        if ($request->filled('station_id')) {
            $query->where('t.station_id', $request->station_id);
        }

        if ($request->filled('product_id')) {
            $query->whereExists(function ($subQuery) use ($request) {
                $subQuery->select(DB::raw(1))
                    ->from('transaction_detail')
                    ->whereColumn('transaction_detail.transaction_id', 't.id')
                    ->where('transaction_detail.product_id', $request->product_id);
            });
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

       // Productos más vendidos
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

        // Ventas por método de pago
        $ventasPorMetodo = DB::table('transactions as t')
            ->leftJoin('payment_method as pm', 't.payment_method_id', '=', 'pm.payment_method_id')
            ->whereIn('t.id', $transactions->pluck('id'))
            ->select(
                'pm.payment_method',
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(t.amount) as total')
            )
            ->groupBy('pm.payment_method')
            ->get();

        // Ventas por estación
        $ventasPorEstacion = DB::table('transactions as t')
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
        if ($request->filled('product_id')) {
            $product = DB::table('products')->find($request->product_id);
            $filtros['producto'] = $product->product_name ?? 'N/A';
        }

        $data = [
            'transactions' => $transactions,
            'total_monto' => $totalMonto,
            'total_transacciones' => $totalTransacciones,
            'productos_vendidos' => $productosVendidos,
            'productos_mas_vendidos' => $productosMasVendidos,
            'ventas_por_metodo' => $ventasPorMetodo,
            'ventas_por_estacion' => $ventasPorEstacion,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'filtros' => $filtros,
        ];

        $pdf = Pdf::loadView('reports.ventas-pdf', $data);
        $pdf->setPaper('letter', 'portrait');

        return $pdf->download('reporte_ventas_' . now()->format('YmdHis') . '.pdf');
    }

    /**
     * Descarga el reporte en formato Excel
     */
    public function downloadExcel(Request $request)
    {
        $filters = $request->only(['fecha_inicio', 'fecha_fin', 'station_id', 'product_id']);

        return Excel::download(
            new ReportesExport($filters),
            'reporte_ventas_' . now()->format('YmdHis') . '.xlsx'
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
     * Obtiene la lista de productos
     */
    private function getProducts()
    {
        return DB::table('products')
            ->select('id', 'product_name')
            ->orderBy('product_name')
            ->get();
    }
}
