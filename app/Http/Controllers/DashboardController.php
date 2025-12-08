<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\Printer;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Modules\Caja\Models\PaymentTerminal;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Renderiza la vista principal del dashboard
    public function index()
    {
        return Inertia::render('Dashboard');
    }

    public function landing()
    {
        return Inertia::render('Landing');
    }

    public function ventas()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // buscar la terminal de pago asignada al usuario
        $paymentTerminal = PaymentTerminal::where('user_id', $user->id)->first();

        // Obtener la estación asignada (la primera, por si tuviera más de una)
        $station = $user->stations()->first();

        // Si el usuario no tiene estación → no hay impresora
        if (!$station) {
            return Inertia::render('Ventas', [
                'printer_ip' => null,
                'station_name' => null,
                'fair_name' => null, // <- nada que mostrar
                'terminal_status' => $paymentTerminal?->status_id,
                'payment_terminal_id' => $paymentTerminal?->id,
            ]);
        }

        // Buscar la impresora activa asociada a la estación
        $printer = Printer::where('station_id', $station->id)
            ->where('status', true)
            ->first(['ip_adress']);

            // Obtener nombre de la feria desde la relación (fallback a null)
        $fairName = $station->fair?->fair_name ?? $station->fair?->name ?? null;

        return Inertia::render('Ventas', [
            'printer_ip' => $printer?->ip_adress ?? null,
            'station_name' => $station->station_name,
            'fair_name' => $fairName,
            'terminal_status' => $paymentTerminal?->status_id,
            'payment_terminal_id' => $paymentTerminal?->id,
        ]);
    }

    public function creditos()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // buscar la terminal de pago asignada al usuario
        $paymentTerminal = PaymentTerminal::where('user_id', $user->id)->first();

        // Obtener la estación asignada (la primera, por si tuviera más de una)
        $station = $user->stations()->first();

        // Si el usuario no tiene estación → no hay impresora
        if (!$station) {
            return Inertia::render('Creditos', [
                'printer_ip' => null,
                'station_name' => null,
                'fair_name' => null,
                'terminal_status' => $paymentTerminal?->status_id,
                'payment_terminal_id' => $paymentTerminal?->id,
            ]);
        }

        // Buscar la impresora activa asociada a la estación
        $printer = Printer::where('station_id', $station->id)
            ->where('status', true)
            ->first(['ip_adress']);

        $fairName = $station->fair?->fair_name ?? $station->fair?->name ?? null;

        return Inertia::render('Creditos', [
            'printer_ip' => $printer?->ip_adress ?? null,
            'station_name' => $station->station_name,
            'fair_name' => $fairName,
            'terminal_status' => $paymentTerminal?->status_id,
            'payment_terminal_id' => $paymentTerminal?->id,
        ]);
    }

    public function administrar()
    {
        return Inertia::render('Admin/Administrar');
    }

    // Devuelve los datos para las gráficas
    public function charts()
    {
        // ====== VENTAS A CLIENTES (transaction_type_id = 1) ======
        // Total ventas a clientes
        $clientSalesTotal = DB::table('transactions')
            ->where('status_id', 1)
            ->where('transaction_type_id', 1) // VENTA
            ->sum('amount');

        // Por método de pago
        $salesByPaymentMethod = DB::table('transactions')
            ->join('payment_method', 'transactions.payment_method_id', '=', 'payment_method.payment_method_id')
            ->where('transactions.status_id', 1)
            ->where('transactions.transaction_type_id', 1)
            ->groupBy('transactions.payment_method_id', 'payment_method.payment_method')
            ->selectRaw('payment_method.payment_method as method, SUM(transactions.amount) as total')
            ->get()
            ->map(fn($item) => [
                'label' => $item->method,
                'value' => round($item->total, 2)
            ])->values();

        // Top 8 productos
        $topProducts = DB::table('transaction_detail')
            ->join('transactions', 'transaction_detail.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_detail.product_id', '=', 'products.id')
            ->where('transactions.status_id', 1)
            ->where('transactions.transaction_type_id', 1)
            ->groupBy('transaction_detail.product_id', 'products.product_name')
            ->selectRaw('products.product_name, COUNT(*) as count, SUM(transaction_detail.total) as total')
            ->orderByDesc('count')
            ->limit(8)
            ->get()
            ->map(fn($item) => [
                'label' => $item->product_name,
                'value' => round($item->total, 2)
            ])->values();

        // Tendencia diaria (últimos 30 días)
        $dailyTrend = DB::table('transactions')
            ->where('status_id', 1)
            ->where('transaction_type_id', 1)
            ->whereBetween('transaction_date', [now()->subDays(30), now()])
            ->groupByRaw('DATE(transaction_date)')
            ->selectRaw('DATE(transaction_date) as date, SUM(amount) as daily_total')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'label' => $item->date,
                'value' => round($item->daily_total, 2)
            ])->values();

        // Ganancias por estación
        $gainsByStation = DB::table('transactions')
            ->join('stations', 'transactions.station_id', '=', 'stations.id')
            ->where('transactions.status_id', 1)
            ->where('transactions.transaction_type_id', 1)
            ->groupBy('transactions.station_id', 'stations.station_name')
            ->selectRaw('stations.station_name, SUM(transactions.amount) as station_total')
            ->get()
            ->map(fn($item) => [
                'label' => $item->station_name,
                'value' => round($item->station_total, 2)
            ])->values();

        // Cantidad de transacciones
        $clientTransactionCount = DB::table('transactions')
            ->where('status_id', 1)
            ->where('transaction_type_id', 1)
            ->count();

        // ====== VENTAS EMPLEADO (transaction_type_id = 2) ======
        $employeeSalesTotal = DB::table('transactions')
            ->where('status_id', 1)
            ->where('transaction_type_id', 2) // VENTA EMPLEADO
            ->sum('amount');

        // Top productos en venta empleado
        $topEmployeeProducts = DB::table('transaction_detail')
            ->join('transactions', 'transaction_detail.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_detail.product_id', '=', 'products.id')
            ->where('transactions.status_id', 1)
            ->where('transactions.transaction_type_id', 2)
            ->groupBy('transaction_detail.product_id', 'products.product_name')
            ->selectRaw('products.product_name, COUNT(*) as count')
            ->orderByDesc('count')
            ->limit(8)
            ->get()
            ->map(fn($item) => [
                'label' => $item->product_name,
                'value' => $item->count
            ])->values();

        // Tendencia de venta empleado por día
        $employeeDailyTrend = DB::table('transactions')
            ->where('status_id', 1)
            ->where('transaction_type_id', 2)
            ->whereBetween('transaction_date', [now()->subDays(30), now()])
            ->groupByRaw('DATE(transaction_date)')
            ->selectRaw('DATE(transaction_date) as date, COUNT(*) as daily_count, SUM(amount) as daily_amount')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'label' => $item->date,
                'value' => $item->daily_count
            ])->values();

        // Cantidad de transacciones empleado
        $employeeTransactionCount = DB::table('transactions')
            ->where('status_id', 1)
            ->where('transaction_type_id', 2)
            ->count();

        // ====== TICKETS QR ======
        $qrTickets = DB::table('station_tickets')
            ->join('tickets', 'station_tickets.ticket_id', '=', 'tickets.id')
            ->join('products', 'tickets.product_id', '=', 'products.id')
            ->join('stations', 'station_tickets.station_id', '=', 'stations.id')
            ->where('tickets.status', 0) // Canjeados
            ->select('station_tickets.*', 'products.product_name', 'stations.station_name', 'station_tickets.created_at')
            ->get();

        $ticketTotal = $qrTickets->count();

        // Tickets por producto
        $ticketsByProduct = $qrTickets
            ->groupBy('product_name')
            ->map(function ($group, $product) {
                return ['label' => $product, 'value' => $group->count()];
            })->values();

        // Tickets por día
        $ticketsByDate = $qrTickets
            ->groupBy(fn($item) => \Carbon\Carbon::parse($item->created_at)->format('Y-m-d'))
            ->map(function ($group, $date) {
                return ['label' => $date, 'value' => $group->count()];
            })->sortKeys()->values();

        // Tickets por estación
        $ticketsByStation = $qrTickets
            ->groupBy('station_name')
            ->map(function ($group, $station) {
                return ['label' => $station, 'value' => $group->count()];
            })->values();

        return response()->json([
            'sales' => [
                'byPaymentMethod' => $salesByPaymentMethod,
                'byProduct' => $topProducts,
                'byDate' => $dailyTrend,
                'byStation' => $gainsByStation,
                'total' => round($clientSalesTotal, 2),
                'transactionCount' => $clientTransactionCount,
            ],
            'employeeSales' => [
                'byProduct' => $topEmployeeProducts,
                'byDate' => $employeeDailyTrend,
                'total' => round($employeeSalesTotal, 2),
                'transactionCount' => $employeeTransactionCount,
            ],
            'tickets' => [
                'byProduct' => $ticketsByProduct,
                'byDate' => $ticketsByDate,
                'byStation' => $ticketsByStation,
                'total' => $ticketTotal,
            ],
        ]);
    }
}
