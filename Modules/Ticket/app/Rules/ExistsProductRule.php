<?php

namespace Modules\Ticket\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class ExistsProductRule implements ValidationRule
{
    protected $station_id;

    public function __construct($station_id)
    {
        $this->station_id = $station_id;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->existsProductInStation($value)) {
            $fail('El producto seleccionado, ya se encuentra asignado a la estacion');
        }
    }

    protected function existsProductInStation($product_id)
    {
        return DB::table('station_products')
            ->where([
                'product_id' => $product_id,
                'station_id' => $this->station_id,
                'status' => 'Activo',
            ])
            ->first();
    }
}
