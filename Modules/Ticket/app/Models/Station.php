<?php

namespace Modules\Ticket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Station extends Model
{
    protected $table = 'stations';
    protected $primaryKey = 'station_id';

    protected $fillable = [
        'station_name',
        'status',
        'location_id',
        'fair_id',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Relación: una estación pertenece a una ubicación.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    /**
     * Relación: una estación pertenece a una feria.
     */
    public function fair(): BelongsTo
    {
        return $this->belongsTo(Fair::class, 'fair_id', 'fair_id');
    }

    /**
     * Relación: una estación puede tener varios usuarios asignados (si se activa en el futuro).
     */
    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class, 'station_id', 'station_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'station_products', 'station_id', 'product_id')
            ->withTimestamps();
    }
}
