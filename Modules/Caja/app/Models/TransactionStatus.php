<?php

namespace Modules\Caja\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionStatus extends Model
{
    protected $table = 'transaction_status';
    protected $primaryKey = 'transaction_status_id';

    protected $fillable = [
        'name',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'transaction_status_id', 'transaction_status_id');
    }
}