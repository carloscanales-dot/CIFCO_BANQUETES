<?php

namespace Modules\Ticket\Exports;

use Illuminate\Database\Query\Builder;

/**
 * Filtros compartidos por las hojas del export de tickets.
 *
 * Estaban duplicados en cada hoja y se desincronizaron con la vista: el filtro
 * de feria existe en el frontend pero nunca se aplicó aquí, así que el Excel
 * traía las cortesías de todas las ferias mezcladas.
 */
trait FiltersTickets
{
    protected function applyTicketFilters(Builder $query, $request): Builder
    {
        return $query
            ->when($request->get('fair_id'), function ($q, $fairId) {
                return $q->where('fair_id', $fairId);
            })
            ->when($request->get('product_id'), function ($q, $productId) {
                return $q->where('product_id', $productId);
            })
            ->when($request->get('status'), function ($q, $status) {
                // Estados del filtro frontend -> status_id
                $statusMap = [
                    'D' => 3, // Disponible -> PENDIENTE
                    'C' => 1, // Canjeado   -> APLICADO
                    'A' => 2, // Anulado    -> ANULADO
                ];
                $statusId = $statusMap[$status] ?? null;

                return $statusId ? $q->where('status_id', $statusId) : $q;
            })
            ->when($request->get('uuid'), function ($q, $uuid) {
                return $q->where('uuid', 'like', '%' . $uuid . '%');
            })
            ->when($request->get('start_id'), function ($q, $startId) {
                return $q->where('ticket_id', '>=', $startId);
            })
            ->when($request->get('end_id'), function ($q, $endId) {
                return $q->where('ticket_id', '<=', $endId);
            });
    }
}
