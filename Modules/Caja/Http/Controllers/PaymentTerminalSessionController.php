<?php

namespace Modules\Caja\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Caja\Models\PaymentTerminalOpening;
use Modules\Caja\Models\PaymentTerminalClosing;
use Modules\Caja\Models\PaymentTerminal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // <-- AQUI

class PaymentTerminalSessionController extends Controller
{
    /**
     * Mostrar lista de aperturas/cierres por terminal
     */
    public function index(Request $request)
    {
        $q = $request->input('q');
        $perPage = $request->input('perPage', 10);

        // Feria activa
        $openFair = \Modules\Ticket\Models\Fair::where('status', 2)->first();

        if (!$openFair) {
            return Inertia::render('TerminalSessions', [
                'terminals' => [],
                'filters'   => $request->only(['q', 'perPage']),
                'message'   => 'No hay ninguna feria activa en este momento.',
            ]);
        }

        // Estaciones
        $stationIds = $openFair->stations()->pluck('id');

        // Terminales
        $terminals = PaymentTerminal::with([
            'station',
            'user',
            'openings' => function ($q) {
                $q->with([
                    'closing',
                    'transactions'
                ])
                    ->orderByDesc('opening_date')
                    ->limit(1);
            }
        ])
            ->whereIn('station_id', $stationIds)
            ->when($q, fn($query) => $query->where('terminal_name', 'like', "%{$q}%"))
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // ===========================
        // AÑADIR TOTALES A CADA APERTURA
        // ===========================
        $terminals->getCollection()->transform(function ($terminal) {

            $opening = $terminal->openings->first();

            if ($opening) {
                $transactions = \Modules\Caja\Models\Transaction::where('payment_terminal_opening_id', $opening->id)
                    ->where('status_id', 1)
                    ->where('transaction_type_id', 1)
                    ->get();

                $opening->total_cash       = $transactions->where('payment_method_id', 1)->sum('amount');
                $opening->total_card       = $transactions->where('payment_method_id', 2)->sum('amount');
                $opening->total_chivo      = $transactions->where('payment_method_id', 3)->sum('amount');
                $opening->total_transacted = $transactions->sum('amount');

                $opening->expected_amount  = $opening->total_cash;

                $opening->change_fund = $opening->opening_amount;
            }

            return $terminal;
        });

        return Inertia::render('TerminalSessions', [
            'terminals' => $terminals,
            'stations'  => $openFair->stations()->select('id', 'station_name')->get(),
            'fair'      => $openFair,
            'filters'   => $request->only(['q', 'perPage']),
        ]);
    }


    /**
     * Registrar una apertura
     */
    public function open(Request $request)
    {
        $data = $request->validate([
            'payment_terminal_id' => 'required|exists:payment_terminal,id',
            'user_id'             => 'required|exists:users,id',
            'opening_amount'      => 'required|numeric|min:0',
        ]);

        // Verificar si ya tiene apertura sin cierre
        $alreadyOpen = PaymentTerminalOpening::where('payment_terminal_id', $data['payment_terminal_id'])
            ->whereDoesntHave('closing')
            ->exists();

        if ($alreadyOpen) {
            return response()->json([
                'success' => false,
                'message' => 'La terminal ya tiene una apertura activa.',
            ], 409);
        }

        $data['opening_date'] = Carbon::now();

        $opening = PaymentTerminalOpening::create($data);

        // Cambiar estado de terminal → abierta (5)
        PaymentTerminal::where('id', $data['payment_terminal_id'])
            ->update(['status_id' => 5]);

        return response()->json([
            'success' => true,
            'message' => 'Apertura registrada correctamente.',
            'opening' => $opening,
        ]);
    }


    /**
     * Registrar un cierre
     */
    public function close(Request $request, $openingId)
    {
        $opening = PaymentTerminalOpening::with('terminal')->findOrFail($openingId);

        if ($opening->is_closed) {
            return response()->json([
                'success' => false,
                'message' => 'Esta apertura ya fue cerrada.',
            ], 409);
        }

        $data = $request->validate([
            'real_amount' => 'required|numeric|min:0',
            'pos_real_amount' => 'required|numeric|min:0',
            'notes'       => 'nullable|string|max:255',
            'user_id'     => 'required|exists:users,id',
        ]);

        DB::beginTransaction();

        try {

            // ==========================================
            // Total de transacciones en EFECTIVO
            // ==========================================
            $cashTransactionsTotal = \Modules\Caja\Models\Transaction::where('payment_terminal_opening_id', $opening->id)
                ->where('status_id', 1)
                ->where('transaction_type_id', 1)
                ->whereIn('payment_method_id', [1, 2])->sum('amount');
            // ==========================================
            // Calcular monto esperado (apertura + efectivo)
            // ==========================================
            $expectedAmount = $cashTransactionsTotal;

            // ==========================================
            // Calcular diferencia (real - esperado)
            // ==========================================
            $closingBalance = ($data['real_amount'] + $data['pos_real_amount']) - $expectedAmount;

            // ==========================================
            // Crear cierre con montos correctos
            // ==========================================
            $closing = PaymentTerminalClosing::create([
                'payment_terminal_opening_id' => $opening->id,
                'payment_terminal_id'         => $opening->payment_terminal_id,
                'user_id'                     => $data['user_id'],
                'closing_date'                => Carbon::now(),
                'expected_amount'             => $expectedAmount,
                'real_amount'                 => $data['real_amount'],
                'pos_real_amount'             => $data['pos_real_amount'],
                'closing_balance'             => $closingBalance,
                'notes'                       => $data['notes'] ?? '',
            ]);

            // Cambiar estado de terminal → cerrada
            $opening->terminal->update(['status_id' => 6]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cierre registrado correctamente.',
                'closing' => $closing,
            ]);
        } catch (\Throwable $th) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el cierre.',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }



    public function exportClosing($closingId)
    {
        $closing = \Modules\Caja\Models\PaymentTerminalClosing::with([
            'opening.terminal.station',
            'opening.terminal.user',
        ])->findOrFail($closingId);

        $opening = $closing->opening;
        $terminal = $opening->terminal;

        // ===============================
        // TRANSACCIONES DE LA SESIÓN
        // ===============================
        $transactions = \Modules\Caja\Models\Transaction::with('paymentMethod')
            ->where('payment_terminal_opening_id', $opening->id)
            ->where('status_id', 1) // completada
            ->where('transaction_type_id', 1)
            ->get();

        // Totales por método de pago
        $totalCash   = $transactions->where('payment_method_id', 1)->sum('amount');
        $totalCard   = $transactions->where('payment_method_id', 2)->sum('amount');
        $totalChivo  = $transactions->where('payment_method_id', 3)->sum('amount');
        $totalTransacted = $transactions->sum('amount');

        // ===============================
        // DETALLES POR PRODUCTO
        // ===============================
        $details = \Modules\Caja\Models\TransactionDetail::with('product')
            ->whereIn('transaction_id', $transactions->pluck('id'))
            ->get()
            ->groupBy('product_id')
            ->map(function ($group) {
                return [
                    'product_name' => $group->first()->product->product_name,
                    'unit_price'   => $group->first()->unit_price,
                    'quantity'     => $group->sum('quantity'),
                    'total'        => $group->sum('total'),
                ];
            })
            ->values();

        // ===============================
        // PREPARAR DATA
        // ===============================
        $data = [
            'opening'         => $opening,
            'closing'         => $closing,
            'terminal'        => $terminal,
            'station'         => $terminal->station,
            'cashier'         => $terminal->user,
            'totalCash'       => $totalCash,
            'totalCard'       => $totalCard,
            'totalChivo'      => $totalChivo,
            'totalTransacted' => $totalTransacted,
            'details'         => $details,
        ];

        // ===============================
        // GENERAR PDF
        // ===============================
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.terminal_closing', $data)
            ->setPaper('letter', 'portrait');

        // Carpeta donde se guardará
        $directory = storage_path("app/reports/terminal_closings/");
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filename = "cierre-terminal-" . $closing->payment_terminal_closing_id . ".pdf";
        $filepath = $directory . $filename;

        file_put_contents($filepath, $pdf->output());

        // ===============================
        // DEVOLVER DESCARGA DIRECTA
        // ===============================
        return response()->download($filepath, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function current(Request $request)
    {
        $stationId = $request->query('station_id');

        if (!$stationId) {
            return response()->json([
                'success' => false,
                'message' => 'station_id is required'
            ], 400);
        }

        // Buscar terminal asociada a la estación (ajusta el where si tu columna es diferente)
        $terminal = PaymentTerminal::with([
            'station',
            'user',
            'openings' => function ($q) {
                $q->with(['closing', 'transactions.paymentMethod'])
                    ->orderByDesc('opening_date')
                    ->limit(1);
            }
        ])->where('station_id', $stationId)->first();

        if (!$terminal) {
            return response()->json([
                'success' => false,
                'message' => 'No terminal found for this station'
            ], 404);
        }

        // Añadir totales por método para la apertura (si existe)
        $opening = $terminal->openings->first() ?? null;

        if ($opening) {
            $transactions = \Modules\Caja\Models\Transaction::where('payment_terminal_opening_id', $opening->id)
                ->where('status_id', 1) // completadas
                ->where('transaction_type_id', 1)
                ->get();

            $opening->total_cash       = $transactions->where('payment_method_id', 1)->sum('amount');
            $opening->total_card       = $transactions->where('payment_method_id', 2)->sum('amount');
            $opening->total_chivo      = $transactions->where('payment_method_id', 3)->sum('amount');
            $opening->total_transacted = $transactions->sum('amount');
        }

        return response()->json([
            'success' => true,
            'terminal' => $terminal
        ]);
    }

    public function preclose(Request $request, $openingId)
    {
        $opening = PaymentTerminalOpening::with('terminal')->findOrFail($openingId);

        if ($opening->is_closed ?? false) {
            return response()->json([
                'success' => false,
                'message' => 'Esta apertura ya fue cerrada.',
            ], 409);
        }

        $data = $request->validate([
            'user_id' => 'nullable|exists:users,id',
        ]);

        DB::beginTransaction();

        try {
            // Obtener transacciones completadas de la apertura
            $transactions = \Modules\Caja\Models\Transaction::where('payment_terminal_opening_id', $opening->id)
                ->where('status_id', 1) // completada
                ->where('transaction_type_id', 1)
                ->get();

            $totalCash   = $transactions->where('payment_method_id', 1)->sum('amount');
            $totalCard   = $transactions->where('payment_method_id', 2)->sum('amount');
            $totalChivo  = $transactions->where('payment_method_id', 3)->sum('amount');
            $totalTransacted = $transactions->sum('amount');

            // Cambiar el estado de la terminal a PRE_CIERRE (7)
            $opening->terminal->update(['status_id' => 7]);

            DB::commit();

            // Preparar detalles por producto (opcional, para imprimir)
            $details = \Modules\Caja\Models\TransactionDetail::with('product')
                ->whereIn('transaction_id', $transactions->pluck('id'))
                ->get()
                ->groupBy('product_id')
                ->map(function ($group) {
                    return [
                        'product_name' => $group->first()->product->product_name,
                        'unit_price'   => $group->first()->unit_price,
                        'quantity'     => $group->sum('quantity'),
                        'total'        => $group->sum('total'),
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Pre-cierre aplicado (estado 7).',
                'totals'  => [
                    'total_cash' => $totalCash,
                    'total_card' => $totalCard,
                    'total_chivo' => $totalChivo,
                    'total_transacted' => $totalTransacted,
                ],
                'details' => $details,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Preclose error: ' . $th->getMessage(), ['opening_id' => $openingId]);
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar pre-cierre.',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
