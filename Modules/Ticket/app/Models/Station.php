<?php

namespace Modules\Ticket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Station extends Model
{
    protected $table = 'stations';
    protected $primaryKey = 'id';

    protected $fillable = [
        'station_name',
        'status',
        'location_id',
        'fair_id',
    ];


    /**
     * Relación: una estación pertenece a una ubicación.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }


    /**
     * Relación: una estación pertenece a una feria.
     */
    public function fair(): BelongsTo
    {
        return $this->belongsTo(Fair::class, 'fair_id', 'id');
    }

    /**
     * Relación: una estación puede tener varios usuarios asignados (si se activa en el futuro).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'station_user', 'station_id', 'user_id');
    }


    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'station_products', 'station_id', 'product_id')
            ->withTimestamps();
    }
}
