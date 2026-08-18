<?php

namespace Modules\Caja\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

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
    ];

    /**
     * Terminal a la que pertenece la apertura
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
     * Usuario (cajero) que hizo la apertura
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Cierre asociado a esta apertura
     */
    public function closing(): HasOne
    {
        return $this->hasOne(
            PaymentTerminalClosing::class,
            'payment_terminal_opening_id',
            'id'
        );
    }

    /**
     * Indica si la apertura ya fue cerrada
     */
    public function getIsClosedAttribute(): bool
    {
        return $this->closing()->exists();
    }

    /**
     * Monto actual en caja (puede extenderse para incluir ventas)
     */
    public function getCurrentAmountAttribute(): float
    {
        if ($this->closing) {
            return (float) $this->closing->real_amount;
        }

        return (float) $this->opening_amount;
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(\Modules\Caja\Models\Transaction::class, 'payment_terminal_opening_id', 'id');
    }

    public function getTotalTransactedAttribute()
    {
        if ($this->relationLoaded('transactions')) {
            return (float) $this->transactions
                ->where('transaction_type_id', 1)
                ->sum('amount');
        }

        return (float) $this->transactions()
            ->where('transaction_type_id', 1)
            ->sum('amount');
    }

    /**
     * Total por método de pago. Las ventas MIXTAS (método 4) se reparten: su
     * parte en efectivo suma a EFECTIVO y su parte en tarjeta a TARJETA.
     */
    public function getTotalCashAttribute()
    {
        return (float) $this->transactions()
                ->where('transaction_type_id', 1)
                ->where('payment_method_id', 1)
                ->sum('amount')
            + (float) $this->transactions()
                ->where('transaction_type_id', 1)
                ->where('payment_method_id', 4)
                ->sum('amount_cash');
    }

    public function getTotalCardAttribute()
    {
        return (float) $this->transactions()
                ->where('transaction_type_id', 1)
                ->where('payment_method_id', 2)
                ->sum('amount')
            + (float) $this->transactions()
                ->where('transaction_type_id', 1)
                ->where('payment_method_id', 4)
                ->sum('amount_card');
    }

    public function getTotalChivoAttribute()
    {
        return $this->transactions()
            ->where('payment_method_id', 3)
            ->where('transaction_type_id', 1)
            ->sum('amount');
    }
}
