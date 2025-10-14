<?php

namespace Modules\Ticket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $table = 'products';

    /**
     * @var string primary key
     */
    protected $primaryKey = 'product_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'prefix',
        'product_name',
        'unit_price',
        'status',
    ];


    /**
     * Get the operations for the part.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'product_id');
    }

    /**
     * Get the operations for the part.
     */
    public function stations(): BelongsToMany
    {
        return $this->belongsToMany(Station::class, 'station_products');
    }
}
