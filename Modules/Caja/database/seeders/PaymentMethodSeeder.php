<?php

namespace Modules\Caja\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('payment_method')->insert([
            ['payment_method' => 'EFECTIVO', 'created_at' => now(), 'updated_at' => now()],
            ['payment_method' => 'TARJETA', 'created_at' => now(), 'updated_at' => now()],
            ['payment_method' => 'CHIVO WALLET', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
