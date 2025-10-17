<?php

namespace Modules\Caja\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionType extends Model
{
    protected $table = 'transaction_type';
    protected $primaryKey = 'transaction_type_id';

    protected $fillable = [
        'name',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'transaction_type_id', 'transaction_type_id');
    }
}