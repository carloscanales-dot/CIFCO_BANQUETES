<?php

namespace Modules\Caja\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ticket\Models\Station;


class PaymentTerminal extends Model
{
    use HasFactory;

    // Si tu migración creó la tabla 'payment_terminal' en singular:
    protected $table = 'payment_terminal';

    protected $fillable = [
        'terminal_name',
        'status',
        'station_id',
        'user_id',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    // Relaciones
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
