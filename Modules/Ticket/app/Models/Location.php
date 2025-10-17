<?php

namespace Modules\Ticket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $table = 'location';
    protected $primaryKey = 'location_id';

    protected $fillable = [
        'location_name',
    ];

    public function fair()
    {
        return $this->belongsTo(Fair::class, 'fair_id');
    }

    /**
     * Relación: una ubicación puede tener muchas estaciones.
     */

    public function stations(): HasMany
    {
        return $this->hasMany(Station::class, 'location_id', 'location_id');
    }
}
