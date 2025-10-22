<?php

namespace Modules\Ticket\Models;

use Illuminate\Database\Eloquent\Model;

class Fair extends Model
{
    protected $table = 'fairs';
    protected $primaryKey = 'id';

    protected $fillable = [
        'fair_name',
        'start_date',
        'end_date',
        'status',
    ];

    public function locations()
    {
        return $this->hasMany(Location::class, 'fair_id');
    }
}
