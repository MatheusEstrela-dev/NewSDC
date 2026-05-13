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

        // 1b. Permissões específicas do módulo RAT (roles + 9 permissões)
        $this->call(RatPermissionsSeeder::class);

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
        $superAdminRole = \App\Models\Role::where('slug', 'super-admin')
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

        // 6. RATs mock (15 registros com status variados — tabela legada)
        if (\Illuminate\Support\Facades\Schema::hasTable('rats')) {
            $this->call(RatMockSeeder::class);
        } else {
            $this->command->warn('Tabela "rats" não encontrada - RatMockSeeder pulado.');
        }

        // 6b. REDECs de Minas Gerais (tabela de referência rat_redec)
        if (\Illuminate\Support\Facades\Schema::hasTable('rat_redec')) {
            $this->call(RatRedecSeeder::class);
        } else {
            $this->command->warn('Tabela "rat_redec" não encontrada - RatRedecSeeder pulado.');
        }

        // 7. Orgaos de teste (hierarquia completa para testes)
        $this->call(TestOrgaosSeeder::class);

        // 8. Usuarios de teste com hierarquia e diferentes status
        $this->call(TestUsersSeeder::class);

        // 9. Estrutura canonica do FIDE/S2iD (grupos -> categorias -> itens -> campos)
        //    Indispensavel para a aba "Dados de Desastre" exibir os campos de input.
        if (\Illuminate\Support\Facades\Schema::hasTable('dec_desastre_grupos')) {
            $this->call(DesastreEstruturaSeeder::class);
        } else {
            $this->command->warn('Tabela "dec_desastre_grupos" nao encontrada - DesastreEstruturaSeeder pulado.');
        }
    }
}
