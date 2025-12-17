<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $supervisores = User::factory(3)->create()->each(function ($user) {
            $user->assignRole('supervisor');
        });

        User::factory(12)->create()->each(function ($empleado) use ($supervisores) {


            $empleado->assignRole('employee');

            // Asignar supervisor aleatoriamente
            $empleado->supervisor_id = $supervisores->random()->id;
            $empleado->save();
        });
    }
}
