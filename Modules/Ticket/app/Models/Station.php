<?php

namespace Modules\Ticket\Models;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Station extends Model
{
    protected $table = 'stations';

    /**
     * @var string primary key
     */
    protected $primaryKey = 'station_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'station_name',
        'status',
        'user_id'
    ];


    /**
     * Get the operations for the part.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'station_products', 'station_id', 'product_id')
            ->withTimestamps();
    }

    /**
     * Get the operations for the part.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'station_users', 'station_id', 'user_id')
            ->withTimestamps();
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->user_id = Auth::user()->user_id;
        });
    }
}
