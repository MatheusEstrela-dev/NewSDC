<?php

declare(strict_types=1);

namespace App\Modules\Ranking\DTOs;

/**
 * Regra do catalogo ja localizada na competencia do fato.
 *
 * Existe porque o ScoreCalculator e o RecordScoreTransaction pedem coisas
 * diferentes da MESMA linha de ranking.regras:
 *
 *   - o calculo quer apenas os atributos de pontuacao (ScoreRuleData), sem
 *     saber que existe banco;
 *   - o livro quer a identidade da versao aplicada (id e versao), para que a
 *     transacao e o lancamento registrem QUAL regra decidiu o premio.
 *
 * Sem o par id/versao gravado junto, republicar o catalogo tornaria impossivel
 * reproduzir a decisao antiga: o lancamento apontaria para uma regra cujo texto
 * mudou. Por isso o repositorio devolve os dois lados juntos e o orquestrador
 * nao precisa consultar a tabela duas vezes.
 *
 * REGRA DESABILITADA TAMBEM CHEGA AQUI. Nao e o repositorio quem filtra: o
 * ScoreCalculator decide Zero com motivo 'regra_desabilitada', e a transacao
 * precisa do id para registrar contra qual regra a decisao foi tomada.
 */
final readonly class RegraVigente
{
    public function __construct(
        /** ranking.regras.id da versao aplicada. */
        public int $id,

        /** ranking.regras.versao. Vai para lancamentos.regra_versao. */
        public int $versao,

        /** Projecao pura para o calculo. */
        public ScoreRuleData $regra,
    ) {}
}
