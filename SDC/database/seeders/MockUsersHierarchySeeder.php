<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MockUsersHierarchySeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        // Buscar todas as roles
        $roles = Role::where('guard_name', $guard)->get()->keyBy('slug');

        // Buscar IDs de órgãos existentes (se o OrgaosSeeder já rodou)
        $orgaos = DB::table('orgaos')->pluck('id', 'codigo')->toArray();

        $defaultPassword = 'password';

        // ========================================================================
        // USUÁRIOS COM DIVERSAS HIERARQUIAS E ORGÃOS
        // ========================================================================
        $users = [
            // ── SUPER ADMIN (Nível 0) ── Desenvolvedores / Manutenção ──────
            [
                'name'  => 'Matheus Developer',
                'email' => 'matheus.dev@defesa.mg.gov.br',
                'cpf'   => '10020030040',
                'role'  => 'super-admin',
                'orgao' => 'CEDEC-SC',
                'active' => true,
            ],
            [
                'name'  => 'Lucas DevOps',
                'email' => 'lucas.devops@defesa.mg.gov.br',
                'cpf'   => '10020030041',
                'role'  => 'super-admin',
                'orgao' => 'CEDEC-SC',
                'active' => true,
            ],

            // ── ADMIN (Nível 1) ── Administradores do sistema ──────────────
            [
                'name'  => 'Carlos Eduardo Souza',
                'email' => 'carlos.souza@defesa.mg.gov.br',
                'cpf'   => '20030040050',
                'role'  => 'admin',
                'orgao' => 'CEDEC-SC',
                'active' => true,
            ],
            [
                'name'  => 'Patrícia Mendes',
                'email' => 'patricia.mendes@defesa.mg.gov.br',
                'cpf'   => '20030040051',
                'role'  => 'admin',
                'orgao' => 'CEDEC-SC',
                'active' => true,
            ],
            [
                'name'  => 'Rodrigo Alves Lima',
                'email' => 'rodrigo.lima@defesa.mg.gov.br',
                'cpf'   => '20030040052',
                'role'  => 'admin',
                'orgao' => 'REDEC-01-FLORIANOPOLIS',
                'active' => true,
            ],

            // ── MANAGER (Nível 2) ── Gestores Regionais ────────────────────
            [
                'name'  => 'Fernando Augusto Ribeiro',
                'email' => 'fernando.ribeiro@defesa.mg.gov.br',
                'cpf'   => '30040050060',
                'role'  => 'manager',
                'orgao' => 'REDEC-01-FLORIANOPOLIS',
                'active' => true,
            ],
            [
                'name'  => 'Adriana Campos Silva',
                'email' => 'adriana.silva@defesa.mg.gov.br',
                'cpf'   => '30040050061',
                'role'  => 'manager',
                'orgao' => 'REDEC-02-ITAJAI',
                'active' => true,
            ],
            [
                'name'  => 'Marcos Vinícius Ferreira',
                'email' => 'marcos.ferreira@defesa.mg.gov.br',
                'cpf'   => '30040050062',
                'role'  => 'manager',
                'orgao' => 'REDEC-03-JOINVILLE',
                'active' => true,
            ],
            [
                'name'  => 'Renata Cristina Barros',
                'email' => 'renata.barros@defesa.mg.gov.br',
                'cpf'   => '30040050063',
                'role'  => 'manager',
                'orgao' => 'CEDEC-SC',
                'active' => true,
            ],

            // ── ANALYST (Nível 3) ── Analistas Técnicos ────────────────────
            [
                'name'  => 'Thiago Henrique Costa',
                'email' => 'thiago.costa@defesa.mg.gov.br',
                'cpf'   => '40050060070',
                'role'  => 'analyst',
                'orgao' => 'COMPDEC-FLORIANOPOLIS',
                'active' => true,
            ],
            [
                'name'  => 'Camila de Oliveira Santos',
                'email' => 'camila.santos@defesa.mg.gov.br',
                'cpf'   => '40050060071',
                'role'  => 'analyst',
                'orgao' => 'COMPDEC-SAO-JOSE',
                'active' => true,
            ],
            [
                'name'  => 'Bruno Nascimento Pereira',
                'email' => 'bruno.pereira@defesa.mg.gov.br',
                'cpf'   => '40050060072',
                'role'  => 'analyst',
                'orgao' => 'COMPDEC-BLUMENAU',
                'active' => true,
            ],
            [
                'name'  => 'Isabela Vieira Machado',
                'email' => 'isabela.machado@defesa.mg.gov.br',
                'cpf'   => '40050060073',
                'role'  => 'analyst',
                'orgao' => 'COMPDEC-JOINVILLE',
                'active' => true,
            ],
            [
                'name'  => 'Rafael Dias Monteiro',
                'email' => 'rafael.monteiro@defesa.mg.gov.br',
                'cpf'   => '40050060074',
                'role'  => 'analyst',
                'orgao' => 'REDEC-01-FLORIANOPOLIS',
                'active' => true,
            ],
            [
                'name'  => 'Larissa Freitas Gonçalves',
                'email' => 'larissa.goncalves@defesa.mg.gov.br',
                'cpf'   => '40050060075',
                'role'  => 'analyst',
                'orgao' => 'REDEC-02-ITAJAI',
                'active' => true,
            ],

            // ── OPERATOR (Nível 4) ── Operadores de Sistema ────────────────
            [
                'name'  => 'Diego Moreira Lopes',
                'email' => 'diego.lopes@defesa.mg.gov.br',
                'cpf'   => '50060070080',
                'role'  => 'operator',
                'orgao' => 'COMPDEC-FLORIANOPOLIS',
                'active' => true,
            ],
            [
                'name'  => 'Priscila Rocha Almeida',
                'email' => 'priscila.almeida@defesa.mg.gov.br',
                'cpf'   => '50060070081',
                'role'  => 'operator',
                'orgao' => 'COMPDEC-SAO-JOSE',
                'active' => true,
            ],
            [
                'name'  => 'Gustavo Henrique Teixeira',
                'email' => 'gustavo.teixeira@defesa.mg.gov.br',
                'cpf'   => '50060070082',
                'role'  => 'operator',
                'orgao' => 'COMPDEC-BLUMENAU',
                'active' => true,
            ],
            [
                'name'  => 'Amanda Beatriz Cardoso',
                'email' => 'amanda.cardoso@defesa.mg.gov.br',
                'cpf'   => '50060070083',
                'role'  => 'operator',
                'orgao' => 'COMPDEC-JOINVILLE',
                'active' => true,
            ],
            [
                'name'  => 'Leandro Martins Nunes',
                'email' => 'leandro.nunes@defesa.mg.gov.br',
                'cpf'   => '50060070084',
                'role'  => 'operator',
                'orgao' => 'REDEC-01-FLORIANOPOLIS',
                'active' => true,
            ],

            // ── VIEWER (Nível 5) ── Somente Leitura ────────────────────────
            [
                'name'  => 'Mariana Lúcia Barbosa',
                'email' => 'mariana.barbosa@defesa.mg.gov.br',
                'cpf'   => '60070080090',
                'role'  => 'viewer',
                'orgao' => 'CEDEC-SC',
                'active' => true,
            ],
            [
                'name'  => 'Paulo Ricardo Azevedo',
                'email' => 'paulo.azevedo@defesa.mg.gov.br',
                'cpf'   => '60070080091',
                'role'  => 'viewer',
                'orgao' => 'REDEC-02-ITAJAI',
                'active' => true,
            ],
            [
                'name'  => 'Cláudia Aparecida Fonseca',
                'email' => 'claudia.fonseca@defesa.mg.gov.br',
                'cpf'   => '60070080092',
                'role'  => 'viewer',
                'orgao' => 'COMPDEC-FLORIANOPOLIS',
                'active' => true,
            ],

            // ── USER (Nível 6) ── Acesso Mínimo ────────────────────────────
            [
                'name'  => 'Tatiane Souza Ramos',
                'email' => 'tatiane.ramos@defesa.mg.gov.br',
                'cpf'   => '70080090100',
                'role'  => 'user',
                'orgao' => 'COMPDEC-BLUMENAU',
                'active' => true,
            ],
            [
                'name'  => 'Vinícius de Araújo Pinto',
                'email' => 'vinicius.pinto@defesa.mg.gov.br',
                'cpf'   => '70080090101',
                'role'  => 'user',
                'orgao' => 'COMPDEC-JOINVILLE',
                'active' => true,
            ],

            // ── SEM CARGO ── Aguardando Atribuição ─────────────────────────
            [
                'name'  => 'Luciana Matos Correia',
                'email' => 'luciana.correia@defesa.mg.gov.br',
                'cpf'   => '80090100110',
                'role'  => null,
                'orgao' => null,
                'active' => true,
            ],
            [
                'name'  => 'Sérgio Luiz Borges',
                'email' => 'sergio.borges@defesa.mg.gov.br',
                'cpf'   => '80090100111',
                'role'  => null,
                'orgao' => null,
                'active' => true,
            ],

            // ── INATIVOS ── Contas Desativadas ─────────────────────────────
            [
                'name'  => 'Roberto Carlos Inactive',
                'email' => 'roberto.inactive@defesa.mg.gov.br',
                'cpf'   => '90100110120',
                'role'  => 'operator',
                'orgao' => 'COMPDEC-FLORIANOPOLIS',
                'active' => false,
            ],
            [
                'name'  => 'Simone Inativa Pereira',
                'email' => 'simone.inativa@defesa.mg.gov.br',
                'cpf'   => '90100110121',
                'role'  => 'analyst',
                'orgao' => 'REDEC-01-FLORIANOPOLIS',
                'active' => false,
            ],
        ];

        $created  = 0;
        $skipped  = 0;
        $tableRows = [];

        foreach ($users as $userData) {
            $existing = User::where('email', $userData['email'])
                ->orWhere('cpf', $userData['cpf'])
                ->first();

            if ($existing) {
                $skipped++;
                continue;
            }

            // Resolver orgao_principal_id
            $orgaoPrincipalId = null;
            if (!empty($userData['orgao']) && isset($orgaos[$userData['orgao']])) {
                $orgaoPrincipalId = $orgaos[$userData['orgao']];
            }

            $user = User::create([
                'name'              => $userData['name'],
                'email'             => $userData['email'],
                'cpf'               => $userData['cpf'],
                'password'          => $defaultPassword,
                'email_verified_at' => now(),
                'active'            => $userData['active'],
                'orgao_principal_id' => $orgaoPrincipalId,
            ]);

            // Atribuir role
            $roleName = $userData['role'];
            if ($roleName && isset($roles[$roleName])) {
                $user->syncRoles([$roles[$roleName]]);
            }

            // Vincular ao orgão na tabela pivot
            if ($orgaoPrincipalId) {
                $funcaoMap = [
                    'super-admin' => 'coordenador',
                    'admin'       => 'coordenador',
                ];
                $funcao = $funcaoMap[$roleName] ?? 'agente';

                DB::table('orgao_user')->insertOrIgnore([
                    'user_id'      => $user->id,
                    'orgao_id'     => $orgaoPrincipalId,
                    'funcao'       => $funcao,
                    'is_principal'  => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            $created++;
            $tableRows[] = [
                $userData['name'],
                $userData['email'],
                $roleName ?? 'Sem cargo',
                $userData['orgao'] ?? '-',
                $userData['active'] ? 'Ativo' : 'Inativo',
            ];
        }

        $this->command->newLine();
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('  USUÁRIOS COM HIERARQUIAS DIVERSAS - MOCK');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info("  Criados: {$created} | Já existentes (pulados): {$skipped}");
        $this->command->info("  Senha padrão: {$defaultPassword}");
        $this->command->newLine();

        // Resumo por hierarquia
        $hierarchy = collect($users)->groupBy('role');
        $this->command->info('  Distribuição por Hierarquia:');
        foreach ($hierarchy as $role => $group) {
            $roleLabel = $role ?: 'sem-cargo';
            $this->command->info("    {$roleLabel}: " . $group->count() . ' usuários');
        }
        $this->command->newLine();

        if (!empty($tableRows)) {
            $this->command->table(
                ['Nome', 'Email', 'Cargo', 'Órgão', 'Status'],
                $tableRows
            );
        }
    }
}
