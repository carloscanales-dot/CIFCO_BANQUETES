<?php

namespace Modules\Ticket\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employee';
    protected $primaryKey = 'employee_id';

    protected $fillable = [
        'employee_name',
        'employee_area',
        'status',
    ];
}
