<?php

namespace Modules\Caja\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTerminalOpening extends Model
{
    protected $table = 'payment_terminal_opening';
    protected $primaryKey = 'payment_terminal_opening_id';

    protected $fillable = [
        'station_id',
        'user_id',
        'opening_time',
        'closing_time',
        'initial_balance',
        'final_balance',
    ];

    protected $casts = [
        'opening_time' => 'datetime',
        'closing_time' => 'datetime',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'payment_terminal_opening_id', 'payment_terminal_opening_id');
    }
}