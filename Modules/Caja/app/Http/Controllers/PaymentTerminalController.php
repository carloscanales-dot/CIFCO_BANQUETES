<?php

namespace Modules\Caja\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Caja\App\Models\PaymentTerminal;
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
        $perPage = $request->input('perPage', 10);

        $terminals = PaymentTerminal::with(['station', 'user', 'status'])
            ->when($q, fn($query) => $query->where('terminal_name', 'like', "%{$q}%"))
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Feria abierta (status_id = 5 → abierta)
        $openFair = Fair::where('status', 2)->first();

        $stations = $openFair
            ? $openFair->stations()->select('id', 'station_name')->orderBy('station_name')->get()
            : collect();

        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::render('PaymentTerminals', [
            'terminals' => $terminals,
            'stations'  => $stations,
            'users'     => $users,
            'filters'   => $request->only(['q', 'perPage']),
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

        // por defecto: estado cerrado (6)
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
     * Cambiar estado (1 abierta / 2 cerrada)
     */
    // Modules/Caja/Http/Controllers/PaymentTerminalController.php
    public function toggleStatus(PaymentTerminal $paymentTerminal)
    {
        // Alternar entre abierto (5) y cerrado (6)
        $nuevoEstado = $paymentTerminal->status_id == 5 ? 6 : 5;

        $paymentTerminal->update(['status_id' => $nuevoEstado]);

        return response()->json([
            'success'    => true,
            'status_id'  => $nuevoEstado,
        ]);
    }
}
