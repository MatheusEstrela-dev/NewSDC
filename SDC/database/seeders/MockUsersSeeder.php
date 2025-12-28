<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MockUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Buscar roles existentes
        $superAdmin = Role::where('slug', 'super-admin')->first();
        $admin = Role::where('slug', 'admin')->first();
        $analyst = Role::where('slug', 'analyst')->first();
        $operator = Role::where('slug', 'operator')->first();

        // Criar usuários mock
        $users = [
            [
                'name' => 'João Silva',
                'email' => 'joao.silva@defesa.mg.gov.br',
                'cpf' => '12345678910',
                'role' => $admin,
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'maria.santos@defesa.mg.gov.br',
                'cpf' => '98765432100',
                'role' => $analyst,
            ],
            [
                'name' => 'Pedro Oliveira',
                'email' => 'pedro.oliveira@defesa.mg.gov.br',
                'cpf' => '45678912345',
                'role' => $analyst,
            ],
            [
                'name' => 'Ana Costa',
                'email' => 'ana.costa@defesa.mg.gov.br',
                'cpf' => '32165498765',
                'role' => $operator,
            ],
            [
                'name' => 'Carlos Pereira',
                'email' => 'carlos.pereira@defesa.mg.gov.br',
                'cpf' => '78912345678',
                'role' => $operator,
            ],
            [
                'name' => 'Juliana Almeida',
                'email' => 'juliana.almeida@defesa.mg.gov.br',
                'cpf' => '15975348620',
                'role' => $analyst,
            ],
            [
                'name' => 'Roberto Fernandes',
                'email' => 'roberto.fernandes@defesa.mg.gov.br',
                'cpf' => '75315948630',
                'role' => $admin,
            ],
            [
                'name' => 'Fernanda Lima',
                'email' => 'fernanda.lima@defesa.mg.gov.br',
                'cpf' => '95135785240',
                'role' => $operator,
            ],
        ];

        foreach ($users as $userData) {
            // Verificar se o usuário já existe
            $existingUser = User::where('email', $userData['email'])->first();

            if ($existingUser) {
                $this->command->warn("Usuário {$userData['email']} já existe. Pulando...");
                continue;
            }

            // Criar usuário
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'cpf' => $userData['cpf'],
                'password' => Hash::make('password'), // Senha padrão: password
                'email_verified_at' => now(),
            ]);

            // Atribuir role
            if ($userData['role']) {
                $user->roles()->attach($userData['role']->id);
                $this->command->info("✓ Usuário {$userData['name']} criado com role {$userData['role']->name}");
            } else {
                $this->command->info("✓ Usuário {$userData['name']} criado sem role");
            }
        }

        $this->command->newLine();
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('  USUÁRIOS MOCK CRIADOS COM SUCESSO');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->newLine();
        $this->command->info('Credenciais padrão:');
        $this->command->info('  Email: <qualquer email acima>');
        $this->command->info('  Senha: password');
        $this->command->newLine();
        $this->command->table(
            ['Nome', 'Email', 'Role'],
            collect($users)->map(function ($user) {
                return [
                    $user['name'],
                    $user['email'],
                    $user['role']->name ?? 'Sem role',
                ];
            })->toArray()
        );
    }
}
