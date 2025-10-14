<?php

namespace Modules\Ticket\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
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
        $query = DB::table('v_station_tickets')->when($request->get('search'), function ($query, $search) {
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
            ->select('product_name', 'uuid', 'station_name', 'created_at')
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
