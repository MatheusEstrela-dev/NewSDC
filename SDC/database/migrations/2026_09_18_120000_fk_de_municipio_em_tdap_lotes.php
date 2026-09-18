<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `tdap_lotes.municipio_id` guarda 0 -- id que nao existe em `municipios`.
 *
 * A coluna nasceu NOT NULL com FK em 2026_05_14_120002. A carga de dados
 * legados gravou 0 onde nao havia municipio, porque o LoteDTO da epoca fazia
 * `(int) ($data['municipio_id'] ?? 0)` -- ausencia virava zero, nao NULL. O
 * codigo atual ja corrigiu isso para `$municipioIds[0] ?? null`.
 *
 * A migration 2026_08_17_100000 criou a pivo N:N `tdap_lote_municipios`,
 * afrouxou a coluna para nullable e TENTOU zerar os invalidos. O saneamento
 * dela foi no-op: a tabela estava vazia quando rodou, e os 0 voltaram depois,
 * na carga. Por isso a FK nunca conseguiu ser recriada -- no restore, o
 * pg_restore aborta o ADD CONSTRAINT por violacao, registra o erro e segue.
 *
 * NENHUMA INFORMACAO SE PERDE AQUI. 0 nao e municipio: a coluna ja nao diz
 * nada hoje. O municipio de verdade vive na pivo, que tem FK propria com
 * ON DELETE RESTRICT -- essa sim, a relacao autoritativa.
 *
 * Levantamento antes de decidir (homologacao, copia de producao): 45 lotes com
 * municipio_id = 0. Desses, 34 tem municipio na pivo. Os 11 restantes tem ZERO
 * cronogramas, e a pivo foi populada a partir dos cronogramas
 * (2026_09_16_100000) -- sem cronograma nao havia de onde derivar. Um deles se
 * chama "cronograma tdap para testes". Nenhum esta em uso operacional.
 *
 * ON DELETE SET NULL, nao RESTRICT como no schema original: esta coluna e
 * legada e anulavel por decisao de projeto (ver o docblock de Lote::municipio).
 * Quem impede apagar municipio em uso e a FK da pivo, que ja e RESTRICT.
 * Repetir RESTRICT aqui bloquearia exclusao duas vezes pelo mesmo motivo e
 * dificultaria aposentar a coluna depois.
 *
 * Generica de proposito: nenhum id fixo, porque dev, homologacao e producao nao
 * tem os mesmos. Idempotente: pode rodar duas vezes sem efeito colateral.
 */
return new class extends Migration
{
    private const FK = 'tdap_lotes_municipio_id_foreign';

    public function up(): void
    {
        $this->anularMunicipiosInexistentes();

        if (! $this->fkExiste()) {
            DB::statement(
                'ALTER TABLE tdap_lotes ADD CONSTRAINT '.self::FK.
                ' FOREIGN KEY (municipio_id) REFERENCES municipios (id)'.
                ' ON UPDATE CASCADE ON DELETE SET NULL'
            );
        }
    }

    /**
     * Derruba so a constraint. NAO repoe os 0: eram dado invalido, e recria-los
     * seria reintroduzir o defeito. Mesmo criterio da migration de CNPJ e da de
     * ponto de captacao.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE tdap_lotes DROP CONSTRAINT IF EXISTS '.self::FK);
    }

    /**
     * Cobre o 0 e qualquer outro id que nao resolva, sem citar valor nenhum.
     *
     * `updated_at` e tocado de proposito: e alteracao de dado, e esconde-la do
     * registro so dificultaria a investigacao seguinte.
     */
    private function anularMunicipiosInexistentes(): void
    {
        DB::statement(<<<'SQL'
            UPDATE tdap_lotes l
               SET municipio_id = NULL,
                   updated_at = NOW()
             WHERE l.municipio_id IS NOT NULL
               AND NOT EXISTS (
                     SELECT 1 FROM municipios m WHERE m.id = l.municipio_id
                   )
        SQL);
    }

    private function fkExiste(): bool
    {
        return DB::table('pg_constraint')
            ->where('conname', self::FK)
            ->where('contype', 'f')
            ->exists();
    }
};
