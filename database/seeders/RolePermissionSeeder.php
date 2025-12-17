<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Dashboard
            'view-dashboard',

            // Orders
            'view-orders',
            'manage-orders',

            // Restaurants
            'view-restaurants',
            'manage-restaurants',

            // Integrations
            'view-integrations',
            'manage-integrations',

            // Reports
            'view-reports',
            'generate-reports',

            // Users (Admin only)
            'manage-users',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        $clienteRole = Role::create(['name' => 'cliente', 'guard_name' => 'web']);
        $clienteRole->givePermissionTo([
            'view-dashboard',
            'view-orders',
            'view-restaurants',
        ]);

        $gestorRole = Role::create(['name' => 'gestor', 'guard_name' => 'web']);
        $gestorRole->givePermissionTo([
            'view-dashboard',
            'view-orders',
            'manage-orders',
            'view-restaurants',
            'manage-restaurants',
            'view-integrations',
            'manage-integrations',
            'view-reports',
            'generate-reports',
        ]);

        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all());
    }
}
