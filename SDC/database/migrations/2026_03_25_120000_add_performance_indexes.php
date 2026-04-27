<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gin');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_stat_statements');

        $trgmIndexes = [
            ['table' => 'pae_protocolos', 'column' => 'num_protocolo',      'name' => 'idx_trgm_pae_num_protocolo'],
            ['table' => 'pae_protocolos', 'column' => 'sei_numero',         'name' => 'idx_trgm_pae_sei_numero'],
            ['table' => 'pae_protocolos', 'column' => 'sigibar',            'name' => 'idx_trgm_pae_sigibar'],
            ['table' => 'pae_protocolos', 'column' => 'empnto_search',      'name' => 'idx_trgm_pae_empnto'],
            ['table' => 'processos',      'column' => 'n_protocolo_fide',   'name' => 'idx_trgm_proc_protocolo'],
            ['table' => 'processos',      'column' => 'tipo_desastre_nome', 'name' => 'idx_trgm_proc_tipo'],
            ['table' => 'rats',           'column' => 'protocolo',          'name' => 'idx_trgm_rat_protocolo'],
            ['table' => 'tasks',          'column' => 'titulo',             'name' => 'idx_trgm_task_titulo'],
            ['table' => 'tasks',          'column' => 'protocolo',          'name' => 'idx_trgm_task_protocolo'],
        ];

        foreach ($trgmIndexes as $idx) {
            if (Schema::hasTable($idx['table']) && !$this->hasIndex($idx['table'], $idx['name'])) {
                DB::statement(
                    "CREATE INDEX {$idx['name']} ON {$idx['table']} USING GIN ({$idx['column']} gin_trgm_ops)"
                );
            }
        }

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

        // btree_gin: GIN compostos em colunas de alta cardinalidade
        $btreeGinIndexes = [
            ['table' => 'pae_protocolos', 'columns' => ['status', 'created_at'], 'name' => 'idx_gin_pae_status_created'],
            ['table' => 'processos',      'columns' => ['status', 'created_at'], 'name' => 'idx_gin_proc_status_created'],
            ['table' => 'rats',           'columns' => ['status', 'created_at'], 'name' => 'idx_gin_rat_status_created'],
            ['table' => 'tasks',          'columns' => ['status', 'created_at'], 'name' => 'idx_gin_task_status_created'],
        ];

        foreach ($btreeGinIndexes as $idx) {
            if (Schema::hasTable($idx['table']) && !$this->hasIndex($idx['table'], $idx['name'])) {
                $cols = implode(', ', $idx['columns']);
                DB::statement(
                    "CREATE INDEX {$idx['name']} ON {$idx['table']} USING GIN ({$cols})"
                );
            }
        }
    }

    public function down(): void
    {
        $trgmNames = [
            'idx_trgm_pae_num_protocolo', 'idx_trgm_pae_sei_numero', 'idx_trgm_pae_sigibar',
            'idx_trgm_pae_empnto', 'idx_trgm_proc_protocolo', 'idx_trgm_proc_tipo',
            'idx_trgm_rat_protocolo', 'idx_trgm_task_titulo', 'idx_trgm_task_protocolo',
        ];
        foreach ($trgmNames as $name) {
            DB::statement("DROP INDEX IF EXISTS {$name}");
        }

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

        $btreeGinNames = [
            'idx_gin_pae_status_created', 'idx_gin_proc_status_created',
            'idx_gin_rat_status_created', 'idx_gin_task_status_created',
        ];
        foreach ($btreeGinNames as $name) {
            DB::statement("DROP INDEX IF EXISTS {$name}");
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $indexes = DB::select(
                "SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ? AND schemaname = 'public'",
                [$table, $indexName]
            );
        } else {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        }

        return \count($indexes) > 0;
    }
};
