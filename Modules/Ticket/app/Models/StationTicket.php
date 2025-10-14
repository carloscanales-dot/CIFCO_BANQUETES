<?php

namespace Modules\Ticket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StationTicket extends Model
{
    protected $table = 'station_tickets';

    /**
     * @var string primary key
     */
    protected $primaryKey = 'station_ticket_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'ticket_id',
        'station_user_id'
    ];


    /**
     * Get the user.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'ticket_id');
    }

    /**
     * Get the product.
     */
    public function stationUser(): BelongsTo
    {
        return $this->belongsTo(StationUser::class, 'station_user_id', 'station_user_id');
    }
}
