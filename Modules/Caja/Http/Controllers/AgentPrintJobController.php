<?php

namespace Modules\Caja\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Caja\Models\PrintAgent;
use Modules\Caja\Models\PrintJob;

class AgentPrintJobController extends Controller
{
    /**
     * Login del agente de impresión
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'agent_id' => 'required|string',
            'agent_secret' => 'required|string'
        ]);

        $agent = PrintAgent::where('agent_id', $validated['agent_id'])
            ->where('is_active', true)
            ->first();

        if (!$agent || !$agent->verifySecret($validated['agent_secret'])) {
            return response()->json([
                'message' => 'Credenciales inválidas'
            ], 401);
        }

        // Actualizar última vez visto
        $agent->updateLastSeen();

        // Retornar el agent_id como token (simple pero funcional)
        return response()->json([
            'token' => $agent->agent_id,
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'location' => $agent->location
            ]
        ]);
    }

    /**
     * Obtener trabajos de impresión pendientes
     */
    public function getPending(Request $request)
    {
        $jobs = PrintJob::where('status', 'pending')
            ->with(['user:id,name', 'station:id,station_name'])
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();

        return response()->json([
            'jobs' => $jobs->map(function ($job) {
                return [
                    'id' => $job->print_job_id,
                    'type' => $job->type,
                    'payload' => $job->payload,
                    'created_at' => $job->created_at->toIso8601String(),
                    'transaction_id' => $job->transaction_id,
                    'station_name' => $job->station->station_name ?? 'N/A',
                    'cashier_name' => $job->user->name ?? 'N/A',
                ];
            })
        ]);
    }

    /**
     * Marcar un trabajo como impreso
     */
    public function markPrinted(Request $request, $jobId)
    {
        $job = PrintJob::find($jobId);

        if (!$job) {
            return response()->json([
                'message' => 'Trabajo no encontrado'
            ], 404);
        }

        $job->update([
            'status' => 'printed',
            'printed_at' => now()
        ]);

        return response()->json([
            'message' => 'Trabajo marcado como impreso',
            'job_id' => $jobId
        ]);
    }

    /**
     * Marcar un trabajo como fallido
     */
    public function markFailed(Request $request, $jobId)
    {
        $validated = $request->validate([
            'error' => 'required|string'
        ]);

        $job = PrintJob::find($jobId);

        if (!$job) {
            return response()->json([
                'message' => 'Trabajo no encontrado'
            ], 404);
        }

        $job->update([
            'status' => 'failed',
            'error' => $validated['error']
        ]);

        return response()->json([
            'message' => 'Trabajo marcado como fallido',
            'job_id' => $jobId
        ]);
    }
}
