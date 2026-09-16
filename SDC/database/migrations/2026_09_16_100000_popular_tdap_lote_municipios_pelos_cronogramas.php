<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Preenche tdap_lote_municipios com os pares que os cronogramas ja provam.
 *
 * A migration que criou o pivot mudou o modelo -- um lote passou a atender
 * varios municipios -- e o formulario de cronograma passou a ler dali. O que
 * nao houve foi a carga: o pivot ficou VAZIO para os 45 lotes, e a tela mostra
 * "Este lote nao tem municipios vinculados" em todos eles.
 *
 * A coluna antiga `tdap_lotes.municipio_id` nao serve de origem: ela esta com
 * 0 nas 45 linhas, e 0 nao e municipio nenhum -- lixo do ETL.
 *
 * A fonte confiavel e o historico: cada cronograma emitido registra o lote E o
 * municipio atendido. Sao 75 pares distintos cobrindo 34 dos 45 lotes. Os 11
 * lotes que nunca emitiram cronograma ficam de fora porque nao ha de onde
 * inferir -- esses precisam de cadastro manual.
 *
 * Idempotente: o unique (lote_id, municipio_id) com ON CONFLICT DO NOTHING
 * deixa rodar de novo sem duplicar, e sem desfazer vinculo editado a mao.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            INSERT INTO tdap_lote_municipios (lote_id, municipio_id, created_at, updated_at)
            SELECT DISTINCT c.lote_id, c.municipio_id, now(), now()
            FROM tdap_cronogramas c
            JOIN tdap_lotes l ON l.id = c.lote_id AND l.deleted_at IS NULL
            JOIN municipios m ON m.id = c.municipio_id
            WHERE c.deleted_at IS NULL
              AND c.lote_id IS NOT NULL
              AND c.municipio_id IS NOT NULL
            ON CONFLICT (lote_id, municipio_id) DO NOTHING
        SQL);
    }

    /**
     * Sem rollback de proposito.
     *
     * Depois desta carga o pivot passa a receber edicao manual pela tela de
     * lote, e nao ha como distinguir o que veio daqui do que alguem cadastrou
     * depois. Apagar tudo no down destruiria trabalho humano para desfazer uma
     * copia de dados que ja existiam.
     */
    public function down(): void
    {
        // no-op
    }
};
