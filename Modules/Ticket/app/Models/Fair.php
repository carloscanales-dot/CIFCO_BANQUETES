<?php

namespace Modules\Ticket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Fair extends Model
{
    protected $table = 'fairs';

    /**
     * @var string primary key
     */
    protected $primaryKey = 'fair_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'fair_name',
        'start_date',
        'end_date',
        'status',
        'user_id'
    ];

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->user_id = Auth::user()->user_id;
        });
    }
}
