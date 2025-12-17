<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $categories = [
            'Comida',
            'Hotel',
            'Taxi',
            'Transporte público',
            'Dietas',
            'Otros',
        ];

        foreach ($categories as $category) {
            TicketCategory::firstOrCreate(
                ['name' => $category],       // criterio de búsqueda
                ['active' => true]           // valores si se crea
            );
        }
    }
}
