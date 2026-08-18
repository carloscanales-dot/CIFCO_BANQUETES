<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class TransaccionController extends Controller
{
    /**
     * Muestra la vista principal de transacciones
     */
    public function index()
    {
        return Inertia::render('Transacciones/Ventas/Index');
    }

    /**
     * Obtiene la lista de transacciones con filtros
     */
    public function list(Request $request)
    {
        $query = DB::table('transactions as t')
            ->leftJoin('stations as s', 't.station_id', '=', 's.id')
            ->leftJoin('users as u', 't.user_id', '=', 'u.id')
            ->select(
                't.id',
                't.transaction_date',
                't.amount',
                't.status_id',
                's.station_name',
                'u.name as user_name'
            )
            ->where('t.transaction_type_id', 1); // Solo ventas normales

        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('t.id', 'like', "%{$search}%")
                  ->orWhere('u.name', 'like', "%{$search}%")
                  ->orWhere('s.station_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('t.status_id', $request->status);
        }

        if ($request->filled('date')) {
            $query->where('t.jornada', $request->date);
        }

        $transactions = $query->orderBy('t.transaction_date', 'desc')
            ->limit(100)
            ->get();

        return response()->json($transactions);
    }

    /**
     * Obtiene los detalles de una transacción específica
     */
    public function details($id)
    {
        $transaction = DB::table('transactions as t')
            ->leftJoin('stations as s', 't.station_id', '=', 's.id')
            ->leftJoin('users as u', 't.user_id', '=', 'u.id')
            ->where('t.id', $id)
            ->select(
                't.id',
                't.transaction_date',
                't.amount',
                't.status_id',
                's.station_name',
                'u.name as user_name'
            )
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transacción no encontrada'], 404);
        }

        // Cargar detalles de productos
        $transaction->details = DB::table('transaction_detail as td')
            ->leftJoin('products as p', 'td.product_id', '=', 'p.id')
            ->where('td.transaction_id', $id)
            ->select(
                'p.product_name',
                'td.quantity',
                'td.unit_price',
                'td.total'
            )
            ->get();

        return response()->json($transaction);
    }

    /**
     * Anula una transacción (cambia su estado)
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'status_id' => 'required|integer'
        ]);

        try {
            DB::table('transactions')
                ->where('id', $id)
                ->update([
                    'status_id' => $request->status_id,
                    'updated_at' => now()
                ]);

            return response()->json([
                'message' => 'Transacción anulada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al anular la transacción',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
