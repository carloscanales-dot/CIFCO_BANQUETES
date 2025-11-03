<?php

namespace Modules\Ticket\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StationUserController extends Controller
{
    public function store(Request $request, $station_id)
    {
        $userIds = $request->input('user_ids', []);

        // Elimina usuarios anteriores de la estación
        DB::table('station_users')->where('station_id', $station_id)->delete();

        // Inserta los nuevos
        foreach ($userIds as $userId) {
            DB::table('station_users')->insert([
                'station_id' => $station_id,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Usuarios asignados correctamente']);
    }
    public function assignedUsers($station_id)
    {
        $users = DB::table('station_users')
            ->join('users', 'station_users.user_id', '=', 'users.id')
            ->where('station_users.station_id', $station_id)
            ->select('users.id', 'users.name')
            ->get();

        return response()->json($users);
    }
}
