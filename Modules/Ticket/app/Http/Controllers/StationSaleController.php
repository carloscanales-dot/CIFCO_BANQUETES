<?php

namespace Modules\Ticket\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StationSaleController extends Controller
{
    /**
     * Devuelve los productos disponibles para la estación del usuario autenticado.
     */
    public function getProductsForUser(Request $request)
    {
        $user = $request->user();

        // Obtener la estación asignada al usuario. Si tuviera stands en varias
        // ferias, se prefiere el de una feria ABIERTA (status = 2) y la más reciente.
        $station = DB::table('station_users')
            ->join('stations', 'station_users.station_id', '=', 'stations.id')
            ->join('fairs', 'stations.fair_id', '=', 'fairs.id')
            ->where('station_users.user_id', $user->id)
            ->orderByRaw('CASE WHEN fairs.status = 2 THEN 0 ELSE 1 END')
            ->orderByDesc('fairs.start_date')
            ->select('station_users.station_id')
            ->first();

        if (!$station) {
            return response()->json([
                'products' => [],
                'message' => 'El usuario no tiene estación asignada'
            ]);
        }

        // Obtener los productos de esa estación (sin la columna 'icon')
        $products = DB::table('station_products')
            ->join('products', 'station_products.product_id', '=', 'products.id')
            ->where('station_products.station_id', $station->station_id)
            ->select('products.id as product_id', 'products.product_name', 'products.unit_price')
            ->get();

        // Agregar un ícono por defecto para el frontend
        $products = $products->map(function ($p) {
            $p->icon = 'mdi-food'; // ícono por defecto
            return $p;
        });

        return response()->json([
            'products' => $products
        ]);
    }
}
