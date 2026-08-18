<?php

namespace Modules\Ticket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class Fair extends Model
{
    protected $table = 'fairs';
    protected $primaryKey = 'id';

    /** Estados de la feria */
    public const STATUS_SCHEDULED = 1; // Programada
    public const STATUS_OPEN      = 2; // Abierta (activa)
    public const STATUS_CLOSED    = 3; // Cerrada

    protected $fillable = [
        'fair_name',
        'start_date',
        'end_date',
        'status',
    ];

    /**
     * Scope: solo ferias abiertas (activas). Puede haber más de una a la vez.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Devuelve el id de la feria por defecto para el dashboard:
     * la feria abierta más reciente y, si no hay ninguna abierta,
     * la feria más reciente registrada.
     */
    public static function defaultDashboardId(): ?int
    {
        return static::query()->open()->orderByDesc('start_date')->value('id')
            ?? static::query()->orderByDesc('start_date')->value('id');
    }

    /**
     * Ferias abiertas para poblar un selector (id + nombre), más recientes primero.
     */
    public static function openOptions(): Collection
    {
        return static::query()->open()
            ->orderByDesc('start_date')
            ->get(['id', 'fair_name', 'start_date', 'end_date', 'status']);
    }

    /**
     * Estaciones de las ferias abiertas, etiquetadas con el nombre de su feria.
     * Útil para dropdowns donde los nombres de stand se repiten entre ferias
     * (p. ej. tras clonar). Devuelve id, station_name, fair_id, fair_name y label.
     *
     * @param int|null $fairId Si se indica, limita a esa feria (debe estar abierta).
     */
    public static function openStationOptions(?int $fairId = null): Collection
    {
        return DB::table('stations')
            ->join('fairs', 'stations.fair_id', '=', 'fairs.id')
            ->where('fairs.status', self::STATUS_OPEN)
            ->when($fairId, fn($q) => $q->where('fairs.id', $fairId))
            ->orderBy('fairs.fair_name')
            ->orderBy('stations.station_name')
            ->select(
                'stations.id',
                'stations.station_name',
                'fairs.id as fair_id',
                'fairs.fair_name',
                DB::raw("CONCAT(stations.station_name, ' — ', fairs.fair_name) as label")
            )
            ->get();
    }

    /**
     * ¿La estación pertenece a una feria abierta? (para validaciones).
     */
    public static function stationBelongsToOpenFair(int $stationId): bool
    {
        return DB::table('stations')
            ->join('fairs', 'stations.fair_id', '=', 'fairs.id')
            ->where('stations.id', $stationId)
            ->where('fairs.status', self::STATUS_OPEN)
            ->exists();
    }

    public function locations()
    {
        return $this->hasMany(Location::class, 'fair_id');
    }
        public function stations()
    {
        return $this->hasMany(Station::class, 'fair_id');
    }
}
