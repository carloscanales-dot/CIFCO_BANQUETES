<?php

namespace Modules\Ticket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $table = 'tickets';
    protected $primaryKey = 'ticket_id';

    protected $fillable = [
        'uuid',
        'status_id',
        'product_id',
        'redeem_date',
    ];

    protected $casts = [
        'status' => 'integer',
        'redeem_date' => 'datetime',
    ];

    /**
     * Relación: un ticket pertenece a un producto.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    /**
     * Scope para filtrar tickets activos (status = 1).
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope para filtrar tickets canjeados.
     */
    public function scopeRedeemed($query)
    {
        return $query->whereNotNull('redeem_date');
    }
}
