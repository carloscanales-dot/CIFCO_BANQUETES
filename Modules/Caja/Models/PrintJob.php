<?php

namespace Modules\Caja\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Modules\Ticket\Models\Station;


class PrintJob extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'print_jobs';
    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'print_job_id';
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'station_id',
        'transaction_id',
        'type',
        'payload',
        'status',
        'error',
        'printed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'printed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id', 'id');
    }
}
