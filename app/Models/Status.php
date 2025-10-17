<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Caja\Models\Transaction;

class Status extends Model
{
    use HasFactory;

    protected $table = 'status';
    protected $primaryKey = 'status_id';

    protected $fillable = [
        'status',
    ];

    /**
     * Relaciones
     */

    // Relación con cuentas (Account)
    public function accounts()
    {
        return $this->hasMany(Account::class, 'status_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'status_id');
    }
}
