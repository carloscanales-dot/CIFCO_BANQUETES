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
    use FiltersTickets;

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
        return $this->applyTicketFilters(DB::table('v_tickets'), $this->request)
            // El orden fija el correlativo; sin él quedaba a criterio del motor.
            ->orderBy('ticket_id')
            ->select('ticket_id', 'product_name', 'uuid', 'unit_price', 'generated_for', 'status')
            ->get()
            ->values()
            ->map(function ($ticket, $index) {
                return [
                    // Correlativo del listado exportado, empieza en 1. El
                    // ticket_id es global y arrastra el de ferias anteriores.
                    'correlativo' => $index + 1,
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
        return ['N°', 'ID Ticket', 'Producto', 'UUID', 'Precio', 'Generado para', 'Estatus'];
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
