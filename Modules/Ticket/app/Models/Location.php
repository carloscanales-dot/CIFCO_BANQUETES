<?php

namespace Modules\Ticket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $table = 'location';
    protected $primaryKey = 'id';

    protected $fillable = [
        'location_name',
    ];

    public function stations(): HasMany
    {
        return $this->hasMany(Station::class, 'location_id', 'id');
    }
}