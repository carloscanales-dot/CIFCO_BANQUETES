<?php

namespace Modules\Ticket\Models;

use App\Models\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $table = 'tickets';
    protected $primaryKey = 'id';

    protected $fillable = [
        'uuid',
        'status_id',
        'product_id',
        'generated_for',
        'redeem_date',
    ];

    protected $casts = [
        'status_id' => 'integer',
        'redeem_date' => 'datetime',
    ];

    /**
     * Relación: un ticket pertenece a un producto.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación: un ticket pertenece a un status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    /**
     * Scope para filtrar tickets pendientes (status_id = 3).
     */
    public function scopePending($query)
    {
        return $query->where('status_id', 3);
    }

    /**
     * Scope para filtrar tickets aplicados (status_id = 1).
     */
    public function scopeApplied($query)
    {
        return $query->where('status_id', 1);
    }

    /**
     * Scope para filtrar tickets anulados (status_id = 2).
     */
    public function scopeCanceled($query)
    {
        return $query->where('status_id', 2);
    }
}
