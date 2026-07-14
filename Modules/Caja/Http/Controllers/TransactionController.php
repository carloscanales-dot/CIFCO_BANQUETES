<?php

namespace Modules\Caja\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Modules\Caja\Models\Transaction;
use Modules\Caja\Models\PaymentTerminal;
use Modules\Caja\Models\PaymentTerminalOpening;
use Modules\Ticket\Models\Station; // <-- importar Station
use Modules\Caja\Models\PrintJob;
use App\Models\TransactionReprint;

class TransactionController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            abort(403, 'Usuario no autenticado');
        }

        $stationIds = $user->stations()->pluck('station_id');

        // Eager load relations and also select the transaction type name via JOIN
        // SOLO TRANSACCIONES TIPO VENTA (transaction_type_id = 1)
        $transactions = Transaction::select('transactions.*', 'transaction_type.transaction_type as transaction_type_name')
            ->leftJoin('transaction_type', 'transactions.transaction_type_id', '=', 'transaction_type.transaction_type_id')
            ->with(['user', 'status', 'paymentMethod', 'station'])
            ->whereIn('station_id', $stationIds)
            ->where('transactions.user_id', $user->id) // Solo transacciones del usuario en sesión
            ->where('transactions.transaction_type_id', 1) // Solo tipo VENTA
            ->latest()
            ->paginate(10);

        return Inertia::render('Caja/HistorialVentas', [
            'transactions' => $transactions,
        ]);
    }

    public function empleados()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            abort(403, 'Usuario no autenticado');
        }

        $stationIds = $user->stations()->pluck('station_id');

        // Eager load relations and also select the transaction type name via JOIN
        // SOLO TRANSACCIONES TIPO VENTA EMPLEADO (transaction_type_id = 2)
        $transactions = Transaction::select('transactions.*', 'transaction_type.transaction_type as transaction_type_name')
            ->leftJoin('transaction_type', 'transactions.transaction_type_id', '=', 'transaction_type.transaction_type_id')
            ->with(['user', 'status', 'paymentMethod', 'station', 'employee'])
            ->whereIn('station_id', $stationIds)
            ->where('transactions.user_id', $user->id) // Solo transacciones del usuario en sesión
            ->where('transactions.transaction_type_id', 2) // Solo tipo VENTA EMPLEADO
            ->latest()
            ->paginate(10);

        return Inertia::render('Caja/HistorialEmpleados', [
            'transactions' => $transactions,
        ]);
    }

    public function refund(Transaction $transaction)
    {
        $transaction->update(['status_id' => 4]);

        return redirect()->back()->with('success', 'Transacción marcada como devolución.');
    }

    public function store(Request $request)
    {
        $cartItems = $request->input('cartItems', []);
        $total = $request->input('total', 0);

        // leer como enteros/valores
        $station_id = (int) $request->input('station_id', 0); // NO usar 1 por defecto
        $payment_method = (int) $request->input('payment_method', 0); // 0 = no informado
        $employee_id = (int) $request->input('employee_id', 0); // opcional
        $transaction_type_id = (int) $request->input('transaction_type_id', 1); // 1 = Venta, 2 = Venta Empleado


        // Nota: removido logging de depuración en producción para evitar ruido en logs.

        // Validaciones básicas
        if (!$station_id) {
            return response()->json(['success' => false, 'message' => 'Station_id no proporcionado'], 422);
        }

        // Validar existencia de estación y cargar con impresora y feria
        $station = Station::with('printer', 'fair')->find($station_id);
        if (! $station) {
            return response()->json(['success' => false, 'message' => 'Estación inválida'], 422);
        }

        // Validar payment method: si no viene, asignar un default razonable (por ejemplo 1 = Efectivo)
        if (! $payment_method) {
            $payment_method = 1;
        }

        // Buscar la última apertura de terminal del usuario autenticado
        // Ahora determinamos la(s) terminal(es) asociadas a la estación solicitada
        $terminalIds = PaymentTerminal::where('station_id', $station_id)->pluck('id');

        // Si no hay terminal asociada a la estación, fallamos
        if ($terminalIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ninguna terminal asociada a la estación.'
            ], 422);
        }

        // Buscar la última apertura (no cerrada) para la(s) terminal(es) y el usuario autenticado
        $terminalOpening = PaymentTerminalOpening::whereIn('payment_terminal_id', $terminalIds)
            ->where('user_id', Auth::id())
            ->whereDoesntHave('closing')
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
                'transaction_type_id' => $transaction_type_id, // 1 = venta normal
                'status_id' => 1,
                'payment_method_id' => $payment_method,
                'payment_terminal_opening_id' => $terminalOpening->id,
                'employee_id' => $employee_id ?: 103,
                'is_refunded' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Obtener datos de impresora de la estación
            $printerIp = $station->printer->ip_adress ?? null;
            $printerPort = 9100; // Puerto estándar para impresoras térmicas

            // Determinar el tipo de venta y nombre correspondiente
            $jobType = 'sale'; // Por defecto: venta normal
            $personName = Auth::user()->name; // Por defecto: cajero

            // Si es venta a empleado (transaction_type_id = 2)
            if ($transaction_type_id == 2 && $employee_id) {
                $jobType = 'employee_sale';
                $employee = \Modules\Ticket\Models\Employee::find($employee_id);
                $personName = $employee ? $employee->employee_name : 'Empleado N/A';
            }

            // Construir payload para el agente de impresión
            $printJobPayload = [
                'printer' => [
                    'ip' => $printerIp,
                    'port' => $printerPort
                ],
                'fair_name' => $station->fair->fair_name ?? config('app.name', 'CIFCO'),
                'transaction_id' => $transactionId,
                'station_name' => $station->station_name,
                'cashier_name' => Auth::user()->name,
                'person_name' => $personName, // Cajero o Empleado
                'payment_method' => $payment_method,
                'cash_amount' => $request->input('cash_amount', $total),
                'total' => $total,
                'items' => []
            ];

            // Insertar los detalles de cada producto
            foreach ($cartItems as $item) {
                $printJobPayload['items'][] = [
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price']
                ];

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

            // Crear PrintJob para que el agente lo procese
            PrintJob::create([
                'user_id' => Auth::id(),
                'station_id' => $station_id,
                'transaction_id' => $transactionId,
                'type' => $jobType, // 'sale' o 'employee_sale'
                'payload' => $printJobPayload,
                'status' => 'pending',
            ]);

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

    /**
     * Reimprimir una transacción existente
     * Solo crea un nuevo PrintJob, NO duplica la transacción
     */
    public function reprint(Request $request, $transactionId)
    {
        try {
            // Cargar la transacción con todas sus relaciones
            $transaction = Transaction::with(['station.printer', 'station.fair', 'user', 'details.product', 'employee'])
                ->find($transactionId);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transacción no encontrada'
                ], 404);
            }

            // Verificar que el usuario actual tenga permiso (es dueño de la transacción o admin)
            if ($transaction->user_id !== Auth::id()) {
                // Aquí podrías agregar una validación adicional para administradores si lo necesitas
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para reimprimir esta transacción'
                ], 403);
            }

            // Obtener datos de impresora de la estación
            $station = $transaction->station;
            $printerIp = $station->printer->ip_adress ?? null;
            $printerPort = 9100;

            // Determinar el tipo de venta
            $jobType = 'sale'; // Por defecto: venta normal
            $personName = $transaction->user->name; // Por defecto: cajero

            // Si es venta a empleado (transaction_type_id = 2)
            if ($transaction->transaction_type_id == 2 && $transaction->employee_id) {
                $jobType = 'employee_sale';
                $personName = $transaction->employee ? $transaction->employee->employee_name : 'Empleado N/A';
            }

            // Construir payload para el agente de impresión (igual que en store)
            $printJobPayload = [
                'printer' => [
                    'ip' => $printerIp,
                    'port' => $printerPort
                ],
                'fair_name' => $station->fair->fair_name ?? config('app.name', 'CIFCO'),
                'transaction_id' => $transaction->id,
                'station_name' => $station->station_name,
                'cashier_name' => $transaction->user->name,
                'person_name' => $personName,
                'payment_method' => $transaction->payment_method_id,
                'cash_amount' => $transaction->amount, // Para reimpresión usamos el monto de la transacción
                'total' => $transaction->amount,
                'reimpreso' => true, // Indica que es una reimpresión
                'items' => []
            ];

            // Agregar los items de la transacción
            foreach ($transaction->details as $detail) {
                $printJobPayload['items'][] = [
                    'product_name' => $detail->product->product_name ?? 'Producto N/A',
                    'quantity' => $detail->quantity,
                    'unit_price' => $detail->unit_price
                ];
            }

            // Crear nuevo PrintJob para reimpresión
            PrintJob::create([
                'user_id' => Auth::id(),
                'station_id' => $transaction->station_id,
                'transaction_id' => $transaction->id,
                'type' => $jobType,
                'payload' => $printJobPayload,
                'status' => 'pending',
            ]);

            // Registrar la reimpresión en el log
            TransactionReprint::create([
                'transaction_id' => $transaction->id,
                'user_id' => Auth::id(),
                'station_id' => $transaction->station_id,
                'reprinted_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trabajo de impresión creado correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Reprint error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el trabajo de impresión: ' . $e->getMessage()
            ], 500);
        }
    }
}
