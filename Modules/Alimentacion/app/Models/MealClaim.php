<?php

namespace Modules\Alimentacion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealClaim extends Model
{
    use HasFactory;

    protected $primaryKey = 'claim_id';

    protected $fillable = [
        'employee_id',
        'meal_type_id',
        'claimed_at',
        'station_id',
        'is_valid',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
        'is_valid' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(FairEmployee::class, 'employee_id');
    }

    public function mealType(): BelongsTo
    {
        return $this->belongsTo(MealType::class, 'meal_type_id');
    }
}
