<?php

namespace Modules\Caja\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Caja\Models\PrintAgent;

class PrintAgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PrintAgent::updateOrCreate(
            ['agent_id' => 'LOCAL_AGENT_001'],
            [
                'agent_secret' => Hash::make('secret123'),
                'name' => 'Agente Local Principal',
                'location' => 'Servidor Local',
                'is_active' => true,
            ]
        );

        $this->command->info('✓ Agente de impresión creado: LOCAL_AGENT_001');
        $this->command->info('  Secret: secret123');
    }
}
