<?php

namespace Modules\Ticket\Traits;

trait SetFilterQuery
{

    /**
     * @param type $query
     * @param type $operator
     * @param type $field
     * @param type $value
     */
    public function setFilter($query, $operator, $field, $value)
    {
        $query->when($operator == 'equal', function ($q) use ($field, $value) {
            return $q->where($field, '=', $value);
        });

        $query->when($operator == 'like', function ($q) use ($field, $value) {
            return $q->where($field, 'like', '%' . $value . '%');
        });

        $query->when($operator == 'greaterThan', function ($q) use ($field, $value) {
            return $q->where($field, '>=', $value);
        });

        $query->when($operator == 'lessThan', function ($q) use ($field, $value) {
            return $q->where($field, '<=', $value);
        });
    }
}
