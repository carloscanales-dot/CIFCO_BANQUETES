<?php

namespace Modules\Ticket\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Ticket\Models\Location;
use App\Http\Controllers\Controller;


class LocationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:Administrador']);
    }

    /**
     * Renderiza la vista principal del CRUD de locaciones
     */
    public function index()
    {
        $locations = Location::orderBy('location_name')->get();

        return Inertia::render('Locations', [
            'locations' => $locations,
        ]);
    }

    /**
     * Listar todas las locaciones (endpoint API)
     */
    public function listAll()
    {
        return response()->json(['locations' => Location::all()]);
    }

    /**
     * Crear una nueva locación
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'location_name' => 'required|string|max:65|unique:location,location_name',
        ]);

        Location::create($validated);

        return redirect()->back()->with('success', 'Locación creada correctamente.');
    }
    /**
     * Actualizar locación existente
     */
    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'location_name' => 'required|string|max:65|unique:location,location_name,' . $location->id,
        ]);

        $location->update($validated);

        return redirect()->back()->with('success', 'Locación actualizada correctamente.');
    }
    /**
     * Eliminar locación
     */
    public function destroy(Location $location)
    {
        $location->delete();
        return redirect()->back()->with('success', 'Locación eliminada correctamente.');
    }
}
