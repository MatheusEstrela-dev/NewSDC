<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Usa DB::table() diretamente para evitar o scope de SoftDeletes do modelo Role,
     * que adiciona "AND roles.deleted_at IS NULL" antes da coluna existir.
     */
    public function up(): void
    {
        try {
            $guard = config('auth.defaults.guard', 'web');
            $now   = now();

            // Cria a role via query builder (sem SoftDeletes scope)
            $roleId = DB::table('roles')->where('slug', 'super-admin')->where('guard_name', $guard)->value('id');
            if (!$roleId) {
                $roleId = DB::table('roles')->insertGetId([
                    'name'            => 'Desenvolvedor',
                    'guard_name'      => $guard,
                    'slug'            => 'super-admin',
                    'hierarchy_level' => 0,
                    'description'     => 'Acesso total e irrestrito ao sistema - Desenvolvimento e Manutencao',
                    'is_active'       => true,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }

            // Cria ou atualiza o usuário principal
            $userId = DB::table('users')->where('cpf', '12345678900')->value('id');
            if (!$userId) {
                $userId = DB::table('users')->insertGetId([
                    'name'              => 'Admin Geral',
                    'email'             => 'admin@defesa.mg.gov.br',
                    'cpf'               => '12345678900',
                    'password'          => Hash::make('password'),
                    'email_verified_at' => $now,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }

            // Vincula role ao usuário (tabela pivot model_has_roles)
            if ($userId && $roleId) {
                $exists = DB::table('model_has_roles')
                    ->where('role_id', $roleId)
                    ->where('model_id', $userId)
                    ->where('model_type', \App\Models\User::class)
                    ->exists();

                if (!$exists) {
                    DB::table('model_has_roles')->insert([
                        'role_id'    => $roleId,
                        'model_id'   => $userId,
                        'model_type' => \App\Models\User::class,
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao atribuir role na migração: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nenhuma ação necessária no rollback
    }
};
