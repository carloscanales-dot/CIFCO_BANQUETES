<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Ticket\Models\Product;
use Modules\Caja\Models\Transaction;



class AccountDetail extends Model
{
    use HasFactory;

    protected $table = 'account_detail';

    protected $fillable = [
        'account_id',
        'product_id',
        'transaction_id',
        'quantity',
        'transaction_date',
    ];

    /**
     * Relaciones
     */

    // Relación con Account
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    // Relación con Product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

     public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}
