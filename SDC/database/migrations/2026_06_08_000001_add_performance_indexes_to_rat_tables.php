<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // rat_ocorrencias — índices para ORDER BY created_at e filtros de status/usuário
        $this->safeAddIndexes('rat_ocorrencias', [
            ['created_at'],
            ['status'],
            ['created_by'],
        ]);

        // rat_ocorrencia_relatos — tabela de junção polimórfica; índices críticos para eager loading
        $this->safeAddIndexes('rat_ocorrencia_relatos', [
            ['ocorrencia_id'],
            ['conteudo_type', 'conteudo_id'],
        ]);

        // rat_relato_dados_gerais — lookup por ocorrência
        $this->safeAddIndexes('rat_relato_dados_gerais', [
            ['ocorrencia_id'],
        ]);
    }

    public function down(): void
    {
        Schema::table('rat_ocorrencias', function (Blueprint $table) {
            try { $table->dropIndex(['created_at']); } catch (\Throwable) {}
            try { $table->dropIndex(['status']); } catch (\Throwable) {}
            try { $table->dropIndex(['created_by']); } catch (\Throwable) {}
        });

        Schema::table('rat_ocorrencia_relatos', function (Blueprint $table) {
            try { $table->dropIndex(['ocorrencia_id']); } catch (\Throwable) {}
            try { $table->dropIndex(['conteudo_type', 'conteudo_id']); } catch (\Throwable) {}
        });

        Schema::table('rat_relato_dados_gerais', function (Blueprint $table) {
            try { $table->dropIndex(['ocorrencia_id']); } catch (\Throwable) {}
        });
    }

    private function safeAddIndexes(string $table, array $columnSets): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($columnSets) {
            foreach ($columnSets as $columns) {
                try {
                    $blueprint->index($columns);
                } catch (\Throwable) {
                    // Índice já existe — ignorar
                }
            }
        });
    }
};
