<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Support;

use App\Modules\Resgate\Enums\EscopoCarteira;

/**
 * Definicao UNICA de "credito do ente" no ledger (plano, secao 2.2), usada
 * pela carteira (saldo) e pelo debito (consumo FIFO). Duas copias desta regra
 * divergiriam, e o saldo mostrado deixaria de ser o saldo debitado.
 *
 * Cada linha: lancamento de credito de transacao CONFIRMADA do ente, com
 *   liquido       = pontos + estornos que o anulam (estorno parcial reduz);
 *   consumido     = o que resgates ja debitaram dele;
 *   demo          = chave 'demo:*' ou contexto.demonstracao = true;
 *   em_ajuste     = ha pedido de ajuste pendente sobre ele.
 *
 * O ente vem do vinculo HISTORICO gravado no lancamento, nunca do atual.
 */
final class CreditosDoEnte
{
    /** CTE `creditos` parametrizada pelo id do ente (1 binding). */
    public static function cte(EscopoCarteira $escopo): string
    {
        // Coluna vinda do enum, nunca da requisicao: segura para interpolar.
        $coluna = $escopo->colunaDoLancamento();

        return "creditos AS (
            SELECT l.id,
                   l.pontos + COALESCE(est.total, 0) AS liquido,
                   COALESCE(con.total, 0) AS consumido,
                   l.competencia_em,
                   (t.chave_canonica LIKE 'demo:%' OR COALESCE(t.contexto->>'demonstracao', 'false') = 'true') AS demo,
                   EXISTS (
                       SELECT 1 FROM ranking.pedidos_ajuste a
                        WHERE a.lancamento_id = l.id AND a.decisao = 'pendente'
                   ) AS em_ajuste
              FROM ranking.lancamentos l
              JOIN ranking.transacoes t ON t.id = l.transacao_id
              LEFT JOIN LATERAL (
                   SELECT SUM(e.pontos) AS total FROM ranking.lancamentos e WHERE e.estorno_de_id = l.id
              ) est ON true
              LEFT JOIN LATERAL (
                   SELECT SUM(c.pontos) AS total FROM resgate.consumos c WHERE c.lancamento_id = l.id
              ) con ON true
             WHERE l.{$coluna} = ?
               AND l.estorno_de_id IS NULL
               AND l.pontos > 0
               AND t.decisao = 'confirmada'
        )";
    }
}
