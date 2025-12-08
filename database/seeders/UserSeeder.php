<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure the main admin user exists
        $user = User::firstOrCreate(
            ['email' => 'carlos.canales@cifco.gob.sv'],
            [
                'name' => 'Carlos Canales',
                'password' => Hash::make('Arrup32020*'),
                'status' => true,
            ]
        );

        $user->assignRole('Administrador');

        // Ensure the second user exists and is an administrator
        $alejandra = User::firstOrCreate(
            ['email' => 'alejandra.portillo@cifco.gob.sv'],
            [
                'name' => 'Alejandra Portillo',
                'password' => Hash::make('C1fc0*.2025'),
                'status' => true,
            ]
        );

        $alejandra->assignRole('Administrador');
    }
}
