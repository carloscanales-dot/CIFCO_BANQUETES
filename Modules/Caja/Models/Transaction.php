<?php

namespace Modules\Caja\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Status;
use Modules\Ticket\Models\Station;

class Transaction extends Model
{
    protected $fillable = ['status_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id', 'id');
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(\App\Models\PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }
}
