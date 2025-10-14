<?php

namespace Modules\Ticket\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Ticket\Http\Requests\ReaderRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Ticket\Exports\TicketExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;

use Modules\Ticket\Traits\SetFilterQuery;

class ReaderController extends Controller
{
    use SetFilterQuery;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Ticket/Reader/Index');
    }

    public function validateTicket($uuid)
    {
        $success = true;
        $message = '';

        $ticket = $this->getTicket($uuid);
        $product_name = Str::upper($ticket->product_name);

        switch ($ticket->status) {
            case 'C':
                $success = false;
                $message = "El producto $product_name, ya ha sido CANJEADO.";
                break;
            case 'A':
                $success = false;
                $message = "El producto $product_name, ha sido ANULADO.";
                break;
            default:
                $message = "El producto $product_name, esta disponible, desea CANJEARLO?.";
        }

        return response()->json([
            'success' => $success,
            'message' => $message,
            'ticket' => $ticket
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(ReaderRequest $request)
    {
        $station = $this->getStation();
        $dataStore = $this->setDataStore($request, $station->station_user_id);

        DB::transaction(function () use ($dataStore) {
            $stationTicketId = DB::table('station_tickets')->insertGetId($dataStore);

            if ($stationTicketId) {
                DB::table('tickets')->where('ticket_id', $dataStore['ticket_id'])->update([
                    'status' => 'C'
                ]);
            }
        });
    }

    public function ticketPdf(Request $request)
    {
        if ($request->get('type') === 'excel') {
            return Excel::download(new TicketExport($request), 'tickets_' . $this->getCurrentDate()->format('Y-m-d') . '.xlsx');
        } else {
            $result = $this->queryTicket($request);

            $tickets = $result->map(function ($ticket) {
                return [
                    'uuid' => $ticket->uuid,
                    'product_name' => $ticket->product_name,
                    'unit_price' => $ticket->unit_price,
                    'ticket_id' => $ticket->ticket_id
                ];
            })
                ->toArray();

            $pdf = Pdf::loadView('reports.tickets', [
                'data' => $tickets,
                'columns' => 2, // Número de columnas
                'rows' => 2,
                'qrSize' => 65 // Tamaño en px (ajustable)
            ]);

            return $pdf->download('tickets.pdf');
        }
    }

    private function setDataStore($request, $station_user_id)
    {
        $current_date = $this->getCurrentDate()->format('Y-m-d H.i:s');

        return [
            'ticket_id' => $request->get('ticket_id'),
            'station_user_id' => $station_user_id,
            'created_at' => $current_date,
            'updated_at' => $current_date,
        ];
    }

    private function getStation()
    {
        $user = Auth::user();

        return DB::table('station_users')
            ->where('user_id', $user->user_id)
            ->first();
    }

    private function queryTicket($request)
    {
        return DB::table('v_tickets')
            ->when($request->get('product_id'), function ($query, $product_id) {
                return $query->where('product_id', $product_id);
            })
            ->when($request->get('status'), function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->get('uuid'), function ($query, $uuid) {
                return $query->where('uuid', 'like', '%' . $uuid . '%');
            })
            ->select('product_name', 'uuid', 'unit_price', 'ticket_id')
            ->get();
    }

    private function getTicket($uuid)
    {
        return DB::table('v_tickets')
            ->where('uuid', 'like', '%' . $uuid . '%')
            ->select('ticket_id', 'product_name', 'uuid', 'unit_price', 'status')
            ->first();
    }

    protected function getCurrentDate()
    {
        return Carbon::now()->setTimezone(config('app.timezone'));
    }
}
