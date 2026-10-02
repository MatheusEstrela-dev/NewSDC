<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cronograma passa a ter VARIOS pontos de captacao (antes: um so, em
 * tdap_cronogramas.ponto_captacao_id).
 *
 * `pmda_plano_id` guarda de qual PMDA aprovado o ponto veio no momento do
 * vinculo. Fica nulo no acervo migrado -- nenhum dos 91 cronogramas com ponto
 * usa ponto vinculado a PMDA aprovado (levantamento de 2026-10-02) -- e e
 * isso que a tela mostra como "Legado".
 *
 * ON DELETE do ponto: CASCADE, pelo mesmo motivo do SET NULL da FK antiga
 * (2026_09_18_110000): apagar um ponto nao pode ficar bloqueado por cronograma
 * historico. O cronograma ativo nao perde o registro, que esta no snapshot
 * `stored_pmda_ponto`.
 *
 * A coluna `ponto_captacao_id` NAO sai aqui: o codigo deixa de usa-la e ela
 * fica para o rollback ate a validacao em producao.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_cronograma_ponto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cronograma_id')
                ->constrained('tdap_cronogramas')
                ->cascadeOnDelete();
            $table->foreignId('ponto_id')
                ->constrained('pip_pmda_ponto')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('pmda_plano_id')->nullable()
                ->constrained('pmda_planos')
                ->nullOnDelete()
                ->comment('PMDA aprovado que autorizou o ponto; nulo = legado/fora do PMDA');
            $table->timestamps();

            $table->unique(['cronograma_id', 'ponto_id']);
            $table->index('ponto_id');
        });

        // Inclui os cronogramas excluidos (soft delete) para nao perder historico.
        DB::statement(<<<'SQL'
            INSERT INTO tdap_cronograma_ponto (cronograma_id, ponto_id, pmda_plano_id, created_at, updated_at)
            SELECT c.id, c.ponto_captacao_id, NULL, NOW(), NOW()
              FROM tdap_cronogramas c
             WHERE c.ponto_captacao_id IS NOT NULL
               AND EXISTS (SELECT 1 FROM pip_pmda_ponto p WHERE p.id = c.ponto_captacao_id)
        SQL);
    }

    /**
     * Devolve a coluna antiga onde ela estiver vazia (cronograma criado ja com
     * o pivot), com o menor ponto de cada cronograma: e o melhor que um campo
     * unico consegue guardar de uma lista.
     */
    public function down(): void
    {
        DB::statement(<<<'SQL'
            UPDATE tdap_cronogramas c
               SET ponto_captacao_id = (
                     SELECT MIN(cp.ponto_id) FROM tdap_cronograma_ponto cp WHERE cp.cronograma_id = c.id
                   )
             WHERE c.ponto_captacao_id IS NULL
               AND EXISTS (SELECT 1 FROM tdap_cronograma_ponto cp WHERE cp.cronograma_id = c.id)
        SQL);

        Schema::dropIfExists('tdap_cronograma_ponto');
    }
};
