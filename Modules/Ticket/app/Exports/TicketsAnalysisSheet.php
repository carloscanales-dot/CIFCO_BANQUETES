<?php

namespace Modules\Ticket\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketsAnalysisSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
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
        // Mismos filtros que la hoja de detalle, incluida la feria
        $query = $this->applyTicketFilters(DB::table('v_tickets'), $this->request);

        // Resumen general
        $totalTickets = $query->count();
        $pendientes = (clone $query)->where('status_id', 3)->count(); // PENDIENTE
        $aplicados = (clone $query)->where('status_id', 1)->count(); // APLICADO
        $anulados = (clone $query)->where('status_id', 2)->count(); // ANULADO

        // Valor total y promedio
        $totalValor = (clone $query)->sum('unit_price');
        $promedioValor = $totalTickets > 0 ? round($totalValor / $totalTickets, 2) : 0;

        // Resumen por producto
        $porProducto = (clone $query)
            ->select(
                'product_name',
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(unit_price) as total_valor'),
                DB::raw('SUM(CASE WHEN status_id = 3 THEN 1 ELSE 0 END) as pendientes'),
                DB::raw('SUM(CASE WHEN status_id = 1 THEN 1 ELSE 0 END) as aplicados')
            )
            ->groupBy('product_name')
            ->get();

        // Construir la colección de datos para el análisis
        $analysis = collect();

        // Resumen General
        $analysis->push(['RESUMEN GENERAL', '', '', '', '']);
        $analysis->push(['Métrica', 'Valor', '', '', '']);
        $analysis->push(['Total de Tickets', $totalTickets, '', '', '']);
        $analysis->push(['Tickets Pendientes', $pendientes, '', '', '']);
        $analysis->push(['Tickets Aplicados', $aplicados, '', '', '']);
        $analysis->push(['Tickets Anulados', $anulados, '', '', '']);
        $analysis->push(['Valor Total', '$' . number_format($totalValor, 2), '', '', '']);
        $analysis->push(['Valor Promedio', '$' . number_format($promedioValor, 2), '', '', '']);
        $analysis->push(['', '', '', '', '']);

        // Resumen por Producto
        $analysis->push(['RESUMEN POR PRODUCTO', '', '', '', '']);
        $analysis->push(['Producto', 'Cantidad', 'Valor Total', 'Pendientes', 'Aplicados']);

        foreach ($porProducto as $producto) {
            $analysis->push([
                $producto->product_name,
                $producto->cantidad,
                '$' . number_format($producto->total_valor, 2),
                $producto->pendientes,
                $producto->aplicados,
            ]);
        }

        $analysis->push(['', '', '', '', '']);

        // Porcentajes
        $analysis->push(['ANÁLISIS PORCENTUAL', '', '', '', '']);
        $analysis->push(['Concepto', 'Porcentaje', '', '', '']);
        if ($totalTickets > 0) {
            $analysis->push(['% Pendientes', round(($pendientes / $totalTickets) * 100, 2) . '%', '', '', '']);
            $analysis->push(['% Aplicados', round(($aplicados / $totalTickets) * 100, 2) . '%', '', '', '']);
            $analysis->push(['% Anulados', round(($anulados / $totalTickets) * 100, 2) . '%', '', '', '']);
        }

        return $analysis;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Análisis y Resumen';
    }

    /**
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
            10 => ['font' => ['bold' => true, 'size' => 14]],
            11 => ['font' => ['bold' => true]],
        ];
    }
}
