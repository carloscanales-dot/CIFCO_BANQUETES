<?php

namespace Modules\Ticket\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Ticket\Models\Station;
use Modules\Ticket\Http\Requests\StationRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use Modules\Ticket\Traits\SetFilterQuery;

class StationController extends Controller
{
    use SetFilterQuery;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DB::table('stations')->when($request->get('search'), function ($query, $search) {
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
            ->select('station_id', 'station_name', 'status')
            ->paginate($request->get('limit', 10));

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return Inertia::render('Ticket/Station/Index', [
            'result' => $result
        ]);
    }

    /**
     * List categories load resource
     */
    public function list($enabled)
    {
        $stations =  DB::table('stations')
            ->where(function ($query) use ($enabled) {
                if ($enabled === 'Activa') {
                    $query->where('status', 'Activa');
                }
            })
            ->select('station_id', 'station_name')
            ->orderBy('station_name', 'asc')
            ->get();

        return response()->json(['stations' => $stations]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Ticket/Station/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StationRequest $request)
    {
        $station = $this->setDataStore($request);
        $stationId = DB::table('stations')->insertGetId($station);

        $station = $this->getStationById($stationId);

        if ($request->has('product_ids')) {
            $station->products()->sync($request->input('product_ids'));
        }

        if ($request->has('user_ids')) {
            $station->users()->sync($request->input('user_ids'));
        }

        $message = sprintf('La estacion %s, ha sido ingresada exitosamente.', $station['station_name']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'station' => $station
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Station $station): Response
    {
        $station->product_ids = $station->products->pluck('product_id');
        $station->user_ids = $station->users->pluck('user_id');

        return Inertia::render('Ticket/Station/Edit', [
            'station' => $station
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StationRequest $request, Station $station)
    {
        $station->update($request->all());

        if ($request->has('product_ids')) {
            $station->products()->sync($request->input('product_ids'));
        }

        if ($request->has('user_ids')) {
            $station->users()->sync($request->input('user_ids'));
        }

        return redirect()->back()->with('success', sprintf('Actualizado con éxito, la estacion %s', $station->station_name));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Station $station)
    {
        $station->delete();

        return redirect()->back()->with('success', sprintf('Eliminado con éxito, la estacion %s', $station->station_name));
    }

    private function getStationById($station_id)
    {
        return Station::find($station_id);
    }

    protected function setDataStore($request)
    {
        $user = Auth::user();
        $current_date = $this->getCurrentDate()->format('Y-m-d H.i:s');

        return [
            'station_name' => $request->get('station_name'),
            'status' => $request->get('status'),
            'user_id' => $user->user_id,
            'created_at' => $current_date,
            'updated_at' => $current_date
        ];
    }

    protected function getCurrentDate()
    {
        return Carbon::now()->setTimezone(config('app.timezone'));
    }

    /**
     * @param type $field
     * @return type
     */
    protected function setField($field): array
    {
        $fieldList = [
            'station_name' => [
                'field' => 'station_name',
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
