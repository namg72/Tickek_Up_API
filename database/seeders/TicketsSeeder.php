<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketCategory;
use Carbon\Carbon;

class TicketsSeeder extends Seeder
{
    public function run(): void
    {
        // Traer empleados del sistema
        $employees = User::role('employee')->get();

        // Traer todas las categorías creadas
        $categories = TicketCategory::all();

        // Estados posibles
        $statuses = ['pending', 'approved', 'rejected'];

        // Crear entre 30 y 40 tickets aleatorios
        $total = rand(30, 40);

        for ($i = 0; $i < $total; $i++) {

            // Seleccionamos un empleado al azar
            $employee = $employees->random();

            // Obtenemos el supervisor del empleado
            $supervisorId = $employee->supervisor_id;

            $amount = fake()->randomFloat(2, 5, 600); // entre 5 y 600 €
            $iva    = round($amount * 0.21, 2);
            $total_amount  = $amount + $iva;


            Ticket::create([
                'user_id' => $employee->id,
                'supervisor_id' => $supervisorId,
                'category_id' => $categories->random()->id,
                'title' => fake()->sentence(3),
                'description' => fake()->text(100),
                'amount' => $amount,
                'iva_amount'   => $iva,
                'total_amount'  => $total_amount,
                'uri' => null,
                'status' => $statuses[array_rand($statuses)],
                'needs_revision' => fake()->boolean(20),       // 20% de tickets necesitan revisión
                'finalized_by_admin' => fake()->boolean(10),  // 10% finalizados por admin
                'reviewed_by' => null, // lo puedes ajustar luego
                'created_at' => Carbon::now()->subDays(rand(1, 400)), // fechas aleatorias de 1 a 400 días atrás
            ]);
        }
    }
}
