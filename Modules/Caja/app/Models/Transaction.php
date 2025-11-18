<?php

namespace Modules\Caja\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Status;
use App\Models\User;
use Modules\Ticket\Models\Station;
use Modules\Caja\App\Models\PaymentTerminalOpening;
use Modules\Caja\Models\TransactionType;
use Modules\Caja\Models\PaymentMethod;
use Modules\Ticket\Models\Employee;

class Transaction extends Model
{
    protected $table = 'transactions';
    protected $primaryKey = 'id';

    protected $fillable = [
        'station_id',
        'user_id',
        'amount',
        'transaction_date',
        'transaction_type_id',
        'status_id',
        'payment_method_id',
        'payment_terminal_opening_id',
        'is_refunded',
        'employee_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'is_refunded' => 'boolean',
    ];

    /** Estación */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id', 'id');
    }

    /** Usuario */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /** Tipo de Transacción */
    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class, 'transaction_type_id', 'transaction_type_id');
    }

    /** Estado (usa tabla status real) */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id', 'id');
    }

    /** Método de pago */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }

    /** Apertura de terminal */
    public function opening(): BelongsTo
    {
        return $this->belongsTo(PaymentTerminalOpening::class, 'payment_terminal_opening_id', 'id');
    }

    /** Empleado */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function details()
    {
        return $this->hasMany(TransactionDetail::class, 'transaction_id', 'id');
    }
}
