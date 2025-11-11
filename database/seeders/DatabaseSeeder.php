<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            ViewsSeeder::class, // si tuvieras uno específico
            StatusSeeder::class,

            \Modules\Caja\Database\Seeders\CajaDatabaseSeeder::class,
            \Modules\Ticket\Database\Seeders\TicketDatabaseSeeder::class,
        ]);
    }
}
