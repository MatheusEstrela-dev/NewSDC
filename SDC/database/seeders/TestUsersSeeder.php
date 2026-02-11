<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TestUsersSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'Test@2026!';

    public function run(): void
    {
        $this->command->info('Criando usuarios de teste com hierarquia...');

        $guard = config('auth.defaults.guard', 'web');
        $roles = Role::where('guard_name', $guard)->pluck('id', 'slug')->toArray();

        if (empty($roles)) {
            $this->command->error('Nenhuma role encontrada. Execute RolesAndPermissionsSeeder primeiro.');
            return;
        }

        $orgaos = DB::table('orgaos')
            ->where('codigo', 'LIKE', 'TEST-%')
            ->pluck('id', 'codigo')
            ->toArray();

        if (empty($orgaos)) {
            $this->command->error('Nenhum orgao de teste encontrado. Execute TestOrgaosSeeder primeiro.');
            return;
        }

        $users = $this->getUsersData($orgaos);
        $created = 0;
        $skipped = 0;

        foreach ($users as $userData) {
            $existing = User::where('email', $userData['email'])
                ->orWhere('cpf', $userData['cpf'])
                ->first();

            if ($existing) {
                $this->command->warn("Usuario ja existe: {$userData['email']}");
                $skipped++;
                continue;
            }

            $roleSlug = $userData['role'];
            $orgaoCodigo = $userData['orgao'];
            $funcao = $userData['funcao'];
            unset($userData['role'], $userData['orgao'], $userData['funcao']);

            $orgaoId = $orgaoCodigo ? ($orgaos[$orgaoCodigo] ?? null) : null;

            $user = User::create(array_merge($userData, [
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'email_verified_at' => $userData['status'] === 'pending' ? null : now(),
                'orgao_principal_id' => $orgaoId,
            ]));

            if ($roleSlug && isset($roles[$roleSlug])) {
                $user->roles()->attach($roles[$roleSlug]);
            }

            if ($orgaoId) {
                DB::table('orgao_user')->insertOrIgnore([
                    'user_id' => $user->id,
                    'orgao_id' => $orgaoId,
                    'funcao' => $funcao,
                    'is_principal' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $created++;
        }

        $this->command->info("Usuarios criados: {$created} | Ignorados: {$skipped}");
        $this->command->newLine();
        $this->command->info('Credenciais de teste:');
        $this->command->table(
            ['Email', 'Senha', 'Role', 'Status'],
            collect($this->getUsersData($orgaos))->map(fn($u) => [
                $u['email'],
                self::DEFAULT_PASSWORD,
                $u['role'] ?? 'sem-role',
                $u['status'],
            ])->toArray()
        );
    }

    private function getUsersData(array $orgaos): array
    {
        return [
            // SUPER-ADMIN (nivel 0) - CEDEC
            [
                'name' => 'Super Admin Teste',
                'email' => 'super.admin@test.defesa.mg.gov.br',
                'cpf' => '90000000001',
                'active' => true,
                'status' => 'active',
                'role' => 'super-admin',
                'orgao' => 'TEST-CEDEC-MG',
                'funcao' => 'coordenador',
            ],

            // ADMIN (nivel 1) - CEDEC e REDEC
            [
                'name' => 'Admin CEDEC Teste',
                'email' => 'admin.cedec@test.defesa.mg.gov.br',
                'cpf' => '90000000002',
                'active' => true,
                'status' => 'active',
                'role' => 'admin',
                'orgao' => 'TEST-CEDEC-MG',
                'funcao' => 'coordenador',
            ],
            [
                'name' => 'Admin REDEC Metro Teste',
                'email' => 'admin.redec@test.defesa.mg.gov.br',
                'cpf' => '90000000003',
                'active' => true,
                'status' => 'active',
                'role' => 'admin',
                'orgao' => 'TEST-REDEC-METRO',
                'funcao' => 'coordenador',
            ],

            // COORDENADOR (nivel 2) - REDECs e COMPDEC
            [
                'name' => 'Coordenador REDEC Metro Teste',
                'email' => 'coord.metro@test.defesa.mg.gov.br',
                'cpf' => '90000000004',
                'active' => true,
                'status' => 'active',
                'role' => 'coordenador',
                'orgao' => 'TEST-REDEC-METRO',
                'funcao' => 'coordenador',
            ],
            [
                'name' => 'Coordenador REDEC Sul Teste',
                'email' => 'coord.sul@test.defesa.mg.gov.br',
                'cpf' => '90000000005',
                'active' => true,
                'status' => 'active',
                'role' => 'coordenador',
                'orgao' => 'TEST-REDEC-SUL',
                'funcao' => 'coordenador',
            ],
            [
                'name' => 'Coordenador COMPDEC BH Teste',
                'email' => 'coord.bh@test.defesa.mg.gov.br',
                'cpf' => '90000000006',
                'active' => true,
                'status' => 'active',
                'role' => 'coordenador',
                'orgao' => 'TEST-COMPDEC-BH',
                'funcao' => 'coordenador',
            ],

            // ANALISTA (nivel 3) - COMPDECs
            [
                'name' => 'Analista COMPDEC BH Teste',
                'email' => 'analista.bh@test.defesa.mg.gov.br',
                'cpf' => '90000000007',
                'active' => true,
                'status' => 'active',
                'role' => 'analista',
                'orgao' => 'TEST-COMPDEC-BH',
                'funcao' => 'tecnico',
            ],
            [
                'name' => 'Analista COMPDEC Contagem Teste',
                'email' => 'analista.contagem@test.defesa.mg.gov.br',
                'cpf' => '90000000008',
                'active' => true,
                'status' => 'active',
                'role' => 'analista',
                'orgao' => 'TEST-COMPDEC-CONTAGEM',
                'funcao' => 'tecnico',
            ],
            [
                'name' => 'Analista COMPDEC Pouso Alegre Teste',
                'email' => 'analista.pouso@test.defesa.mg.gov.br',
                'cpf' => '90000000009',
                'active' => true,
                'status' => 'active',
                'role' => 'analista',
                'orgao' => 'TEST-COMPDEC-POUSO',
                'funcao' => 'tecnico',
            ],

            // OPERADOR (nivel 4) - COMPDECs
            [
                'name' => 'Operador COMPDEC BH Teste',
                'email' => 'operador.bh@test.defesa.mg.gov.br',
                'cpf' => '90000000010',
                'active' => true,
                'status' => 'active',
                'role' => 'operador',
                'orgao' => 'TEST-COMPDEC-BH',
                'funcao' => 'agente',
            ],
            [
                'name' => 'Operador COMPDEC Varginha Teste',
                'email' => 'operador.varginha@test.defesa.mg.gov.br',
                'cpf' => '90000000011',
                'active' => true,
                'status' => 'active',
                'role' => 'operador',
                'orgao' => 'TEST-COMPDEC-VARGINHA',
                'funcao' => 'agente',
            ],

            // VISUALIZADOR (nivel 5) - diferentes status
            [
                'name' => 'Visualizador Ativo Teste',
                'email' => 'visualizador.ativo@test.defesa.mg.gov.br',
                'cpf' => '90000000012',
                'active' => true,
                'status' => 'active',
                'role' => 'visualizador',
                'orgao' => null,
                'funcao' => 'apoio',
            ],
            [
                'name' => 'Visualizador Inativo Teste',
                'email' => 'visualizador.inativo@test.defesa.mg.gov.br',
                'cpf' => '90000000013',
                'active' => false,
                'status' => 'inactive',
                'role' => 'visualizador',
                'orgao' => null,
                'funcao' => 'apoio',
            ],
            [
                'name' => 'Visualizador Bloqueado Teste',
                'email' => 'visualizador.bloqueado@test.defesa.mg.gov.br',
                'cpf' => '90000000014',
                'active' => false,
                'status' => 'blocked',
                'role' => 'visualizador',
                'orgao' => null,
                'funcao' => 'apoio',
            ],

            // STATUS ESPECIAIS
            [
                'name' => 'Usuario Suspenso Teste',
                'email' => 'usuario.suspenso@test.defesa.mg.gov.br',
                'cpf' => '90000000015',
                'active' => false,
                'status' => 'suspended',
                'role' => 'operador',
                'orgao' => 'TEST-COMPDEC-BH',
                'funcao' => 'agente',
            ],
            [
                'name' => 'Usuario Pendente Teste',
                'email' => 'usuario.pendente@test.defesa.mg.gov.br',
                'cpf' => '90000000016',
                'active' => true,
                'status' => 'pending',
                'role' => 'operador',
                'orgao' => 'TEST-COMPDEC-CONTAGEM',
                'funcao' => 'agente',
            ],

            // SEM ROLE (para teste de acesso negado)
            [
                'name' => 'Usuario Sem Role Teste',
                'email' => 'sem.role@test.defesa.mg.gov.br',
                'cpf' => '90000000017',
                'active' => true,
                'status' => 'active',
                'role' => null,
                'orgao' => 'TEST-COMPDEC-VARGINHA',
                'funcao' => 'apoio',
            ],
        ];
    }
}
