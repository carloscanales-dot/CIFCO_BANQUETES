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
use Illuminate\Http\Exceptions\HttpResponseException;

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

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket no encontrado.',
                'ticket' => null
            ]);
        }

        $product_name = Str::upper($ticket->product_name);
        // Determinar si el usuario está asociado a una estación y si el producto
        // está asignado a esa estación.
        $station = $this->getStation();
        $assigned = true;

        if ($station) {
            $productId = DB::table('v_tickets')->where('uuid', trim($uuid))->value('product_id');
            $stationId = $station->station_id;

            // ✅ La cortesía debe pertenecer a la feria del stand (si está atada a una).
            $ticketFairId = DB::table('tickets')->where('id', $ticket->ticket_id)->value('fair_id');
            if ($ticketFairId && (int) $ticketFairId !== (int) $station->fair_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta cortesía pertenece a otra feria y no puede canjearse en este stand.',
                    'ticket' => $ticket,
                ]);
            }

            // ✅ Verificar si el ticket YA FUE ESCANEADO en esta estación
            $alreadyScanned = DB::table('station_tickets')
                ->where('ticket_id', $ticket->ticket_id)
                ->where('station_id', $stationId)
                ->exists();

            if ($alreadyScanned) {
                return response()->json([
                    'success' => false,
                    'message' => "El ticket $ticket->uuid ya fue ESCANEADO en esta estación.",
                    'ticket' => $ticket,
                    'already_scanned' => true // Indicador especial
                ]);
            }

            $hasProduct = DB::table('station_products')
                ->where('station_id', $stationId)
                ->where('product_id', $productId)
                ->exists();

            $assigned = $hasProduct;
        } else {
            // Si el usuario no está asociado a una estación, marcamos assigned = false.
            $assigned = false;
        }

        // status_id: 3 = PENDIENTE, 1 = APLICADO, 2 = ANULADO
        switch ($ticket->status_id) {
            case 1: // APLICADO
                $success = false;
                $message = "El producto $product_name, ya ha sido APLICADO.";
                break;
            case 2: // ANULADO
                $success = false;
                $message = "El producto $product_name, está ANULADO.";
                break;
            case 3: // PENDIENTE
            default:
                if (! $assigned) {
                    $success = false;
                    $message = 'Este producto NO está asignado a la estación donde estás trabajando.';
                } else {
                    $message = "El producto $product_name, esta disponible, desea CANJEARLO?.";
                }
        }

        // Añadimos la propiedad 'assigned' al ticket para que el frontend pueda
        // manejar el modal específico de producto no asignado.
        $ticket = (object) array_merge((array) $ticket, ['assigned' => $assigned]);

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

        if (!$station) {
            throw new HttpResponseException(response()->json([
                'message' => 'User is not associated with a station.'
            ], 403));
        }

        // ================================
        // 🔥 VALIDAR QUE LA ESTACIÓN TENGA EL PRODUCTO
        // ================================
        $ticket = DB::table('tickets')
            ->where('id', $request->get('ticket_id'))
            ->first();

        if (!$ticket) {
            return response()->json([
                'message' => 'El ticket no existe.'
            ], 404);
        }

        $productId = $ticket->product_id;
        $stationId = $station->station_id;

        // La cortesía debe pertenecer a la feria del stand (si está atada a una).
        if ($ticket->fair_id && (int) $ticket->fair_id !== (int) $station->fair_id) {
            return response()->json([
                'success' => false,
                'message' => 'Esta cortesía pertenece a otra feria y no puede canjearse en este stand.'
            ], 403);
        }

        $hasProduct = DB::table('station_products')
            ->where('station_id', $stationId)
            ->where('product_id', $productId)
            ->exists();

        if (!$hasProduct) {
            return response()->json([
                'message' => 'Este producto NO está asignado a la estación donde estás trabajando.'
            ], 403);
        }

        // ✅ Verificar si el ticket YA FUE ESCANEADO en esta estación
        $alreadyScanned = DB::table('station_tickets')
            ->where('ticket_id', $request->get('ticket_id'))
            ->where('station_id', $stationId)
            ->exists();

        if ($alreadyScanned) {
            return response()->json([
                'message' => 'Este ticket ya fue escaneado en esta estación.',
                'success' => false
            ], 409); // 409 Conflict
        }
        // ================================

        $dataStore = $this->setDataStore($request, $stationId);

        DB::transaction(function () use ($dataStore) {
            $stationTicketId = DB::table('station_tickets')->insertGetId($dataStore);

            if ($stationTicketId) {
                DB::table('tickets')->where('id', $dataStore['ticket_id'])->update([
                    'status_id' => 1, // APLICADO
                    'redeem_date' => $this->getCurrentDate(),
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Ticket aplicado correctamente.'
        ], 200);
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

    private function setDataStore($request, $station_id)
    {
        $current_date = $this->getCurrentDate()->format('Y-m-d H.i:s');

        return [
            'ticket_id' => $request->get('ticket_id'),
            'station_id' => $station_id,
            'created_at' => $current_date,
            'updated_at' => $current_date,
        ];
    }

    private function getStation()
    {
        $user = Auth::user();

        // Un cajero podría tener asignaciones en varias ferias; se prefiere el
        // stand de una feria ABIERTA (status = 2) y, entre ellas, la más reciente.
        return DB::table('station_users')
            ->join('stations', 'station_users.station_id', '=', 'stations.id')
            ->join('fairs', 'stations.fair_id', '=', 'fairs.id')
            ->where('station_users.user_id', $user->id)
            ->orderByRaw('CASE WHEN fairs.status = 2 THEN 0 ELSE 1 END')
            ->orderByDesc('fairs.start_date')
            ->select('station_users.station_id', 'stations.fair_id')
            ->first();
    }

    private function queryTicket($request)
    {
        return DB::table('v_tickets')
            ->when($request->get('product_id'), function ($query, $product_id) {
                return $query->where('product_id', $product_id);
            })
            ->when($request->get('status'), function ($query, $status) {
                // Mapear estados del filtro frontend a status_id
                $statusMap = [
                    'D' => 3, // Disponible -> PENDIENTE
                    'C' => 1, // Canjeado -> APLICADO
                    'A' => 2, // Anulado -> ANULADO
                ];
                $statusId = $statusMap[$status] ?? null;
                if ($statusId) {
                    return $query->where('status_id', $statusId);
                }
                return $query;
            })
            ->when($request->get('uuid'), function ($query, $uuid) {
                return $query->where('uuid', 'like', '%' . $uuid . '%');
            })
            ->when($request->get('fair_id'), function ($query, $fair_id) {
                return $query->where('fair_id', $fair_id);
            })
            ->when($request->get('start_id'), function ($query, $start_id) {
                return $query->where('ticket_id', '>=', $start_id);
            })
            ->when($request->get('end_id'), function ($query, $end_id) {
                return $query->where('ticket_id', '<=', $end_id);
            })
            ->select('product_name', 'uuid', 'unit_price', 'ticket_id')
            ->get();
    }

    private function getTicket($uuid)
    {
        // Coincidencia EXACTA: el QR codifica el uuid tal cual. Un LIKE '%uuid%'
        // colisiona con uuids donde uno es substring de otro (p. ej. MINI-1 vs
        // MINI-10) y podría devolver/canjear un ticket distinto al escaneado.
        return DB::table('v_tickets')
            ->where('uuid', trim($uuid))
            ->select('ticket_id', 'product_name', 'uuid', 'unit_price', 'status_id', 'status')
            ->first();
    }

    protected function getCurrentDate()
    {
        return Carbon::now()->setTimezone(config('app.timezone'));
    }
}
