<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rat_ocorrencia_relatos')) {
            Schema::table('rat_ocorrencia_relatos', function (Blueprint $table) {
                if (!$this->hasIndex('rat_ocorrencia_relatos', 'idx_ocorrencia')) {
                    $table->index('ocorrencia_id', 'idx_ocorrencia');
                }
                if (!$this->hasIndex('rat_ocorrencia_relatos', 'idx_conteudo')) {
                    $table->index(['conteudo_type', 'conteudo_id'], 'idx_conteudo');
                }
                if (!$this->hasIndex('rat_ocorrencia_relatos', 'idx_created')) {
                    $table->index('created_at', 'idx_created');
                }
            });
        }

        if (Schema::hasTable('rat_relato_envolvidos')) {
            Schema::table('rat_relato_envolvidos', function (Blueprint $table) {
                if (!$this->hasIndex('rat_relato_envolvidos', 'idx_tipo_pessoa')) {
                    $table->index('g_tipo_pessoa', 'idx_tipo_pessoa');
                }
                if (!$this->hasIndex('rat_relato_envolvidos', 'idx_cpf')) {
                    $table->index('p_cpf', 'idx_cpf');
                }
            });
        }

        if (Schema::hasTable('entrada_processos')) {
            Schema::table('entrada_processos', function (Blueprint $table) {
                if (!$this->hasIndex('entrada_processos', 'idx_status_created')) {
                    $table->index(['status', 'created_at'], 'idx_status_created');
                }
            });
        }

        if (Schema::hasTable('decreto_municipio')) {
            Schema::table('decreto_municipio', function (Blueprint $table) {
                if (!$this->hasIndex('decreto_municipio', 'idx_entrada_municipio')) {
                    $table->index(['entrada_processos_id', 'municipio_id'], 'idx_entrada_municipio');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('rat_ocorrencia_relatos', function (Blueprint $table) {
            $table->dropIndex('idx_ocorrencia');
            $table->dropIndex('idx_conteudo');
            $table->dropIndex('idx_created');
        });

        Schema::table('rat_relato_envolvidos', function (Blueprint $table) {
            $table->dropIndex('idx_tipo_pessoa');
            $table->dropIndex('idx_cpf');
        });

        Schema::table('entrada_processos', function (Blueprint $table) {
            $table->dropIndex('idx_status_created');
        });

        Schema::table('decreto_municipio', function (Blueprint $table) {
            $table->dropIndex('idx_entrada_municipio');
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
