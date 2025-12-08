<?php

namespace Modules\Caja\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ticket\Models\Station;

class PaymentTerminal extends Model
{
    use HasFactory;

    protected $table = 'payment_terminal';

    protected $fillable = [
        'terminal_name',
        'status_id',
        'station_id',
        'user_id',
    ];

    protected $casts = [
        'status_id' => 'integer',
    ];

    /* ----------------------------------
       Relaciones
    ----------------------------------- */

    // Estado actual de la terminal (Abierta / Cerrada)
    public function status()
    {
        return $this->belongsTo(\App\Models\Status::class, 'status_id', 'id');
    }

    // Estación asociada
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    // Usuario asignado (cajero)
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    // Aperturas asociadas
    public function openings()
    {
        return $this->hasMany(
            \Modules\Caja\Models\PaymentTerminalOpening::class,
            'payment_terminal_id'
        );
    }
}
