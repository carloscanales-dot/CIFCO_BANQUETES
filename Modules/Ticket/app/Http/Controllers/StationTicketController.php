<?php

namespace Modules\Ticket\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Ticket\Exports\StationTicketExport;

use Modules\Ticket\Traits\SetFilterQuery;

class StationTicketController extends Controller
{
    use SetFilterQuery;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Obtener la estación del usuario autenticado
        $userStation = DB::table('station_users')
            ->where('user_id', Auth::id())
            ->first();

        if (!$userStation) {
            return Inertia::render('Ticket/StationTicket/Index', [
                'result' => collect([]),
            ]);
        }

        // JOIN: tickets → station_tickets
        // Mostrar solo tickets canjeados (status = 0) en la estación del usuario
        $query = DB::table('station_tickets')
            ->join('tickets', 'station_tickets.ticket_id', '=', 'tickets.id')
            ->join('products', 'tickets.product_id', '=', 'products.id')
            ->join('stations', 'station_tickets.station_id', '=', 'stations.id')
            ->where('station_tickets.station_id', $userStation->station_id)
            ->where('tickets.status', 0)  // Solo tickets canjeados (status 0)
            ->when($request->get('search'), function ($query, $search) {
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
            ->select(
                'products.product_name',
                'tickets.uuid',
                'stations.station_name',
                'station_tickets.created_at'
            )
            ->paginate($request->get('limit', 10));

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return Inertia::render('Ticket/StationTicket/Index', [
            'result' => $result
        ]);
    }

    public function export(Request $request)
    {
        return Excel::download(new StationTicketExport($request), 'station_tickets_' . $this->getCurrentDate()->format('Y-m-d') . '.xlsx');
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
            'station_id' => [
                'field' => 'station_id',
                'operator' => 'equal'
            ],
            'product_id' => [
                'field' => 'product_id',
                'operator' => 'equal'
            ],
            'start_created_at' => [
                'field' => 'created_at',
                'operator' => 'greaterThan'
            ],
            'end_created_at' => [
                'field' => 'created_at',
                'operator' => 'lessThan'
            ]
        ];

        return $fieldList[$field] ?? null;
    }
}
