<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PrintAgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('print_agents')->insert([
            [
                'agent_id' => 'test-agent',
                'name' => 'Agente de Prueba',
                'location' => 'Local',
                'agent_secret' => Hash::make('secret123'),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
