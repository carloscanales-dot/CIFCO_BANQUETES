<?php

namespace Modules\Caja\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Caja\Models\PrintAgent;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePrintAgent
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Token no proporcionado'
            ], 401);
        }

        // El token es el agent_id después del login
        $agent = PrintAgent::where('agent_id', $token)
            ->where('is_active', true)
            ->first();

        if (!$agent) {
            return response()->json([
                'message' => 'Token inválido o agente inactivo'
            ], 401);
        }

        // Actualizar última vez visto
        $agent->updateLastSeen();

        // Adjuntar agente al request
        $request->merge(['print_agent' => $agent]);

        return $next($request);
    }
}
