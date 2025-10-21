<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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
        return Inertia::render('Ventas');
    }

    public function creditos()
    {
        return Inertia::render('Creditos');
    }

    // Devuelve los datos para las gráficas
    public function charts()
    {
        $data = DB::table('v_station_tickets')->get();

        // 🎯 Tickets por estación (cantidad)
        $barData = $data->groupBy('station_name')->map(function ($group, $station) {
            return ['label' => $station, 'value' => $group->count()];
        })->values();

        // 🎯 Tickets por producto (distribución)
        $pieData = $data->groupBy('product_name')->map(function ($group, $product) {
            return ['label' => $product, 'value' => $group->count()];
        })->values();

        // 💰 Ganancias por estación
        $gainsByStation = $data->groupBy('station_name')->map(function ($group, $station) {
            $total = $group->sum('unit_price');
            return ['label' => $station, 'value' => round($total, 2)];
        })->values();

        // 📅 Ganancias por día (formateado por fecha)
        $gainsByDate = $data->groupBy(fn ($item) => \Carbon\Carbon::parse($item->created_at)->format('Y-m-d'))
            ->map(function ($group, $date) {
                $total = $group->sum('unit_price');
                return ['label' => $date, 'value' => round($total, 2)];
            })->sortKeys()->values();

        return response()->json([
            'bar' => $barData,
            'pie' => $pieData,
            'gains_by_station' => $gainsByStation,
            'gains_by_date' => $gainsByDate,
        ]);
    }
}
