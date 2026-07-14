<?php

namespace Modules\Ticket\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketsSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Object
    {
        return DB::table('v_tickets')
            ->when($this->request->get('product_id'), function ($query, $product_id) {
                return $query->where('product_id', $product_id);
            })
            ->when($this->request->get('status'), function ($query, $status) {
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
            ->when($this->request->get('uuid'), function ($query, $uuid) {
                return $query->where('uuid', 'like', '%' . $uuid . '%');
            })
            ->when($this->request->get('start_id'), function ($query, $start_id) {
                return $query->where('ticket_id', '>=', $start_id);
            })
            ->when($this->request->get('end_id'), function ($query, $end_id) {
                return $query->where('ticket_id', '<=', $end_id);
            })
            ->select('ticket_id', 'product_name', 'uuid', 'unit_price', 'generated_for', 'status')
            ->get()
            ->map(function ($ticket) {
                return [
                    'ticket_id' => $ticket->ticket_id,
                    'product_name' => $ticket->product_name,
                    'uuid' => $ticket->uuid,
                    'unit_price' => $ticket->unit_price,
                    'generated_for' => $ticket->generated_for ?? 'N/A',
                    'status' => $ticket->status, // Ya viene como texto desde la vista
                ];
            });
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return ['ID', 'Producto', 'UUID', 'Precio', 'Generado para', 'Estatus'];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Tickets';
    }

    /**
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
