<?php

namespace Modules\Caja\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Ticket\Models\Employee;
use App\Models\Status;
use App\Models\User;


class Account extends Model
{
    use HasFactory;

    protected $table = 'account';
    protected $primaryKey = 'account_id';

    protected $fillable = [
        'employee_id',
        'account_balance',
        'status_id',
        'opening_date',
        'user_id',
        'closing_date',
    ];

    protected $casts = [
        'opening_date' => 'datetime',
        'closing_date' => 'datetime',
    ];

    /**
     * Relaciones
     */

    // Relación con Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // Relación con Status
    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    // Relación con User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
