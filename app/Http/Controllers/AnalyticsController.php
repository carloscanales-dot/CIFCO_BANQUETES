<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Ticket\Models\Ticket;
use Modules\Ticket\Models\Product;
use Modules\Ticket\Models\Station;

class AnalyticsController extends Controller
{
    public function ticketsData(Request $request)
    {
        // ============================
        // 1. Filtros
        // ============================
        $filters = [
            'date_from'  => $request->query('date_from'),
            'date_to'    => $request->query('date_to'),
            'station_id' => $request->query('station_id'),
            'product_id' => $request->query('product_id'),
            'status'     => $request->query('status'), // 0 | 1 | ''
            'page'       => $request->query('page', 1),
        ];

        // ============================
        // 2. Query base
        // ============================
        $baseQuery = Ticket::query()
            ->select([
                'tickets.*',
                'products.product_name',
                'stations.station_name',
                DB::raw('tickets.created_at as generated_at'),
            ])
            ->leftJoin('products', 'products.id', '=', 'tickets.product_id')
            ->leftJoin('station_tickets', 'station_tickets.ticket_id', '=', 'tickets.id')
            ->leftJoin('stations', 'stations.id', '=', 'station_tickets.station_id');

        // FILTROS
        if ($filters['date_from']) {
            $baseQuery->whereDate('tickets.created_at', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $baseQuery->whereDate('tickets.created_at', '<=', $filters['date_to']);
        }
        if ($filters['product_id']) {
            $baseQuery->where('tickets.product_id', $filters['product_id']);
        }
        if ($filters['station_id']) {
            $baseQuery->where('stations.id', $filters['station_id']);
        }

        // STATUS (0 = canjeado, 1 = disponible/pending)
        if ($filters['status'] !== null && $filters['status'] !== '') {
            $baseQuery->where('tickets.status', $filters['status']);
        }

        // ============================
        // 3. Tabla paginada
        // ============================
        $tickets = $baseQuery
            ->orderBy('tickets.created_at', 'desc')
            ->paginate(10)
            ->appends($filters);

        // ============================
        // 4. Gráficas
        // ============================

        // generados
        $generated = Ticket::select(
            DB::raw('DATE(created_at) as period'),
            DB::raw('COUNT(*) as total')
        )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // canjeados: status = 0
        $redeemed = Ticket::where('status', 0)
            ->select(
                DB::raw('DATE(redeem_date) as period'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // por estación → contar solo canjeados (status=0)
        $byStation = Station::select([
            'stations.station_name',
            DB::raw("COUNT(CASE WHEN tickets.status = 0 THEN 1 END) as redeemed_count")
        ])
            ->leftJoin('station_tickets', 'station_tickets.station_id', '=', 'stations.id')
            ->leftJoin('tickets', 'tickets.id', '=', 'station_tickets.ticket_id')
            ->groupBy('stations.station_name')
            ->orderBy('redeemed_count', 'desc')
            ->get();

        // por producto → contar canjeados (status=0)
        $byProduct = Product::select([
            'products.product_name',
            DB::raw("COUNT(CASE WHEN tickets.status = 0 THEN 1 END) as redeemed")
        ])
            ->leftJoin('tickets', 'tickets.product_id', '=', 'products.id')
            ->groupBy('products.product_name')
            ->orderBy('redeemed', 'desc')
            ->get();

        // ============================
        // 5. Respuesta
        // ============================
        return response()->json([
            'tickets'      => $tickets,
            'generated'    => $generated,
            'redeemed'     => $redeemed,
            'byStation'    => $byStation,
            'byProduct'    => $byProduct,

            'stationsList' => Station::select('id', 'station_name')->get(),
            'productsList' => Product::select('id', 'product_name')->get(),

            // si luego quieres poblar el select de estado desde DB
            'statusList' => [
                ['id' => '', 'name' => 'Todos'],
                ['id' => 0, 'name' => 'Canjeado'],
                ['id' => 1, 'name' => 'Pendiente'],
            ],

            'activeFilters' => $filters,
        ]);
    }
}
