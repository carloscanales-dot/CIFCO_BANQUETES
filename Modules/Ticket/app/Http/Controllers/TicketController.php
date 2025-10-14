<?php

namespace Modules\Ticket\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Ticket\Models\Ticket;
use Modules\Ticket\Http\Requests\TicketRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use Modules\Ticket\Traits\SetFilterQuery;

class TicketController extends Controller
{
    use SetFilterQuery;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DB::table('v_tickets')->when($request->get('search'), function ($query, $search) {
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
            ->select('ticket_id', 'uuid', 'product_name', 'unit_price', 'status')
            ->paginate($request->get('limit', 10));

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return Inertia::render('Ticket/Ticket/Index', [
            'result' => $result
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Ticket/Ticket/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketRequest $request)
    {
        $tickets = $this->setDataStore($request);
        DB::table('tickets')->insert($tickets);

        $message = sprintf('La cantidad de tickets generados es %s, ', $request->get('quantity'));

        if ($request->expectsJson()) {
            $tickets = DB::table('v_tickets')
                ->where('product_id', $request->get('product_id'))
                ->select('ticket_id', 'uuid', 'product_name', 'unit_price', 'status')
                ->get();

            return response()->json([
                'message' => $message,
                'tickets' => $tickets
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ticket $ticket): Response
    {
        return Inertia::render('Ticket/Ticket/Edit', [
            'ticket' => $ticket
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TicketRequest $request, Ticket $ticket)
    {
        $ticket->update($request->all());

        return redirect()->back()->with('success', sprintf('Actualizado con éxito, el ticket %s', $ticket->ticket_name));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        return redirect()->back()->with('success', sprintf('Eliminado con éxito, el ticket %s', $ticket->ticket_name));
    }

    protected function setDataStore($request)
    {
        $inserts = [];
        $user = Auth::user();
        $current_date = $this->getCurrentDate()->format('Y-m-d H.i:s');
        $prefix = $this->getPrefixProduct($request->get('product_id'));
        $max_product_id = $this->getMaxProductId($request->get('product_id'));

        for ($i = 0; $i < $request->get('quantity'); $i++) {
            $inserts[] = [
                'uuid' => implode('-', [$prefix, $max_product_id]),
                'status' => $request->get('status'),
                'product_id' => $request->get('product_id'),
                'user_id' => $user->user_id,
                'created_at' => $current_date,
                'updated_at' => $current_date

            ];

            $max_product_id++;
        }

        return $inserts;
    }

    protected function getMaxProductId($product_id)
    {
        $product_max_id = 1;

        $ticket =  DB::table('tickets')
            ->where('product_id', $product_id)
            ->orderBy('ticket_id', 'desc')
            ->limit(0, 1)
            ->first();

        if ($ticket) {
            $prefixExplode = explode('-', $ticket->uuid);
            $product_max_id = (int) $prefixExplode[1];
            $product_max_id++;
        }

        return $product_max_id;
    }

    protected function getPrefixProduct($product_id)
    {
        $product = DB::table('products')
            ->where('product_id', $product_id)
            ->first();

        return $product->prefix;
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
            'uuid' => [
                'field' => 'uuid',
                'operator' => 'like'
            ],
            'status' => [
                'field' => 'status',
                'operator' => 'equal'
            ],
            'product_id' => [
                'field' => 'product_id',
                'operator' => 'equal'
            ],
        ];

        return $fieldList[$field] ?? null;
    }
}
