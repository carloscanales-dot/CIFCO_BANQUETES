<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
public function share(Request $request): array
{
    $flash = [
        'type' => null,
        'message' => null,
    ];
    if ($request->session()->has('success')) {
        $flash['type'] = 'success';
        $flash['message'] = $request->session()->get('success');
    } elseif ($request->session()->has('error')) {
        $flash['type'] = 'error';
        $flash['message'] = $request->session()->get('error');
    } elseif ($request->session()->has('warning')) {
        $flash['type'] = 'warning';
        $flash['message'] = $request->session()->get('warning');
    } elseif ($request->session()->has('info')) {
        $flash['type'] = 'info';
        $flash['message'] = $request->session()->get('info');
    }

    $user = $request->user();

    // Normalizar roles y permisos a arrays y calcular is_admin
    $roles = [];
    $permissions = [];
    $isAdmin = false;

    // Estación asignada (por defecto null)
    $stationId = null;
    $station = null;

    if ($user) {
        // roles / permissions
        $roles = $user->getRoleNames()->toArray();
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();

        foreach ($roles as $r) {
            if (stripos($r, 'admin') !== false || strcasecmp($r, 'administrador') === 0) {
                $isAdmin = true;
                break;
            }
        }

        // 1) Si tienes una relación stations() en el User model, obtener la primera
        if (method_exists($user, 'stations')) {
            $firstStation = $user->stations()->first();
            if ($firstStation) {
                $stationId = $firstStation->id;
                // Compartir un objeto mínimo con id y nombre para uso en frontend
                $station = [
                    'id' => $firstStation->id,
                    'name' => $firstStation->name ?? ($firstStation->station_name ?? null),
                ];
            }
        }

        // 2) Si guardas station_id directo en users table (fallback)
        if (empty($stationId) && isset($user->station_id)) {
            $stationId = $user->station_id;
            $station = ['id' => $stationId, 'name' => null];
        }
    }

    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                // enviar arrays puros (no Collections)
                'roles' => $roles,
                'permissions' => $permissions,
                // opcional: compartir estaciones completas si quieres
                // 'stations' => $user->stations ? $user->stations->toArray() : [],
            ] : null,
        ],
        // flag boolean de uso directo en frontend
        'is_admin' => $isAdmin,

        // estación actual: id y nombre (puedes leer page.props.station_id)
        'station_id' => $stationId,
        'station' => $station,

        'flash' => $flash,
    ]);
}


}
