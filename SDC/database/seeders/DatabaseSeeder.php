<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles e Permissões (base do sistema)
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. Órgãos (hierarquia CEDEC > REDEC > COMPDEC)
        $this->call(OrgaosSeeder::class);

        // 3. Admin principal do sistema
        $admin = \App\Models\User::updateOrCreate(
            ['cpf' => '12345678900'],
            [
                'name' => 'Admin Geral',
                'email' => 'admin@defesa.mg.gov.br',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $guard = config('auth.defaults.guard', 'web');
        $superAdminRole = \App\Models\Role::where('name', 'super-admin')
            ->where('guard_name', $guard)
            ->first();

        if ($superAdminRole) {
            $admin->assignRole($superAdminRole);
            $this->command->info('Admin Geral criado com role super-admin');
        }

        // 4. Usuários mock originais
        $this->call(MockUsersSeeder::class);

        // 5. Usuários com hierarquias diversas (30 usuários em todos os níveis)
        $this->call(MockUsersHierarchySeeder::class);

        // 6. RATs mock (15 registros com status variados)
        if (\Illuminate\Support\Facades\Schema::hasTable('rats')) {
            $this->call(RatMockSeeder::class);
        } else {
            $this->command->warn('Tabela "rats" não encontrada - RatMockSeeder pulado.');
        }
    }
}
