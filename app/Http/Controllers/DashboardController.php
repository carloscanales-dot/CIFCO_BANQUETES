<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\Printer;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Modules\Caja\App\Models\PaymentTerminal;

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
                'terminal_status' => $paymentTerminal?->status_id,
                'payment_terminal_id' => $paymentTerminal?->id,
            ]);
        }

        // Buscar la impresora activa asociada a la estación
        $printer = Printer::where('station_id', $station->id)
            ->where('status', true)
            ->first(['ip_adress']);

        return Inertia::render('Ventas', [
            'printer_ip' => $printer?->ip_adress ?? null,
            'station_name' => $station->station_name,
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
                'terminal_status' => $paymentTerminal?->status_id,
                'payment_terminal_id' => $paymentTerminal?->id,
            ]);
        }

        // Buscar la impresora activa asociada a la estación
        $printer = Printer::where('station_id', $station->id)
            ->where('status', true)
            ->first(['ip_adress']);

        return Inertia::render('Creditos', [
            'printer_ip' => $printer?->ip_adress ?? null,
            'station_name' => $station->station_name,
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
        $data = DB::table('v_station_tickets')->get();

        // Tickets por estación (cantidad)
        $barData = $data->groupBy('station_name')->map(function ($group, $station) {
            return ['label' => $station, 'value' => $group->count()];
        })->values();

        // Tickets por producto (distribución)
        $pieData = $data->groupBy('product_name')->map(function ($group, $product) {
            return ['label' => $product, 'value' => $group->count()];
        })->values();

        // Ganancias por estación
        $gainsByStation = $data->groupBy('station_name')->map(function ($group, $station) {
            $total = $group->sum('unit_price');
            return ['label' => $station, 'value' => round($total, 2)];
        })->values();

        // Ganancias por día (formateado por fecha)
        $gainsByDate = $data->groupBy(fn($item) => \Carbon\Carbon::parse($item->created_at)->format('Y-m-d'))
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
