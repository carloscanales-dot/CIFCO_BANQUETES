<?php

namespace Modules\Ticket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'product_id';

    protected $fillable = [
        'prefix',
        'product_name',
        'unit_price',
        'cost',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'unit_price' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    /**
     * Scope para obtener solo productos activos.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
    public function stations(): BelongsToMany
    {
        return $this->belongsToMany(Station::class, 'station_products', 'product_id', 'station_id')
            ->withTimestamps();
    }
}
