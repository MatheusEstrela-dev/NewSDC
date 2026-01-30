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
        // Primeiro, criar roles e permissões
        $this->call(RolesAndPermissionsSeeder::class);

        // Criar admin principal do sistema
        $admin = \App\Models\User::updateOrCreate(
            ['cpf' => '12345678900'],
            [
                'name' => 'Admin Geral',
                'email' => 'admin@defesa.mg.gov.br',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        // Atribuir role super-admin (acesso total)
        $guard = config('auth.defaults.guard', 'web');
        $superAdminRole = \App\Models\Role::where('name', 'super-admin')
            ->where('guard_name', $guard)
            ->first();

        if ($superAdminRole) {
            $admin->assignRole($superAdminRole);
            $this->command->info('✅ Admin Geral criado com role super-admin');
        }

        // Em ambiente de desenvolvimento, criar usuários de teste
        if (app()->environment('local')) {
            $this->call(DevUsersSeeder::class);
        }
    }
}
