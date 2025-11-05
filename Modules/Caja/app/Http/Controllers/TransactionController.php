<?php

namespace Modules\Caja\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        $cartItems = $request->input('cartItems', []);
        $total = $request->input('total', 0);
        $station_id = $request->input('station_id', 1);
        $employee_id = $request->input('employee_id', 103); // 🔹 Por defecto 104 (cliente general)

        // Buscar la última apertura de terminal del usuario autenticado
        $terminalOpening = DB::table('payment_terminal_opening')
            ->where('user_id', Auth::id())
            ->where('payment_terminal_id', 1)
            ->orderByDesc('opening_date')
            ->first();

        if (!$terminalOpening) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró apertura de terminal para el usuario.'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Insertar la transacción general
            $transactionId = DB::table('transactions')->insertGetId([
                'user_id' => Auth::id(),
                'station_id' => $station_id,
                'amount' => $total,
                'transaction_date' => now(),
                'transaction_type_id' => 1, // 1 = venta normal, 2 = crédito (ajusta si lo manejas)
                'status_id' => 1,
                'payment_method_id' => 1,
                'payment_terminal_opening_id' => $terminalOpening->id,
                'employee_id' => $employee_id, // 🔹 Aquí se asigna dinámicamente
                'is_refunded' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insertar los detalles de cada producto
            foreach ($cartItems as $item) {
                DB::table('transaction_detail')->insert([
                    'transaction_id' => $transactionId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['unit_price'] * $item['quantity'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'transaction_id' => $transactionId
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
