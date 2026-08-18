<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\Printer;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Modules\Caja\Models\PaymentTerminal;
use Modules\Ticket\Models\Fair;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Renderiza la vista principal del dashboard
    public function index()
    {
        $fairs = DB::table('fairs')
            ->select('id', 'fair_name', 'start_date', 'end_date', 'status')
            ->orderByDesc('start_date')
            ->get();

        return Inertia::render('Dashboard', [
            'fairs'          => $fairs,
            'selectedFairId' => Fair::defaultDashboardId(),
        ]);
    }

    public function landing()
    {
        return Inertia::render('Landing');
    }

    public function ventas()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Obtener la estación asignada. Si el cajero tiene stands en varias ferias,
        // se prefiere el de una feria ABIERTA (status = 2); si no, el primero.
        $station = $user->stations()->whereHas('fair', fn($q) => $q->where('status', 2))->first()
            ?? $user->stations()->first();

        // Terminal de pago EN el stand resuelto (evita tomar la de otra feria tras
        // clonar). Se prefiere la del cajero; si no, cualquiera del stand.
        $paymentTerminal = $station
            ? (PaymentTerminal::where('station_id', $station->id)->where('user_id', $user->id)->first()
                ?? PaymentTerminal::where('station_id', $station->id)->first())
            : PaymentTerminal::where('user_id', $user->id)->first();

        // Si el usuario no tiene estación → no hay impresora
        if (!$station) {
            return Inertia::render('Ventas', [
                'printer_ip' => null,
                'station_id' => null,
                'station_name' => null,
                'fair_name' => null, // <- nada que mostrar
                'fair_status' => null,
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
            'station_id' => $station->id, // stand resuelto (feria abierta)
            'station_name' => $station->station_name,
            'fair_name' => $fairName,
            'fair_status' => $station->fair?->status !== null ? (int) $station->fair->status : null,
            'terminal_status' => $paymentTerminal?->status_id,
            'payment_terminal_id' => $paymentTerminal?->id,
        ]);
    }

    public function creditos()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Obtener la estación asignada. Si el cajero tiene stands en varias ferias,
        // se prefiere el de una feria ABIERTA (status = 2); si no, el primero.
        $station = $user->stations()->whereHas('fair', fn($q) => $q->where('status', 2))->first()
            ?? $user->stations()->first();

        // Terminal de pago EN el stand resuelto (evita tomar la de otra feria tras
        // clonar). Se prefiere la del cajero; si no, cualquiera del stand.
        $paymentTerminal = $station
            ? (PaymentTerminal::where('station_id', $station->id)->where('user_id', $user->id)->first()
                ?? PaymentTerminal::where('station_id', $station->id)->first())
            : PaymentTerminal::where('user_id', $user->id)->first();

        // Si el usuario no tiene estación → no hay impresora
        if (!$station) {
            return Inertia::render('Creditos', [
                'printer_ip' => null,
                'station_id' => null,
                'station_name' => null,
                'fair_name' => null,
                'fair_status' => null,
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
            'station_id' => $station->id, // stand resuelto (feria abierta)
            'station_name' => $station->station_name,
            'fair_name' => $fairName,
            'fair_status' => $station->fair?->status !== null ? (int) $station->fair->status : null,
            'terminal_status' => $paymentTerminal?->status_id,
            'payment_terminal_id' => $paymentTerminal?->id,
        ]);
    }

    public function administrar()
    {
        return Inertia::render('Admin/Administrar');
    }

    // Devuelve los datos para las gráficas
    public function charts(Request $request)
    {
        // Feria seleccionada (o la de por defecto si no viene en la petición).
        $fairId = $request->integer('fair_id') ?: Fair::defaultDashboardId();

        // Sin feria disponible → estructura vacía (evita mezclar datos globales).
        if (! $fairId) {
            return response()->json($this->emptyChartPayload());
        }

        $fair = DB::table('fairs')
            ->select('id', 'fair_name', 'start_date', 'end_date', 'status')
            ->find($fairId);

        // ====== VENTAS A CLIENTES (transaction_type_id = 1) ======
        // Total ventas a clientes
        $clientSalesTotal = DB::table('transactions')
            ->join('stations', 'transactions.station_id', '=', 'stations.id')
            ->where('stations.fair_id', $fairId)
            ->where('transactions.status_id', 1)
            ->where('transactions.transaction_type_id', 1) // VENTA
            ->sum('transactions.amount');

        // Por método de pago. Las ventas MIXTAS (método 4) se reparten: su parte
        // en efectivo suma a EFECTIVO y su parte en tarjeta a TARJETA (igual que el
        // arqueo de caja), en vez de aparecer como una categoría "MIXTO" aparte.
        $pmBase = DB::table('transactions')
            ->join('stations', 'transactions.station_id', '=', 'stations.id')
            ->where('stations.fair_id', $fairId)
            ->where('transactions.status_id', 1)
            ->where('transactions.transaction_type_id', 1);

        $pmCash  = (clone $pmBase)->where('payment_method_id', 1)->sum('amount')
                 + (clone $pmBase)->where('payment_method_id', 4)->sum('amount_cash');
        $pmCard  = (clone $pmBase)->where('payment_method_id', 2)->sum('amount')
                 + (clone $pmBase)->where('payment_method_id', 4)->sum('amount_card');
        $pmChivo = (clone $pmBase)->where('payment_method_id', 3)->sum('amount');

        $salesByPaymentMethod = collect([
            ['label' => 'EFECTIVO', 'value' => round($pmCash, 2)],
            ['label' => 'TARJETA',  'value' => round($pmCard, 2)],
            ['label' => 'CHIVO',    'value' => round($pmChivo, 2)],
        ])->filter(fn($r) => $r['value'] > 0)->values();

        // Top 8 productos
        $topProducts = DB::table('transaction_detail')
            ->join('transactions', 'transaction_detail.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_detail.product_id', '=', 'products.id')
            ->join('stations', 'transactions.station_id', '=', 'stations.id')
            ->where('stations.fair_id', $fairId)
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

        // Tendencia diaria (dentro del periodo de la feria)
        $dailyTrend = DB::table('transactions')
            ->join('stations', 'transactions.station_id', '=', 'stations.id')
            ->where('stations.fair_id', $fairId)
            ->where('transactions.status_id', 1)
            ->where('transactions.transaction_type_id', 1)
            ->groupBy('transactions.jornada')
            ->selectRaw('transactions.jornada as date, SUM(transactions.amount) as daily_total')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'label' => $item->date,
                'value' => round($item->daily_total, 2)
            ])->values();

        // Ganancias por estación
        $gainsByStation = DB::table('transactions')
            ->join('stations', 'transactions.station_id', '=', 'stations.id')
            ->where('stations.fair_id', $fairId)
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
            ->join('stations', 'transactions.station_id', '=', 'stations.id')
            ->where('stations.fair_id', $fairId)
            ->where('transactions.status_id', 1)
            ->where('transactions.transaction_type_id', 1)
            ->count();

        // ====== VENTAS EMPLEADO (transaction_type_id = 2) ======
        $employeeSalesTotal = DB::table('transactions')
            ->join('stations', 'transactions.station_id', '=', 'stations.id')
            ->where('stations.fair_id', $fairId)
            ->where('transactions.status_id', 1)
            ->where('transactions.transaction_type_id', 2) // VENTA EMPLEADO
            ->sum('transactions.amount');

        // Top productos en venta empleado
        $topEmployeeProducts = DB::table('transaction_detail')
            ->join('transactions', 'transaction_detail.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_detail.product_id', '=', 'products.id')
            ->join('stations', 'transactions.station_id', '=', 'stations.id')
            ->where('stations.fair_id', $fairId)
            ->where('transactions.status_id', 1)
            ->where('transactions.transaction_type_id', 2)
            ->groupBy('transaction_detail.product_id', 'products.product_name')
            ->selectRaw('products.product_name, SUM(transaction_detail.total) as total')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn($item) => [
                'label' => $item->product_name,
                'value' => round($item->total, 2)
            ])->values();

        // Tendencia de venta empleado por día
        $employeeDailyTrend = DB::table('transactions')
            ->join('stations', 'transactions.station_id', '=', 'stations.id')
            ->where('stations.fair_id', $fairId)
            ->where('transactions.status_id', 1)
            ->where('transactions.transaction_type_id', 2)
            ->groupBy('transactions.jornada')
            ->selectRaw('transactions.jornada as date, COUNT(*) as daily_count, SUM(transactions.amount) as daily_amount')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'label' => $item->date,
                'value' => round($item->daily_amount, 2)
            ])->values();

        // Cantidad de transacciones empleado
        $employeeTransactionCount = DB::table('transactions')
            ->join('stations', 'transactions.station_id', '=', 'stations.id')
            ->where('stations.fair_id', $fairId)
            ->where('transactions.status_id', 1)
            ->where('transactions.transaction_type_id', 2)
            ->count();

        // ====== TICKETS QR ======
        $qrTickets = DB::table('station_tickets')
            ->join('tickets', 'station_tickets.ticket_id', '=', 'tickets.id')
            ->join('products', 'tickets.product_id', '=', 'products.id')
            ->join('stations', 'station_tickets.station_id', '=', 'stations.id')
            ->where('stations.fair_id', $fairId)
            ->where('tickets.status_id', 1) // APLICADO (anteriormente "canjeados")
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
            'fair' => $fair ? [
                'id'         => $fair->id,
                'name'       => $fair->fair_name,
                'start_date' => $fair->start_date,
                'end_date'   => $fair->end_date,
                'status'     => (int) $fair->status,
            ] : null,
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

    /**
     * Estructura vacía del dashboard (cuando no hay feria seleccionable).
     */
    private function emptyChartPayload(): array
    {
        return [
            'fair' => null,
            'sales' => [
                'byPaymentMethod' => [],
                'byProduct' => [],
                'byDate' => [],
                'byStation' => [],
                'total' => 0,
                'transactionCount' => 0,
            ],
            'employeeSales' => [
                'byProduct' => [],
                'byDate' => [],
                'total' => 0,
                'transactionCount' => 0,
            ],
            'tickets' => [
                'byProduct' => [],
                'byDate' => [],
                'byStation' => [],
                'total' => 0,
            ],
        ];
    }
}
