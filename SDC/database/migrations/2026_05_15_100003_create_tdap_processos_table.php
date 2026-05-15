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

        // CHECK constraint para estado (Postgres-native)
        DB::statement("
            ALTER TABLE tdap_processos
            ADD CONSTRAINT chk_tdap_processos_estado
            CHECK (estado IN (
                'rascunho','em_habilitacao','decreto_pendente','em_licitacao',
                'em_execucao','liquidacao_pendente','pago','encerrado'
            ))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_processos');
    }
};
