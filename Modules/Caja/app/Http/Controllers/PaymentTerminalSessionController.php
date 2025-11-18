<?php

namespace Modules\Caja\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Caja\App\Models\PaymentTerminalOpening;
use Modules\Caja\App\Models\PaymentTerminalClosing;
use Modules\Caja\App\Models\PaymentTerminal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentTerminalSessionController extends Controller
{
    /**
     * Mostrar lista de aperturas/cierres por terminal
     */
    public function index(Request $request)
    {
        $q = $request->input('q');
        $perPage = $request->input('perPage', 10);

        // Feria activa (status_id = 2 → activa según tu arquitectura)
        $openFair = \Modules\Ticket\Models\Fair::where('status', 2)->first();

        if (!$openFair) {
            return Inertia::render('TerminalSessions', [
                'terminals' => [],
                'filters'   => $request->only(['q', 'perPage']),
                'message'   => 'No hay ninguna feria activa en este momento.',
            ]);
        }

        // Estaciones de la feria activa
        $stationIds = $openFair->stations()->pluck('id');

        // Terminales de esas estaciones
        $terminals = PaymentTerminal::with([
            'station',
            'user',
            'openings' => function ($q) {
                $q->with([
                    'closing',
                    'transactions' //Cargar transacciones de la sesión
                ])
                    ->orderByDesc('opening_date')
                    ->limit(1);
            }

        ])
            ->whereIn('station_id', $stationIds)
            ->when(
                $q,
                fn($query) =>
                $query->where('terminal_name', 'like', "%{$q}%")
            )
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

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
            'expected_amount' => 'required|numeric|min:0',
            'real_amount'     => 'required|numeric|min:0',
            'closing_balance' => 'required|numeric',
            'notes'           => 'nullable|string|max:255',
            'user_id'         => 'required|exists:users,id',
        ]);

        DB::beginTransaction();

        try {
            $closing = PaymentTerminalClosing::create([
                'payment_terminal_opening_id' => $opening->id,
                'payment_terminal_id'         => $opening->payment_terminal_id,
                'user_id'                     => $data['user_id'],
                'closing_date'                => Carbon::now(),
                'expected_amount'             => $data['expected_amount'],
                'real_amount'                 => $data['real_amount'],
                'closing_balance'             => $data['closing_balance'],
                'notes'                       => $data['notes'] ?? '',
            ]);

            // $opening->update([
            //     'opening_amount' => 0
            // ]);
            // Cambiar estado de terminal → cerrada (6)
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
        $closing = \Modules\Caja\App\Models\PaymentTerminalClosing::with([
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
}
