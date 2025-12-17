<?php

namespace Database\Seeders;

use App\Models\User;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'namg72@gmail.com'],     // Buscar por email
            [
                'name' => 'Admin',
                'password' => Hash::make('qwertyuiop')
            ]
        );
        $admin->syncRoles(['admin']);
    }
}
