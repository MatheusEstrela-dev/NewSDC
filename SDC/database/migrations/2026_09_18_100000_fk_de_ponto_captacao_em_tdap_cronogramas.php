<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `tdap_cronogramas.ponto_captacao_id` aponta para ponto que nao existe.
 *
 * A coluna nasceu com FK em 2026_05_14_140001, mas a FK nunca chegou ao banco:
 * o restore do dump de producao aborta o ADD CONSTRAINT por violacao, registra
 * o erro e segue -- sem `--exit-on-error` o pg_restore nao falha, apenas
 * termina com constraints a menos. O resultado e uma coluna com referencia
 * pendurada e nenhuma protecao.
 *
 * ESTADO ENCONTRADO (homologacao, copia de producao):
 *   - 1 cronograma SOFT-DELETED com ponto_captacao_id = 0, sentinela de "sem
 *     ponto" herdada de carga legada, nao id real;
 *   - 1 cronograma ATIVO, em aberto, com 11 caminhoes alocados, apontando para
 *     um ponto que foi criado e purgado com reset de sequence.
 *
 * POR QUE NULL E NAO UM PALPITE: o municipio do cronograma ativo tem
 * exatamente um ponto cadastrado, e seria tentador assumir que era aquele.
 * Nao da para saber -- ponto de captacao e de onde a agua sai, e errar isso
 * atribui origem errada a entrega. NULL diz "nao sabemos", que e a verdade.
 *
 * O comportamento visivel NAO MUDA: `pontoCaptacao()` e um BelongsTo, entao um
 * id que nao resolve ja devolve null hoje. A diferenca e que o banco passa a
 * impedir novas referencias penduradas, e o CronogramaRequest -- que ja exige
 * `Rule::exists` em ponto_captacao_id -- forca quem editar o cronograma a
 * escolher um ponto valido.
 *
 * Generica de proposito: nenhum id fixo, porque dev, homologacao e producao
 * nao tem os mesmos. Idempotente: pode rodar duas vezes sem efeito colateral.
 */
return new class extends Migration
{
    private const FK = 'tdap_cronogramas_ponto_captacao_id_foreign';

    public function up(): void
    {
        $this->anularReferenciasPenduradas();

        if (! $this->fkExiste()) {
            DB::statement(
                'ALTER TABLE tdap_cronogramas ADD CONSTRAINT '.self::FK.
                ' FOREIGN KEY (ponto_captacao_id) REFERENCES pip_pmda_ponto (id)'.
                ' ON UPDATE CASCADE ON DELETE SET NULL'
            );
        }
    }

    /**
     * ON DELETE SET NULL, nao RESTRICT: apagar um ponto de captacao nao pode
     * ficar bloqueado por cronograma historico, e o proprio dominio ja trata
     * ponto ausente como estado valido (a coluna e nullable).
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE tdap_cronogramas DROP CONSTRAINT IF EXISTS '.self::FK);
    }

    /**
     * Zera o que nao resolve, incluindo a sentinela 0.
     *
     * `updated_at` e tocado de proposito: e uma alteracao de dado, e esconder
     * isso do registro tornaria a investigacao futura mais dificil.
     */
    private function anularReferenciasPenduradas(): void
    {
        DB::statement(<<<'SQL'
            UPDATE tdap_cronogramas c
               SET ponto_captacao_id = NULL,
                   updated_at = NOW()
             WHERE c.ponto_captacao_id IS NOT NULL
               AND NOT EXISTS (
                     SELECT 1 FROM pip_pmda_ponto p WHERE p.id = c.ponto_captacao_id
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
