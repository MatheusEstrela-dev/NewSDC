<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compdec_orgaos', function (Blueprint $table) {
            // Atos legais
            if (! Schema::hasColumn('compdec_orgaos', 'lei_criacao_numero')) {
                $table->string('lei_criacao_numero', 50)->nullable()->after('responsavel_email');
            }
            if (! Schema::hasColumn('compdec_orgaos', 'lei_criacao_data')) {
                $table->date('lei_criacao_data')->nullable()->after('lei_criacao_numero');
            }
            if (! Schema::hasColumn('compdec_orgaos', 'decreto_numero')) {
                $table->string('decreto_numero', 50)->nullable()->after('lei_criacao_data');
            }
            if (! Schema::hasColumn('compdec_orgaos', 'decreto_data')) {
                $table->date('decreto_data')->nullable()->after('decreto_numero');
            }
            if (! Schema::hasColumn('compdec_orgaos', 'portaria_numero')) {
                $table->string('portaria_numero', 50)->nullable()->after('decreto_data');
            }
            if (! Schema::hasColumn('compdec_orgaos', 'portaria_data')) {
                $table->date('portaria_data')->nullable()->after('portaria_numero');
            }

            // Quantitativos
            if (! Schema::hasColumn('compdec_orgaos', 'qtd_efetivo')) {
                $table->smallInteger('qtd_efetivo')->default(0)->after('portaria_data');
            }
            if (! Schema::hasColumn('compdec_orgaos', 'qtd_nupdec')) {
                $table->smallInteger('qtd_nupdec')->default(0)->after('qtd_efetivo');
            }

            // Capacidades estruturais (booleanos)
            if (! Schema::hasColumn('compdec_orgaos', 'tem_sede_propria')) {
                $table->boolean('tem_sede_propria')->default(false)->after('qtd_nupdec');
            }
            if (! Schema::hasColumn('compdec_orgaos', 'tem_viatura')) {
                $table->boolean('tem_viatura')->default(false)->after('tem_sede_propria');
            }
            if (! Schema::hasColumn('compdec_orgaos', 'tem_plano_contingencia')) {
                $table->boolean('tem_plano_contingencia')->default(false)
                    ->after('tem_viatura')
                    ->comment('Cache atualizado via observer quando plano e ativado/deletado');
            }
            if (! Schema::hasColumn('compdec_orgaos', 'tem_mapeamento_risco')) {
                $table->boolean('tem_mapeamento_risco')->default(false)->after('tem_plano_contingencia');
            }
            if (! Schema::hasColumn('compdec_orgaos', 'tem_simulado')) {
                $table->boolean('tem_simulado')->default(false)->after('tem_mapeamento_risco');
            }
            if (! Schema::hasColumn('compdec_orgaos', 'tem_cartao_pdc')) {
                $table->boolean('tem_cartao_pdc')->default(false)->after('tem_simulado');
            }

            // Contato secundario
            if (! Schema::hasColumn('compdec_orgaos', 'telefone_secundario')) {
                $table->string('telefone_secundario', 20)->nullable()->after('telefone');
            }
            if (! Schema::hasColumn('compdec_orgaos', 'fax')) {
                $table->string('fax', 20)->nullable()->after('telefone_secundario');
            }
            if (! Schema::hasColumn('compdec_orgaos', 'email_secundario')) {
                $table->string('email_secundario', 255)->nullable()->after('email');
            }
            if (! Schema::hasColumn('compdec_orgaos', 'email_terciario')) {
                $table->string('email_terciario', 255)->nullable()->after('email_secundario');
            }

            // Rastreabilidade ETL
            if (! Schema::hasColumn('compdec_orgaos', 'legacy_id')) {
                $table->unsignedBigInteger('legacy_id')->nullable()->after('metadata')
                    ->comment('ID original do legado (com_comdec.id_comdec) para idempotencia ETL');
                $table->index('legacy_id');
            }
        });

        // Indices secundarios para dashboards (idempotente)
        $this->createIndexIfNotExists('compdec_orgaos', 'idx_compdec_orgaos_tem_plano', '(tem_plano_contingencia)');
        $this->createIndexIfNotExists('compdec_orgaos', 'idx_compdec_orgaos_qtd_efetivo', '(qtd_efetivo)');

        // Indice trigram (busca fuzzy por codigo/nome)
        $this->createIndexIfNotExists(
            'compdec_orgaos',
            'idx_compdec_orgaos_codigo_trgm',
            'USING gin (codigo gin_trgm_ops)'
        );
        $this->createIndexIfNotExists(
            'compdec_orgaos',
            'idx_compdec_orgaos_nome_trgm',
            'USING gin (nome gin_trgm_ops)'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_compdec_orgaos_codigo_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_compdec_orgaos_nome_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_compdec_orgaos_tem_plano');
        DB::statement('DROP INDEX IF EXISTS idx_compdec_orgaos_qtd_efetivo');

        Schema::table('compdec_orgaos', function (Blueprint $table) {
            $columns = [
                'lei_criacao_numero', 'lei_criacao_data',
                'decreto_numero', 'decreto_data',
                'portaria_numero', 'portaria_data',
                'qtd_efetivo', 'qtd_nupdec',
                'tem_sede_propria', 'tem_viatura', 'tem_plano_contingencia',
                'tem_mapeamento_risco', 'tem_simulado', 'tem_cartao_pdc',
                'telefone_secundario', 'fax',
                'email_secundario', 'email_terciario',
                'legacy_id',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('compdec_orgaos', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function createIndexIfNotExists(string $table, string $indexName, string $expression): void
    {
        $exists = DB::selectOne(
            "SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?",
            [$table, $indexName]
        );
        if (! $exists) {
            DB::statement("CREATE INDEX {$indexName} ON {$table} {$expression}");
        }
    }
};
