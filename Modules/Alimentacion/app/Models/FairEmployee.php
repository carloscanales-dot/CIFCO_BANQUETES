<?php

namespace Modules\Alimentacion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
class FairEmployee extends Model
{
    use HasFactory;

    protected $primaryKey = 'employee_id';

    protected $fillable = ['name', 'email', 'role', 'qr_code'];

    public function meals(): BelongsToMany
    {
        return $this->belongsToMany(MealType::class, 'employee_meals', 'employee_id', 'meal_type_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(MealClaim::class, 'employee_id');
    }
}
