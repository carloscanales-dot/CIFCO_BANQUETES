<?php

namespace Modules\Ticket\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Ticket\Models\Station;
use Modules\Ticket\Models\Product;

class StationProductController extends Controller
{
    // StationProductController.php
    public function store(Request $request, Station $station)
    {
        $request->validate([
            'product_ids' => 'array',               // permitir array vacío
            'product_ids.*' => 'exists:products,id'
        ]);

        // Esto sincroniza: agrega los que no existen y elimina los que se quitaron
        $station->products()->sync($request->product_ids ?? []);

        return response()->json(['message' => 'Productos actualizados correctamente']);
    }

    public function getProducts(Station $station)
    {
        // Devuelve solo los ids y nombres de los productos ya asignados
        $products = $station->products()->select('id', 'product_name')->get();
        return response()->json($products);
    }

}

