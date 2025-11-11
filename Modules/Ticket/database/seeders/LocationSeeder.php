<?php

namespace Modules\Ticket\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('location')->insert([
            ['location_name' => 'FACHADA', 'created_at' => now(), 'updated_at' => now()],
            ['location_name' => 'VAGON DONKEY KONG', 'created_at' => now(), 'updated_at' => now()],
            ['location_name' => 'DONKEY KONG', 'created_at' => now(), 'updated_at' => now()],
            ['location_name' => 'IMPRENTA', 'created_at' => now(), 'updated_at' => now()],
            ['location_name' => 'GRAN CENTRAL', 'created_at' => now(), 'updated_at' => now()],
            ['location_name' => 'LOTERIA', 'created_at' => now(), 'updated_at' => now()],
            ['location_name' => 'TREN', 'created_at' => now(), 'updated_at' => now()],
            ['location_name' => 'PASILLO HACIA ROTONDA', 'created_at' => now(), 'updated_at' => now()],
            ['location_name' => 'CASA BLANCA', 'created_at' => now(), 'updated_at' => now()],
            ['location_name' => 'CANCHA', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
