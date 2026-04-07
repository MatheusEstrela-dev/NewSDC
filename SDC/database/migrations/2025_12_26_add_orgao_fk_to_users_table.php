<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Adiciona FK de orgao_principal_id após tabela orgaos ser criada
     */
    public function up(): void
    {
        // Só executa se ambas as tabelas existem
        if (Schema::hasTable('users') && Schema::hasTable('orgaos')) {
            Schema::table('users', function (Blueprint $table) {
                // Verifica se a constraint já não existe
                if (!$this->constraintExists('users', 'users_orgao_principal_id_foreign')) {
                    $table->foreign('orgao_principal_id')
                        ->references('id')
                        ->on('orgaos')
                        ->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if ($this->constraintExists('users', 'users_orgao_principal_id_foreign')) {
                    $table->dropForeign(['orgao_principal_id']);
                }
            });
        }
    }

    /**
     * Verificar se constraint existe
     */
    private function constraintExists($table, $constraint)
    {
        $constraints = DB::select(
            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_NAME = ? AND CONSTRAINT_NAME = ?",
            [DB::getDatabaseName(), $constraint]
        );
        return count($constraints) > 0;
    }
};
