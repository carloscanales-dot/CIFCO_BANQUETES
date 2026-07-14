<?php

namespace Modules\Caja\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class PrintAgent extends Model
{
    protected $fillable = [
        'agent_id',
        'agent_secret',
        'name',
        'location',
        'is_active',
        'last_seen_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    protected $hidden = [
        'agent_secret',
    ];

    /**
     * Verifica si el secret proporcionado coincide
     */
    public function verifySecret(string $secret): bool
    {
        return Hash::check($secret, $this->agent_secret);
    }

    /**
     * Actualiza el timestamp de última conexión
     */
    public function updateLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }
}
