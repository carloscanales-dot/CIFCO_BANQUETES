<?php

namespace Modules\Alimentacion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MealType extends Model
{
    use HasFactory;

    protected $primaryKey = 'meal_type_id';

    protected $fillable = ['name'];

    public function fairemployee(): BelongsToMany
    {
        return $this->belongsToMany(FairEmployee::class, 'employee_meals', 'meal_type_id', 'employee_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(MealClaim::class, 'meal_type_id');
    }
}
