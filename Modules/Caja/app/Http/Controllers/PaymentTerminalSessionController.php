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
     * Mostrar la lista de aperturas (con su cierre si existe)
     */
    public function index(Request $request)
    {
        $q = $request->input('q');
        $perPage = $request->input('perPage', 10);

        // 🟢 Buscar la feria activa
        $openFair = \Modules\Ticket\Models\Fair::where('status', 2)->first();

        if (!$openFair) {
            return Inertia::render('TerminalSessions', [
                'terminals' => [],
                'filters'   => $request->only(['q', 'perPage']),
                'message'   => 'No hay ninguna feria activa en este momento.',
            ]);
        }

        // 🟢 Obtener estaciones de la feria activa
        $stationIds = $openFair->stations()->pluck('id');

        // 🟢 Consultar terminales de esas estaciones
        $terminals = \Modules\Caja\App\Models\PaymentTerminal::with([
            'station',
            'user',
            // Cargar su apertura más reciente y su cierre si existe
            'openings' => function ($q) {
                $q->with('closing')
                    ->orderByDesc('opening_date')
                    ->limit(1); // solo la última apertura
            }
        ])
            ->whereIn('station_id', $stationIds)
            ->when($q, fn($query) => $query->where('terminal_name', 'like', "%{$q}%"))
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // 🟢 Estaciones de la feria activa
        $stations = $openFair->stations()
            ->select('id', 'station_name')
            ->orderBy('station_name')
            ->get();

        return Inertia::render('TerminalSessions', [
            'terminals' => $terminals,
            'stations'  => $stations,
            'fair'      => $openFair,
            'filters'   => $request->only(['q', 'perPage']),
        ]);
    }



    /**
     * Registrar una nueva apertura de terminal
     */
    public function open(Request $request)
    {
        $data = $request->validate([
            'payment_terminal_id' => 'required|exists:payment_terminal,id',
            'user_id'             => 'required|exists:users,id',
            'opening_amount'      => 'required|numeric|min:0',
        ]);

        // Verificar si la terminal ya está abierta
        $alreadyOpen = PaymentTerminalOpening::where('payment_terminal_id', $data['payment_terminal_id'])
            ->whereDoesntHave('closing')
            ->exists();

        if ($alreadyOpen) {
            return response()->json([
                'success' => false,
                'message' => 'La terminal ya tiene una apertura activa.',
            ], 400);
        }

        $data['opening_date'] = Carbon::now();

        $opening = PaymentTerminalOpening::create($data);

        // Opcional: cambiar estado de la terminal
        $terminal = PaymentTerminal::find($data['payment_terminal_id']);
        $terminal->update(['status' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Apertura registrada correctamente.',
            'opening' => $opening,
        ]);
    }

    /**
     * Registrar el cierre de una terminal
     */
    public function close(Request $request, $id)
    {
        $opening = PaymentTerminalOpening::with('terminal')->findOrFail($id);

        if ($opening->is_closed) {
            return response()->json([
                'success' => false,
                'message' => 'Esta apertura ya fue cerrada.',
            ], 400);
        }

        $data = $request->validate([
            'expected_amount' => 'required|numeric|min:0',
            'real_amount'     => 'required|numeric|min:0',
            'closing_balance' => 'required|numeric|min:0',
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

            // Opcional: marcar terminal como cerrada
            $opening->terminal->update(['status' => 2]);

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
}
