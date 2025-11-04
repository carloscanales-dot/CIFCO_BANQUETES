<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Ticket\Models\Station;

class Printer extends Model
{
    use HasFactory;

    protected $table = 'printer';
    protected $primaryKey = 'printer_id';

    protected $fillable = [
        'printer_name',
        'status',
        'station_id',
        'ip_adress',
    ];

    protected $casts = [
        'status' => 'boolean', // ✅ asegura que siempre se trate como boolean
    ];

    /**
     * Relaciones
     */
    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    /**
     * Scope para filtrar activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
