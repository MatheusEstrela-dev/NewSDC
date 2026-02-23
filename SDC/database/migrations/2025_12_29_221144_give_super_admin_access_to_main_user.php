<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // Garante que as roles existam antes de atribuir
            $guard = config('auth.defaults.guard', 'web');
            $superAdminRole = \App\Models\Role::firstOrCreate(
                ['name' => 'super-admin', 'guard_name' => $guard],
                ['hierarchy_level' => 0]
            );

            // Busca ou cria o usuário principal
            $user = \App\Models\User::updateOrCreate(
                ['cpf' => '12345678900'],
                [
                    'name' => 'Admin Geral',
                    'email' => 'admin@defesa.mg.gov.br',
                    'password' => \Illuminate\Support\Facades\Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            if ($user) {
                $user->assignRole($superAdminRole);
            }
        } catch (\Exception $e) {
            // Ignora erros para não travar o deploy se a tabela ainda não existir
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
