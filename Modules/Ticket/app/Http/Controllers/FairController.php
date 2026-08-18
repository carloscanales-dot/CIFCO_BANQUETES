<?php

namespace Modules\Ticket\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Ticket\Models\Fair;
use Modules\Ticket\Http\Requests\FairRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use Modules\Ticket\Traits\SetFilterQuery;

class FairController extends Controller
{
    use SetFilterQuery;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DB::table('fairs')->when($request->get('search'), function ($query, $search) {
            return $query->where(function ($query) use ($search) {
                foreach ($search as $field => $value) {
                    $filter = $this->setField($field);

                    if (!is_null($filter) && !is_null($value)) {
                        $this->setFilter($query, $filter['operator'], $filter['field'], $value);
                    }
                }
            });
        })->when($request->get('sort'), function ($query, $sortBy) {
            return $query->orderBy($sortBy['key'], $sortBy['order']);
        });

        $result = $query
            ->select('id', 'fair_name', 'start_date', 'end_date', 'status')
            ->paginate($request->get('limit', 10));

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return Inertia::render('Ticket/Fair/Index', [
            'result' => $result
        ]);
    }

    /**
     * List categories load resource
     */
    public function list($enabled)
    {
        $fairs =  DB::table('fairs')
            ->where(function ($query) use ($enabled) {
                if ($enabled === 'A') {
                    $query->where('status', true);
                } elseif ($enabled === 'O') {
                    // Solo ferias ABIERTAS (status = 2).
                    $query->where('status', Fair::STATUS_OPEN);
                }
            })
            ->select('id', 'fair_name')
            ->orderBy('fair_name', 'asc')
            ->get();

        return response()->json(['fairs' => $fairs]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Ticket/Fair/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FairRequest $request)
    {
        $fair = $this->setDataStore($request);
        $fairId = DB::table('fairs')->insertGetId($fair);
        $message = sprintf('La feria %s ha sido ingresada exitosamente.', $fair['fair_name']);
        if ($request->expectsJson()) {
            if ($fairId) {
                $newFair = (object) $fair;
                $newFair->fair_id = $fairId;
            }
            return response()->json([
                'message' => $message,
                'fair' => $newFair ?? null,
            ]);
        }
        // Redirigir directamente a la vista de edición
        return redirect()->route('fair.edit', $fairId)
            ->with('fair', (object) array_merge($fair, ['fair_id' => $fairId]))
            ->with('success', $message);
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Fair $fair): Response
    {
        return Inertia::render('Ticket/Fair/Edit', [
            'fair' => $fair
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FairRequest $request, Fair $fair)
    {
        $fair->update($request->all());

        return redirect()->back()->with('success', sprintf('Actualizado con éxito, la feria %s', $fair->fair_name));
    }

    /**
     * Clona una feria origen hacia una feria nueva.
     *
     * Crea la feria nueva y replica sus stands (registros nuevos, ids nuevos) como
     * punto de partida editable. Opcionalmente copia las asignaciones de productos,
     * usuarios e impresoras por stand. NO duplica productos ni usuarios (son globales):
     * solo re-crea las asignaciones (pivotes) hacia los stands nuevos.
     */
    public function clone(Request $request)
    {
        $validated = $request->validate([
            'source_fair_id'       => 'required|integer|exists:fairs,id',
            'fair_name'            => 'required|string|max:1000',
            'start_date'           => 'required|date',
            'end_date'             => 'required|date|after_or_equal:start_date',
            'status'               => 'required|integer|in:1,2,3',
            'copy_products'        => 'boolean',
            'copy_users'           => 'boolean',
            'copy_printers'        => 'boolean',
            'copy_terminals'       => 'boolean',
            'only_active_stations' => 'boolean',
        ]);

        $newFairId = DB::transaction(function () use ($validated) {
            $now = $this->getCurrentDate()->format('Y-m-d H:i:s');

            // 1. Crear la feria nueva.
            $newFairId = DB::table('fairs')->insertGetId([
                'fair_name'  => $validated['fair_name'],
                'start_date' => $validated['start_date'],
                'end_date'   => $validated['end_date'],
                'status'     => $validated['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // 2. Stands de la feria origen (todos o solo activos).
            $stationsQuery = DB::table('stations')->where('fair_id', $validated['source_fair_id']);
            if (!empty($validated['only_active_stations'])) {
                $stationsQuery->where('status', 1);
            }
            $sourceStations = $stationsQuery->get();

            foreach ($sourceStations as $src) {
                // 3. Stand nuevo (registro nuevo ligado a la feria nueva).
                $newStationId = DB::table('stations')->insertGetId([
                    'station_name' => $src->station_name,
                    'status'       => $src->status,
                    'location_id'  => $src->location_id,
                    'fair_id'      => $newFairId,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);

                // 4. Copiar productos asignados (station_products).
                if (!empty($validated['copy_products'])) {
                    $rows = DB::table('station_products')->where('station_id', $src->id)
                        ->pluck('product_id')
                        ->map(fn($pid) => [
                            'product_id' => $pid,
                            'station_id' => $newStationId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])->all();
                    if ($rows) {
                        DB::table('station_products')->insert($rows);
                    }
                }

                // 5. Copiar usuarios asignados (station_users).
                if (!empty($validated['copy_users'])) {
                    $rows = DB::table('station_users')->where('station_id', $src->id)
                        ->pluck('user_id')
                        ->map(fn($uid) => [
                            'station_id' => $newStationId,
                            'user_id'    => $uid,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])->all();
                    if ($rows) {
                        DB::table('station_users')->insert($rows);
                    }
                }

                // 6. Copiar impresoras del stand (registro nuevo por stand).
                if (!empty($validated['copy_printers'])) {
                    $printers = DB::table('printer')->where('station_id', $src->id)->get();
                    foreach ($printers as $p) {
                        DB::table('printer')->insert([
                            'printer_name' => $p->printer_name,
                            'status'       => $p->status,
                            'station_id'   => $newStationId,
                            'ip_adress'    => $p->ip_adress,
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ]);
                    }
                }

                // 7. Copiar terminales de pago (cajas) del stand.
                // La terminal nueva nace en caja CERRADA (status_id = 6) y sin
                // aperturas/cierres: es infraestructura nueva para la feria nueva.
                if (!empty($validated['copy_terminals'])) {
                    $terminals = DB::table('payment_terminal')->where('station_id', $src->id)->get();
                    foreach ($terminals as $t) {
                        DB::table('payment_terminal')->insert([
                            'terminal_name' => $t->terminal_name,
                            'station_id'    => $newStationId,
                            'user_id'       => $t->user_id,
                            'status_id'     => 6, // caja cerrada
                            'created_at'    => $now,
                            'updated_at'    => $now,
                        ]);
                    }
                }
            }

            return $newFairId;
        });

        $message = sprintf(
            'Feria "%s" clonada correctamente. Ajusta los stands, usuarios y productos según necesites.',
            $validated['fair_name']
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'fair_id' => $newFairId]);
        }

        return redirect()->route('fair.edit', $newFairId)->with('success', $message);
    }

    /**
     * Cambia únicamente el estado de la feria (Programada/Abierta/Cerrada).
     * Acción rápida desde el listado, sin abrir el formulario completo.
     */
    public function changeStatus(Request $request, Fair $fair)
    {
        $validated = $request->validate([
            'status' => 'required|integer|in:1,2,3',
        ]);

        $fair->update(['status' => $validated['status']]);

        $labels = [1 => 'Programada', 2 => 'Abierta', 3 => 'Cerrada'];

        return redirect()->back()->with(
            'success',
            sprintf('La feria %s ahora está %s.', $fair->fair_name, $labels[$validated['status']])
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Fair $fair)
    {
        $fair->delete();

        return redirect()->back()->with('success', sprintf('Eliminado con éxito, la feria %s', $fair->fair_name));
    }

    protected function setDataStore($request)
    {
        $current_date = $this->getCurrentDate()->format('Y-m-d H:i:s');

        return [
            'fair_name' => $request->get('fair_name'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'status' => (int) $request->get('status'), // enviar siempre número
            'created_at' => $current_date,
            'updated_at' => $current_date
        ];
    }


    protected function getCurrentDate()
    {
        return Carbon::now()->setTimezone(config('app.timezone'));
    }

    /**
     * @param string $field
     * @return array|null
     */
    protected function setField(string $field): ?array
    {
        $fieldList = [
            'fair_name' => [
                'field' => 'fair_name',
                'operator' => 'like'
            ],
            'status' => [
                'field' => 'status',
                'operator' => 'equal'
            ]
        ];

        return $fieldList[$field] ?? null;
    }
}
