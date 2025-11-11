<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Caja\Models\Transaction;
use Modules\Caja\App\Models\PaymentTerminal;

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

    // Relación con transacciones
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'status_id');
    }

    // Relación con terminales de pago
    public function paymentTerminals()
    {
        return $this->hasMany(PaymentTerminal::class, 'status_id');
    }
}
