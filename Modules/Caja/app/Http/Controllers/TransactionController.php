<?php

namespace Modules\Caja\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Ticket\Models\Station; // <-- importar Station

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        $cartItems = $request->input('cartItems', []);
        $total = $request->input('total', 0);

        // leer como enteros/valores
        $station_id = (int) $request->input('station_id', 0); // NO usar 1 por defecto
        $payment_method = (int) $request->input('payment_method', 0); // 0 = no informado
        $employee_id = (int) $request->input('employee_id', 0); // opcional

        // Opcional: loguear payload para depuración (quítalo en prod)
        Log::debug('Transactions.store payload', [
            'user_id' => Auth::id(),
            'station_id' => $station_id,
            'payment_method' => $payment_method,
            'employee_id' => $employee_id,
            'total' => $total,
            'items_count' => count($cartItems),
        ]);

        // Validaciones básicas
        if (!$station_id) {
            return response()->json(['success' => false, 'message' => 'Station_id no proporcionado'], 422);
        }

        // Validar existencia de estación
        if (! Station::find($station_id)) {
            return response()->json(['success' => false, 'message' => 'Estación inválida'], 422);
        }

        // Validar payment method: si no viene, asignar un default razonable (por ejemplo 1 = Efectivo)
        if (! $payment_method) {
            $payment_method = 1;
        }

        // Buscar la última apertura de terminal del usuario autenticado
        $terminalOpening = DB::table('payment_terminal_opening')
            ->where('user_id', Auth::id())
            ->where('payment_terminal_id', 1) // si esto debe ser dinámico, reemplazar
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
            // Insertar la transacción general usando los valores validados
            $transactionId = DB::table('transactions')->insertGetId([
                'user_id' => Auth::id(),
                'station_id' => $station_id,
                'amount' => $total,
                'transaction_date' => now(),
                'transaction_type_id' => 1, // 1 = venta normal
                'status_id' => 1,
                'payment_method_id' => $payment_method,
                'payment_terminal_opening_id' => $terminalOpening->id,
                'employee_id' => $employee_id ?: null,
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
            Log::error('Transaction store error: '.$e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar la transacción'
            ], 500);
        }
    }
}
