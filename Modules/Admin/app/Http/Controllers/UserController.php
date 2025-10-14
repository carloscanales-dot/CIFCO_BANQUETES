<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function list()
    {
        $users = DB::table('users')
            ->select('user_id', 'name')
            ->get();

        return response()->json(['users' => $users]);
    }
}
