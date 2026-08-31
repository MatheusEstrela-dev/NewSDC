<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder seguro para uso em produção.
 * Cria apenas o usuário admin principal se ele não existir.
 */
class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        // Garantir que roles e permissões existam
        $this->call(RolesAndPermissionsSeeder::class);

        // Dados de referência: municípios de Minas Gerais (idempotente)
        $this->call(MunicipiosMGSeeder::class);

        // Dados de referência: tabela oficial do COBRADE (idempotente)
        $this->call(CobradeSeeder::class);

        // Verificar se o admin já existe (por email ou CPF)
        $adminExists = User::where('email', 'admin@defesa.mg.gov.br')
            ->orWhere('cpf', '12345678900')
            ->exists();

        if ($adminExists) {
            $this->command->info('⚠️  Admin Geral já existe no sistema.');
            return;
        }

        // Criar admin principal
        $admin = User::create([
            'name' => 'Admin Geral',
            'email' => 'admin@defesa.mg.gov.br',
            'cpf' => '12345678900',
            'password' => bcrypt('ChangeMe@2025!'), // ⚠️ ALTERAR NA PRIMEIRA EXECUÇÃO
            'email_verified_at' => now(),
        ]);

        // Atribuir role super-admin
        $superAdminRole = Role::where('slug', 'super-admin')
            ->where('guard_name', $guard)
            ->first();

        if ($superAdminRole) {
            $admin->assignRole($superAdminRole);
            $this->command->info('✅ Admin Geral criado com sucesso!');
            $this->command->info('📧 Email: admin@defesa.mg.gov.br');
            $this->command->info('🔑 CPF: 12345678900');
            $this->command->warn('⚠️  IMPORTANTE: Altere a senha no primeiro login!');
        } else {
            $this->command->error('❌ Erro: Role super-admin não encontrada!');
        }
    }
}
