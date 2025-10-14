<?php

namespace Modules\Ticket\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class TicketExport implements FromCollection, WithHeadings
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
                return $query->where('status', $status);
            })
            ->when($this->request->get('uuid'), function ($query, $uuid) {
                return $query->where('uuid', 'like', '%' . $uuid . '%');
            })
            ->select('ticket_id', 'product_name', 'uuid', 'unit_price')
            ->get();
    }

    /**
     * @return response()
     */
    public function headings(): array
    {
        return ['ticket_id', 'product_name', 'uuid', 'unit_price'];
    }
}
