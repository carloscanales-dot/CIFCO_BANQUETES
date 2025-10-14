<?php

namespace Modules\Ticket\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class StationTicketExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnFormatting,
    WithCustomStartCell,
    WithEvents
{
    protected $request;
    protected $total = 0;
    protected $stationName;

    public function __construct($request)
    {
        $this->request = $request;

        $stationId = $request->get('station_id');
        $this->stationName = $stationId
            ? DB::table('stations')->where('station_id', $stationId)->value('station_name')
            : 'TODAS LAS ESTACIONES';

    }

    public function collection()
    {
        return DB::table('v_station_tickets')
            ->when($this->request->get('station_id'), fn($q, $id) => $q->where('station_id', $id))
            ->when($this->request->get('product_id'), fn($q, $id) => $q->where('product_id', $id))
            ->when($this->request->get('start_created_at'), fn($q, $date) => $q->where('created_at', '>=', $date))
            ->when($this->request->get('end_created_at'), fn($q, $date) => $q->where('created_at', '<=', $date))
            ->select('product_name', 'unit_price', DB::raw('COUNT(*) as units_sold'), DB::raw('SUM(unit_price) as total'))
            ->groupBy('product_name', 'unit_price')
            ->get();
    }

    public function headings(): array
    {
        return ['PRODUCTO', 'P. VENTA CON IVA', 'UNIDADES VENDIDAS', 'EQUIVALENTE $'];
    }

    public function map($row): array
    {
        $equivalente = $row->unit_price * $row->units_sold;
        $this->total += $equivalente;

        return [
            $row->product_name,
            $row->unit_price,
            $row->units_sold,
            $equivalente,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow() + 1;

        $sheet->getStyle('A2:D2')->getFont()->setBold(true);
        $sheet->getStyle('A2:D2')->getFill()->setFillType('solid')->getStartColor()->setRGB('305496');
        $sheet->getStyle('A2:D2')->getFont()->getColor()->setRGB('FFFFFF');

        // Línea de total
        $sheet->setCellValue("C{$lastRow}", 'TOTAL');
        $sheet->setCellValue("D{$lastRow}", $this->total);
        $sheet->getStyle("C{$lastRow}:D{$lastRow}")->getFont()->setBold(true);
        $sheet->getStyle("D3:D{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
        $sheet->getStyle("B3:B{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);

        return [
            'A2:D2' => ['alignment' => ['horizontal' => 'center']],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            'D' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
        ];
    }

    public function startCell(): string
    {
        return 'A2'; // Comienza los encabezados en la segunda fila (dejando la 1 para el título)
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Título del reporte
                $event->sheet->mergeCells('A1:D1');
                $event->sheet->setCellValue('A1', 'REPORTE DE VENTAS POR ESTACIÓN: ' . $this->stationName);

                $event->sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $event->sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
