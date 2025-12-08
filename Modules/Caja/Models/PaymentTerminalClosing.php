<?php

namespace Modules\Caja\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Modules\Caja\Models\PaymentTerminalOpening;
use Modules\Caja\Models\PaymentTerminal;

class PaymentTerminalClosing extends Model
{
    protected $table = 'payment_terminal_closing';

    protected $primaryKey = 'payment_terminal_closing_id';

    protected $fillable = [
        'payment_terminal_opening_id',
        'payment_terminal_id',
        'user_id',
        'closing_date',
        'expected_amount',
        'real_amount',
        'pos_real_amount', 
        'closing_balance',
        'notes',
    ];

    protected $casts = [
        'closing_date'     => 'datetime',
        'expected_amount'  => 'decimal:2',
        'real_amount'      => 'decimal:2',
        'pos_real_amount'  => 'decimal:2',
        'closing_balance'  => 'decimal:2',
    ];

    /**
     * Apertura asociada
     */
    public function opening(): BelongsTo
    {
        return $this->belongsTo(
            PaymentTerminalOpening::class,
            'payment_terminal_opening_id',
            'id'
        );
    }

    /**
     * Terminal asociada
     */
    public function terminal(): BelongsTo
    {
        return $this->belongsTo(
            PaymentTerminal::class,
            'payment_terminal_id',
            'id'
        );
    }

    /**
     * Cajero que realizó el cierre
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Diferencia (real - esperado)
     */
    public function getDifferenceAttribute(): float
    {
        return round((float)$this->real_amount - (float)$this->expected_amount, 2);
    }

    /**
     * Etiqueta del estado del cierre
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->difference === 0.00) {
            return 'Cuadre Exacto';
        }

        return $this->difference > 0
            ? 'Sobrante'
            : 'Faltante';
    }
}
