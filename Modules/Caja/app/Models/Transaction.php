<?php

namespace Modules\Caja\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Account;

class Transaction extends Model
{
    protected $table = 'transactions';
    protected $primaryKey = 'transaction_id';

    protected $fillable = [
        'station_id',
        'user_id',
        'amount',
        'transaction_date',
        'transaction_type_id',
        'transaction_status_id',
        'payment_method_id',
        'payment_terminal_opening_id',
        'is_refunded',
        'account_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'is_refunded' => 'boolean',
    ];

    /**
     * Relación: una transacción pertenece a una estación.
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(\Modules\Ticket\Models\Station::class, 'station_id', 'station_id');
    }

    /**
     * Relación: una transacción es realizada por un usuario.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'user_id');
    }

    /**
     * Relación: una transacción tiene un tipo.
     */
    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class, 'transaction_type_id', 'transaction_type_id');
    }

    /**
     * Relación: una transacción tiene un estado.
     */
    public function transactionStatus(): BelongsTo
    {
        return $this->belongsTo(TransactionStatus::class, 'transaction_status_id', 'transaction_status_id');
    }

    /**
     * Relación: una transacción tiene un método de pago.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }

    /**
     * Relación: una transacción pertenece a una apertura de terminal de pago.
     */
    public function paymentTerminalOpening(): BelongsTo
    {
        return $this->belongsTo(PaymentTerminalOpening::class, 'payment_terminal_opening_id', 'payment_terminal_opening_id');
    }


    public function account(): HasMany
    {
        return $this->HasMany(Account::class, 'account_id', 'account_id');
    }
}
