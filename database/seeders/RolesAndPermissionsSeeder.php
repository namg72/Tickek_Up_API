<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //limpieza de cache de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'ticket.create',
            'ticket.view',
            'ticket.update',
            'ticket.review',
            'ticket.override',
            'category.manage',
            'user.manage',
            'role.manage',

        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        };

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $supervisor = Role::firstOrCreate(['name' => 'supervisor']);
        $employee = Role::firstOrCreate(['name' => 'employee']);

        //asignamos permisos
        $admin->syncPermissions(Permission::all());
        $supervisor->syncPermissions([
            'ticket.view',
            'ticket.review',
            'ticket.update',
        ]);
        $employee->syncPermissions([
            'ticket.create',
            'ticket.view',
            'ticket.update',
        ]);
    }
}
