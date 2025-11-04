<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Printer;
use Illuminate\Http\Request;
use Inertia\Inertia; 
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
    public function index()
    {
        $printers = Printer::with('station')->get();
        $stations = Station::all(['id', 'station_name']);

        return Inertia::render('Printers', [
            'printers' => $printers,
            'stations' => $stations,
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
}
