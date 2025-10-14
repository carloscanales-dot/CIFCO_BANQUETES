<?php

namespace Modules\Ticket\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class ExistsUserRule implements ValidationRule
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
        if ($this->existsUserInStation($value)) {
            $fail('El usuario seleccionado, ya se encuentra asignado a la estacion');
        }
    }

    protected function existsUserInStation($user_id)
    {
        return DB::table('station_users')
            ->where([
                'user_id' => $user_id,
                'station_id' => $this->station_id,
            ])
            ->first();
    }
}
