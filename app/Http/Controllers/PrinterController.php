<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Printer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Modules\Ticket\Models\Fair;
use Modules\Ticket\Models\Station;


class PrinterController extends Controller
{
    public function __construct()
    {
        //Solo accesible para administradores
        $this->middleware(['auth', 'role:Administrador']);
    }

    /**
     * Mostrar listado de impresoras.
     */
    public function index(Request $request)
    {
        // Ferias para el selector (todas). Por defecto, la abierta más reciente.
        $fairs = Fair::query()
            ->orderByDesc('start_date')
            ->get(['id', 'fair_name', 'start_date', 'end_date', 'status']);

        $selectedFairId = $request->integer('fair_id') ?: Fair::defaultDashboardId();

        // Impresoras de la feria seleccionada (vía sus estaciones).
        $stationIds = $selectedFairId
            ? DB::table('stations')->where('fair_id', $selectedFairId)->pluck('id')
            : collect();

        $printers = Printer::with('station')
            ->whereIn('station_id', $stationIds)
            ->get();

        return Inertia::render('Printers', [
            'printers'       => $printers,
            // Solo stands de ferias ABIERTAS (etiquetados con su feria) para crear/editar.
            'stations'       => Fair::openStationOptions(),
            'fairs'          => $fairs,
            'selectedFairId' => $selectedFairId,
            'filters'        => $request->only(['fair_id']),
        ]);
    }

    /**
     * Crear nueva impresora.
     */
    public function store(Request $request)
    {
        $request->validate([
            'printer_name' => 'required|string|max:65',
            'station_id' => 'required|exists:stations,id',
            'ip_adress' => 'nullable|ip',
            'status' => 'boolean',
        ]);

        if (! Fair::stationBelongsToOpenFair((int) $request->station_id)) {
            return back()->withErrors([
                'station_id' => 'La estación seleccionada no pertenece a una feria abierta.',
            ]);
        }

        Printer::create([
            'printer_name' => $request->printer_name,
            'station_id' => $request->station_id,
            'ip_adress' => $request->ip_adress,
            'status' => $request->status ?? false,
        ]);

        return redirect()->back()->with('success', 'Impresora creada correctamente.');
    }

    /**
     * Actualizar impresora existente.
     */
    public function update(Request $request, Printer $printer)
    {
        $request->validate([
            'printer_name' => 'required|string|max:65',
            'station_id' => 'required|exists:stations,id',
            'ip_adress' => 'nullable|ip',
            'status' => 'boolean',
        ]);

        if (! Fair::stationBelongsToOpenFair((int) $request->station_id)) {
            return back()->withErrors([
                'station_id' => 'La estación seleccionada no pertenece a una feria abierta.',
            ]);
        }

        $printer->update([
            'printer_name' => $request->printer_name,
            'station_id' => $request->station_id,
            'ip_adress' => $request->ip_adress,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Impresora actualizada correctamente.');
    }

    /**
     * Eliminar impresora.
     */
    public function destroy(Printer $printer)
    {
        $printer->delete();

        return redirect()->back()->with('success', 'Impresora eliminada correctamente.');
    }

    /**
     * Proxy HTTPS para conexiones a impresora Epson ePOS
     * Evita errores de contenido mixto (Mixed Content) en producción
     */
    public function proxyRequest(Request $request)
    {
        try {
            $printerIp = $request->input('printer_ip');
            $path = $request->input('path', '');
            $method = $request->input('method', 'GET');

            if (!$printerIp) {
                return response()->json(['error' => 'printer_ip es requerido'], 400);
            }

            // Construir la URL de la impresora
            $url = "http://{$printerIp}/{$path}";

            // Realizar la solicitud HTTP a la impresora
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 10,
                'connect_timeout' => 5,
            ])->request($method, $url, [
                'headers' => $request->headers->all(),
            ]);

            return response(
                $response->body(),
                $response->status(),
                $response->headers()
            );
        } catch (\Exception $e) {
            Log::error('Printer proxy error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al conectar con la impresora',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
