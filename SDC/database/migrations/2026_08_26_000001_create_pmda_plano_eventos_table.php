<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Log de eventos do ciclo de vida do PMDA (criacao, envio, devolucao, aprovacao,
 * arquivamento).
 *
 * Existe porque o tramite morava em colunas mutaveis do proprio plano
 * (motivo_analise, dt_estado, resp_estado) compartilhadas por aprovar/arquivar/
 * devolver: cada nova decisao sobrescrevia a anterior e a serie historica perdia
 * o ciclo passado. Aqui cada transicao e uma LINHA, entao devolver -> corrigir ->
 * aprovar preserva os tres momentos.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('pmda_plano_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pmda_plano_id')->constrained('pmda_planos')->cascadeOnDelete();
            // CRIACAO | ENVIO | DEVOLUCAO | APROVACAO | ARQUIVAMENTO
            $table->string('tipo', 20);
            $table->string('de_status', 20)->nullable();
            $table->string('para_status', 20)->nullable();
            $table->text('motivo')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            // Snapshot do nome: o card historico nao pode mudar porque o usuario
            // trocou de nome ou foi removido depois do tramite.
            $table->string('responsavel', 100)->nullable();
            $table->timestamp('ocorrido_em');
            $table->timestamps();

            $table->index(['pmda_plano_id', 'ocorrido_em']);
        });

        $this->backfill();
    }

    /**
     * Reconstroi o que da para reconstruir dos planos que ja existem. E melhor
     * esforco: o instante da devolucao de um plano depois aprovado foi perdido no
     * proprio dt_estado, entao cai para dt_analise para nao aparecer DEPOIS da
     * aprovacao na linha do tempo.
     */
    private function backfill(): void
    {
        DB::statement(<<<'SQL'
            INSERT INTO pmda_plano_eventos
                (pmda_plano_id, tipo, de_status, para_status, motivo, usuario_id, responsavel, ocorrido_em, created_at, updated_at)
            SELECT id, 'CRIACAO', NULL, 'RASCUNHO', NULL, created_by, NULL, created_at, now(), now()
            FROM pmda_planos
            WHERE created_at IS NOT NULL
        SQL);

        DB::statement(<<<'SQL'
            INSERT INTO pmda_plano_eventos
                (pmda_plano_id, tipo, de_status, para_status, motivo, usuario_id, responsavel, ocorrido_em, created_at, updated_at)
            SELECT id, 'ENVIO', 'COMPLETO', 'EM_ANALISE', NULL, NULL, resp_homolog, dt_analise, now(), now()
            FROM pmda_planos
            WHERE dt_analise IS NOT NULL
        SQL);

        DB::statement(<<<'SQL'
            INSERT INTO pmda_plano_eventos
                (pmda_plano_id, tipo, de_status, para_status, motivo, usuario_id, responsavel, ocorrido_em, created_at, updated_at)
            SELECT id, 'DEVOLUCAO', 'EM_ANALISE', 'RASCUNHO', motivo_analise, NULL, resp_estado,
                   CASE
                       WHEN data_aprov IS NOT NULL AND dt_estado >= data_aprov THEN COALESCE(dt_analise, dt_estado)
                       ELSE COALESCE(dt_estado, dt_ultima_alteracao, created_at)
                   END,
                   now(), now()
            FROM pmda_planos
            WHERE pedido_altera = true AND motivo_analise IS NOT NULL AND status <> 'ARQUIVADO'
        SQL);

        DB::statement(<<<'SQL'
            INSERT INTO pmda_plano_eventos
                (pmda_plano_id, tipo, de_status, para_status, motivo, usuario_id, responsavel, ocorrido_em, created_at, updated_at)
            SELECT id, 'APROVACAO', 'EM_ANALISE', 'APROVADO', NULL, NULL, resp_estado, data_aprov, now(), now()
            FROM pmda_planos
            WHERE data_aprov IS NOT NULL
        SQL);

        DB::statement(<<<'SQL'
            INSERT INTO pmda_plano_eventos
                (pmda_plano_id, tipo, de_status, para_status, motivo, usuario_id, responsavel, ocorrido_em, created_at, updated_at)
            SELECT id, 'ARQUIVAMENTO', 'EM_ANALISE', 'ARQUIVADO', motivo_analise, NULL, resp_estado,
                   COALESCE(dt_estado, dt_ultima_alteracao, created_at), now(), now()
            FROM pmda_planos
            WHERE status = 'ARQUIVADO'
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('pmda_plano_eventos');
    }
};
