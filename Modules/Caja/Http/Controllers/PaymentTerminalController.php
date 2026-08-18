<?php

namespace Modules\Caja\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Caja\Models\PaymentTerminal;
use App\Models\User;
use Modules\Ticket\Models\Fair;

class PaymentTerminalController extends Controller
{
    /**
     * Mostrar listado de terminales de pago
     */
    public function index(Request $request)
    {
        $q = $request->input('q');

        // La tabla envía perPage/sortBy/sortDir; se validan contra listas blancas
        // para evitar consultas arbitrarias.
        $perPage = (int) $request->input('perPage', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $sortBy  = $request->input('sortBy', 'id');
        if (! in_array($sortBy, ['id', 'terminal_name'], true)) {
            $sortBy = 'id';
        }
        $sortDir = $request->input('sortDir') === 'asc' ? 'asc' : 'desc';

        // Ferias para el selector (todas: permite ver/gestionar terminales de una
        // feria cerrada para conciliación). Por defecto, la abierta más reciente.
        $fairs = Fair::query()
            ->orderByDesc('start_date')
            ->get(['id', 'fair_name', 'start_date', 'end_date', 'status']);

        $selectedFairId = $request->integer('fair_id') ?: Fair::defaultDashboardId();

        // Terminales de la feria seleccionada (vía sus estaciones).
        $stationIds = $selectedFairId
            ? DB::table('stations')->where('fair_id', $selectedFairId)->pluck('id')
            : collect();

        $terminals = PaymentTerminal::with(['station', 'user', 'status'])
            ->whereIn('station_id', $stationIds)
            ->when($q, fn($query) => $query->where('terminal_name', 'like', "%{$q}%"))
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString();

        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::render('PaymentTerminals', [
            'terminals'      => $terminals,
            // Solo stands de ferias ABIERTAS (etiquetados con su feria) para crear/editar.
            'stations'       => Fair::openStationOptions(),
            'users'          => $users,
            'fairs'          => $fairs,
            'selectedFairId' => $selectedFairId,
            'filters'        => [
                'q'       => $q,
                'perPage' => $perPage,
                'fair_id' => $selectedFairId,
                'sortBy'  => $sortBy,
                'sortDir' => $sortDir,
            ],
        ]);
    }

    /**
     * Crear una nueva terminal
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'terminal_name' => 'required|string|max:65',
            'station_id'    => 'required|exists:stations,id',
            'user_id'       => 'nullable|exists:users,id',
        ]);

        if (! Fair::stationBelongsToOpenFair($data['station_id'])) {
            return back()->withErrors([
                'station_id' => 'La estación seleccionada no pertenece a una feria abierta.',
            ]);
        }

        // por defecto: caja cerrada (status_id = 6)
        $data['status_id'] = 6;

        PaymentTerminal::create($data);

        return redirect()->route('payment-terminals.index');
    }

    /**
     * Actualizar una terminal existente
     */
    public function update(Request $request, PaymentTerminal $paymentTerminal)
    {
        $data = $request->validate([
            'terminal_name' => 'required|string|max:65',
            'station_id'    => 'required|exists:stations,id',
            'user_id'       => 'nullable|exists:users,id',
        ]);

        if (! Fair::stationBelongsToOpenFair($data['station_id'])) {
            return back()->withErrors([
                'station_id' => 'La estación seleccionada no pertenece a una feria abierta.',
            ]);
        }

        $paymentTerminal->update($data);

        return redirect()->route('payment-terminals.index');
        //->with('success', 'Terminal actualizada correctamente.');
    }

    /**
     * Eliminar una terminal
     */
    public function destroy(PaymentTerminal $paymentTerminal)
    {
        $paymentTerminal->delete();

        return redirect()->route('payment-terminals.index');
        //->with('success', 'Terminal eliminada correctamente.');
    }

    /**
     * Obtener el estado de una terminal
     */
    public function getStatus(PaymentTerminal $paymentTerminal)
    {
        return response()->json([
            'status_id' => $paymentTerminal->status_id,
        ]);
    }
}
