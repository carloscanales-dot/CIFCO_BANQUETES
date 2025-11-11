<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('status')->insert([
            ['status' => 'APLICADO', 'created_at' => now(), 'updated_at' => now()],
            ['status' => 'ANULADO', 'created_at' => now(), 'updated_at' => now()],
            ['status' => 'PENDIENTE', 'created_at' => now(), 'updated_at' => now()],
            ['status' => 'DEVOLUCION', 'created_at' => now(), 'updated_at' => now()],
            ['status' => 'ABIERTO', 'created_at' => now(), 'updated_at' => now()],
            ['status' => 'CERRADO', 'created_at' => now(), 'updated_at' => now()],
            ['status' => 'PRE-CIERRE', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
