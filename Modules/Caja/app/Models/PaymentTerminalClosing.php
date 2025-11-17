<?php

namespace Modules\Caja\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTerminalClosing extends Model
{
    protected $table = 'payment_terminal_closing';

    protected $primaryKey = 'payment_terminal_closing_id'; // según tu migración

    protected $fillable = [
        'payment_terminal_opening_id',
        'payment_terminal_id',
        'user_id',
        'closing_date',
        'expected_amount',
        'real_amount',
        'closing_balance',
        'notes',
    ];

    protected $casts = [
        'closing_date'     => 'datetime',
        'expected_amount'  => 'decimal:2',
        'real_amount'      => 'decimal:2',
        'closing_balance'  => 'decimal:2',
    ];

    /**
     * Relación: cierre pertenece a una apertura
     */
    public function opening(): BelongsTo
    {
        return $this->belongsTo(PaymentTerminalOpening::class, 'payment_terminal_opening_id');
    }

    /**
     * Relación: cierre pertenece a una terminal
     */
    public function terminal(): BelongsTo
    {
        return $this->belongsTo(PaymentTerminal::class, 'payment_terminal_id');
    }

    /**
     * Relación: cierre realizado por un usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Helper: diferencia entre monto esperado y real
     */
    public function getDifferenceAttribute(): float
    {
        return (float) ($this->real_amount - $this->expected_amount);
    }

    /**
     * Helper: estado del cierre (por si querés mostrar en tabla)
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->difference == 0
            ? 'Cuadre Exacto'
            : ($this->difference > 0 ? 'Sobrante' : 'Faltante');
    }
}
