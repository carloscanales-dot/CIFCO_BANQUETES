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
            ->select('fair_id', 'fair_name', 'start_date', 'end_date', 'status')
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
                }
            })
            ->select('fair_id', 'fair_name')
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

        $message = sprintf('La feria %s, ha sido ingresada exitosamente.', $fair['fair_name']);

        if ($request->expectsJson()) {
            if ($fairId) {
                $newFair = (object) $fair;
                $newFair->fair_id = $fairId;
            }

            return response()->json([
                'message' => $message,
                'fair' => $newFair
            ]);
        }

        return redirect()->back()->with('success', $message);
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
     * Remove the specified resource from storage.
     */
    public function destroy(Fair $fair)
    {
        $fair->delete();

        return redirect()->back()->with('success', sprintf('Eliminado con éxito, la feria %s', $fair->fair_name));
    }

    protected function setDataStore($request)
    {
        $user = Auth::user();
        $current_date = $this->getCurrentDate()->format('Y-m-d H.i:s');

        return [
            'fair_name' => $request->get('fair_name'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
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
