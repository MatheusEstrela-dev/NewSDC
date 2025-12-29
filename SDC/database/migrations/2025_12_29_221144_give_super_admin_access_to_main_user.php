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

            $user = \App\Models\User::where('cpf', '12345678900')->first();

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
