<?php

namespace Modules\Ticket\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Ticket\Models\Location;

class LocationController extends Controller
{
    /**
     * Listar todas las locations de una feria específica.
     */
    public function list($fairId)
    {
        $locations = Location::where('fair_id', $fairId)->get();
        return response()->json(['locations' => $locations]);
    }

    public function listAll()
    {
        return response()->json(['locations' => Location::all()]);
    }

    /**
     * Guardar una nueva location asociada a una feria.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'location_name' => 'required|string|max:65',
            'fair_id' => 'required|exists:fairs,id',
        ]);

        $location = Location::create($validated);

        return response()->json(['location' => $location]);
    }

    /**
     * Eliminar una location.
     */
    public function destroy(Location $location)
    {
        $location->delete();
        return response()->noContent(); // 204 No Content
    }
}
