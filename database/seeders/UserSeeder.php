<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles
        $adminRole = Role::findByName('admin', 'web');
        $gestorRole = Role::findByName('gestor', 'web');

        // Create Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if (!$admin->hasRole('admin')) {
            $admin->assignRole($adminRole);
        }

        // Create Gestor user
        $gestor = User::firstOrCreate(
            ['email' => 'gestor@example.com'],
            [
                'name' => 'Gestor',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if (!$gestor->hasRole('gestor')) {
            $gestor->assignRole($gestorRole);
        }

        $this->command->info('Usuários criados com sucesso!');
        $this->command->info('Admin: admin@example.com / password');
        $this->command->info('Gestor: gestor@example.com / password');
    }
}

