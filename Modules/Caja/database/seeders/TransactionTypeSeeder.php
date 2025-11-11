<?php

namespace Modules\Caja\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('transaction_type')->insert([
            [
                'transaction_type' => 'VENTA',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'transaction_type' => 'VENTA EMPLEADO',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'transaction_type' => 'CORTESIAS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
