<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Ticket\Models\Station;
use Modules\Caja\Models\Transaction;

class TransactionReprint extends Model
{
    use HasFactory;

    protected $table = 'transaction_reprints';

    protected $fillable = [
        'transaction_id',
        'user_id',
        'station_id',
        'reprinted_at',
    ];

    protected $casts = [
        'reprinted_at' => 'datetime',
    ];

    /**
     * Relación con Transaction
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Relación con User (usuario que hizo la reimpresión)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con Station (estación desde donde se hizo la reimpresión)
     */
    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'id');
    }
}
