<?php

namespace Modules\Ticket\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TicketExport implements WithMultipleSheets
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        return [
            new TicketsSheet($this->request),
            new TicketsAnalysisSheet($this->request),
        ];
    }
}
