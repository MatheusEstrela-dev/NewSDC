<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agregado raiz ProcessoTdap.
 *
 * Coordena o workflow completo (Habilitacao -> Decretagem -> Licitacao ->
 * Execucao -> Liquidacao -> Pago -> Encerrado) e referencia entidades de
 * outros modulos (Decretacoes, PAE) sem acoplamento direto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_processos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero', 20)->unique();
            $table->string('estado', 30)->index()
                ->comment('rascunho | em_habilitacao | decreto_pendente | em_licitacao | em_execucao | liquidacao_pendente | pago | encerrado');
            $table->string('swimlane_atual', 30)->index()
                ->comment('fomentacao | cedec | juridico | licitacao | governador | financeiro | encerrado');

            $table->foreignId('municipio_id')
                ->constrained('municipios')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedBigInteger('decretacao_id')->nullable()
                ->comment('FK opcional para Decretacoes.processos.id (cross-module, sem FK fisica)');
            $table->unsignedBigInteger('pae_form_id')->nullable()
                ->comment('FK opcional para Pae.forms.id (cross-module, sem FK fisica)');

            $table->jsonb('contexto')->nullable()
                ->comment('Dados livres do processo (origem da demanda, etc.)');

            $table->timestampTz('aberto_em');
            $table->timestampTz('encerrado_em')->nullable();

            $table->foreignId('aberto_por')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->index('municipio_id');
            $table->index('decretacao_id');
            $table->index('pae_form_id');
            $table->index(['estado', 'swimlane_atual']);
        });

        // CHECK constraint para estado — apenas PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE tdap_processos
                ADD CONSTRAINT chk_tdap_processos_estado
                CHECK (estado IN (
                    'rascunho','em_habilitacao','decreto_pendente','em_licitacao',
                    'em_execucao','liquidacao_pendente','pago','encerrado'
                ))
            ");
        }

        // Garante a coluna processo_tdap_id em tdap_cronogramas antes da FK.
        // Necessario porque a migration de tdap_cronogramas pode ter rodado
        // em ambientes onde a coluna ainda nao havia sido consolidada nela.
        if (! Schema::hasColumn('tdap_cronogramas', 'processo_tdap_id')) {
            Schema::table('tdap_cronogramas', function (Blueprint $table) {
                $table->uuid('processo_tdap_id')->nullable()->after('prestador_id')->index()
                    ->comment('FK opcional para tdap_processos (workflow Fase 6)');
            });
        }

        // FK tdap_cronogramas.processo_tdap_id -> tdap_processos.id.
        // SET NULL ao deletar processo (nao destroi cronogramas historicos).
        Schema::table('tdap_cronogramas', function (Blueprint $table) {
            $table->foreign('processo_tdap_id')
                ->references('id')->on('tdap_processos')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tdap_cronogramas', function (Blueprint $table) {
            $table->dropForeign(['processo_tdap_id']);
        });
        Schema::dropIfExists('tdap_processos');
    }
};
