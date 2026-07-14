<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransactionReprint;
use Modules\Ticket\Models\Station;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReprintLogController extends Controller
{
    /**
     * Mostrar el historial de reimpresiones
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $stationId = $request->input('station_id');

        $query = TransactionReprint::with([
            'transaction' => function ($query) {
                $query->with([
                    'status',
                    'paymentMethod',
                    'transactionType',
                    'employee'
                ]);
            },
            'user',
            'station'
        ]);

        // Filtro por rango de fechas
        if ($startDate) {
            $query->where('reprinted_at', '>=', $startDate . ' 00:00:00');
        }

        if ($endDate) {
            $query->where('reprinted_at', '<=', $endDate . ' 23:59:59');
        }

        // Filtro por estación
        if ($stationId) {
            $query->where('station_id', $stationId);
        }

        $reprints = $query->orderBy('reprinted_at', 'desc')->paginate($perPage);

        // Obtener todas las estaciones para el filtro
        $stations = Station::select('id', 'station_name')
            ->orderBy('station_name')
            ->get();

        return Inertia::render('Admin/ReprintLogs', [
            'reprints' => $reprints,
            'stations' => $stations,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'station_id' => $stationId,
            ]
        ]);
    }
}
