<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevUsersSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        // Garantir que as roles existam (se ainda não rodou o seeder principal)
        if (!Role::where('name', 'super-admin')->where('guard_name', $guard)->exists()) {
            $this->command?->warn('Roles não encontradas. Execute primeiro: php artisan db:seed --class=RolesAndPermissionsSeeder');
        }

        // Senha padrão para dev (mínimo 8)
        $defaultPassword = 'password';

        $users = [
            [
                'name' => 'Usuario Teste',
                'email' => 'teste@example.com',
                'cpf' => '12345678900',
                'role' => 'super-admin',
            ],
            [
                'name' => 'Admin Sistema',
                'email' => 'admin@example.com',
                'cpf' => '11122233344',
                'role' => 'admin',
            ],
            [
                'name' => 'Gestor Regional',
                'email' => 'gestor@example.com',
                'cpf' => '22233344455',
                'role' => 'manager',
            ],
            [
                'name' => 'Analista Tecnico',
                'email' => 'analista@example.com',
                'cpf' => '33344455566',
                'role' => 'analyst',
            ],
            [
                'name' => 'Operador Plantao',
                'email' => 'operador@example.com',
                'cpf' => '44455566677',
                'role' => 'operator',
            ],
            [
                'name' => 'Visualizador',
                'email' => 'viewer@example.com',
                'cpf' => '55566677788',
                'role' => 'viewer',
            ],
            [
                'name' => 'Usuario Padrao',
                'email' => 'user@example.com',
                'cpf' => '66677788899',
                'role' => 'user',
            ],
            [
                'name' => 'Sem Cargo',
                'email' => 'semcargo@example.com',
                'cpf' => '77788899900',
                'role' => null,
            ],
        ];

        foreach ($users as $payload) {
            $user = User::updateOrCreate(
                ['cpf' => $payload['cpf']],
                [
                    'name' => $payload['name'],
                    'email' => $payload['email'],
                    // password tem cast "hashed" no model, então pode passar em texto plano
                    'password' => $defaultPassword,
                    'email_verified_at' => now(),
                ]
            );

            if (!empty($payload['role'])) {
                $role = Role::where('name', $payload['role'])->where('guard_name', $guard)->first();

                if ($role) {
                    // syncRoles garante que fique com exatamente esse cargo
                    $user->syncRoles([$role]);
                } else {
                    // Fallback: vincular via pivot para não falhar em ambiente com roles custom
                    $roleId = DB::table('roles')
                        ->where('name', $payload['role'])
                        ->where('guard_name', $guard)
                        ->value('id');

                    if ($roleId) {
                        DB::table('model_has_roles')->updateOrInsert(
                            [
                                'role_id' => $roleId,
                                'model_type' => User::class,
                                'model_id' => $user->id,
                            ],
                            []
                        );
                    }
                }
            }
        }

        $this->command?->info('✅ Usuários de DEV criados/atualizados (senha padrão: password).');
    }
}


