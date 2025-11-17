<?php

namespace Modules\Caja\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentTerminalOpening extends Model
{
    protected $table = 'payment_terminal_opening';

    protected $fillable = [
        'payment_terminal_id',
        'user_id',
        'opening_date',
        'opening_amount',
    ];

    protected $casts = [
        'opening_date'   => 'datetime',
        'opening_amount' => 'decimal:2',
        // si querés, podés castear otros campos aquí
    ];

    /**
     * Relación: apertura pertenece a una terminal
     */
    public function terminal()
    {
        return $this->belongsTo(
            \Modules\Caja\App\Models\PaymentTerminal::class,
            'payment_terminal_id',
            'id'
        );
    }


    /**
     * Relación: apertura fue creada por un usuario (cajero)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Relación: cierre asociado (si existe)
     */
    public function closing(): HasOne
    {
        return $this->hasOne(PaymentTerminalClosing::class, 'payment_terminal_opening_id');
    }

    /**
     * Helper: indica si la apertura ya tiene un cierre asociado
     */
    public function getIsClosedAttribute(): bool
    {
        return (bool) $this->closing()->exists();
    }

    /**
     * Helper: monto actual en caja (puedes ajustarlo para sumar ventas)
     * Por defecto retorna el opening_amount; puedes extenderlo para sumar transacciones.
     */
    public function getCurrentAmountAttribute(): float
    {
        // Si ya hay cierre, devolver el monto real del cierre
        if ($this->closing) {
            return (float) $this->closing->real_amount;
        }

        // Por ahora devolvemos el monto de apertura
        return (float) $this->opening_amount;
    }
}
