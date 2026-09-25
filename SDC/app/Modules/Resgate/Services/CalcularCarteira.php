<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Services;

use App\Modules\Resgate\Contracts\SaldoResgatavel;
use App\Modules\Resgate\DTOs\Carteira;
use App\Modules\Resgate\Enums\EscopoCarteira;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Carteira de resgate derivada do ledger (plano, secao 2.2).
 *
 * CREDITO MADURO = todas as condicoes ao mesmo tempo:
 *   - lancamento de credito (sem estorno_de_id) de transacao CONFIRMADA. O
 *     ledger tambem grava lancamento de transacao pendente; ele nao entra;
 *   - valor liquido dos estornos que o anulam (estorno parcial reduz);
 *   - competencia ha mais de `carencia_dias`;
 *   - sem pedido de ajuste pendente sobre ele;
 *   - fora de demonstracao (chave 'demo:*' ou contexto.demonstracao = true).
 *     Ponto de demonstracao NUNCA e resgatavel, por construcao (P5).
 *
 * O ente vem do vinculo HISTORICO gravado no lancamento (municipio_id /
 * orgao_id), nunca do vinculo atual do usuario.
 *
 * CUIDADO COM OCTANE: stateless; conexao resolvida por nome a cada chamada.
 */
final class CalcularCarteira implements SaldoResgatavel
{
    public function carteira(EscopoCarteira $escopo, int $enteId, DateTimeImmutable $agora): Carteira
    {
        $carencia = max(0, (int) config('resgate.carencia_dias'));
        $corte = $agora->setTimezone(new DateTimeZone('UTC'))->modify("-{$carencia} days");
        // Coluna vinda do enum, nunca da requisicao: segura para interpolar.
        $coluna = $escopo->colunaDoLancamento();

        $creditos = $this->conexao()->selectOne(
            "WITH creditos AS (
                SELECT l.pontos + COALESCE(est.total, 0) AS liquido,
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
                 WHERE l.{$coluna} = ?
                   AND l.estorno_de_id IS NULL
                   AND l.pontos > 0
                   AND t.decisao = 'confirmada'
            )
            SELECT COALESCE(SUM(liquido) FILTER (WHERE demo), 0) AS demonstracao,
                   COALESCE(SUM(liquido) FILTER (WHERE NOT demo AND em_ajuste), 0) AS em_ajuste,
                   COALESCE(SUM(liquido) FILTER (WHERE NOT demo AND NOT em_ajuste AND competencia_em > ?::timestamptz), 0) AS em_carencia,
                   MIN(competencia_em) FILTER (WHERE NOT demo AND NOT em_ajuste AND competencia_em > ?::timestamptz AND liquido > 0) AS carencia_mais_antiga,
                   COALESCE(SUM(liquido) FILTER (WHERE NOT demo AND NOT em_ajuste AND competencia_em <= ?::timestamptz), 0) AS maduro
              FROM creditos",
            [$enteId, $corte->format(DATE_ATOM), $corte->format(DATE_ATOM), $corte->format(DATE_ATOM)],
        );

        // Reserva e debito sao negativos; liberacao e estorno de debito,
        // positivos. Reserva ativa = reservado menos o que ja foi liberado.
        // Saldo real e de demonstracao separados: nunca se misturam.
        $movimentos = $this->conexao()->selectOne(
            "SELECT COALESCE(-SUM(pontos) FILTER (WHERE NOT demonstracao AND tipo IN ('reserva', 'liberacao')), 0) AS reservado,
                    COALESCE(-SUM(pontos) FILTER (WHERE NOT demonstracao AND tipo IN ('debito', 'estorno_debito')), 0) AS debitado,
                    COALESCE(-SUM(pontos) FILTER (WHERE demonstracao), 0) AS comprometido_demo
               FROM resgate.movimentos
              WHERE ente_escopo = ? AND ente_id = ?",
            [$escopo->value, $enteId],
        );

        $maisAntiga = $creditos->carencia_mais_antiga ?? null;

        return new Carteira(
            escopo: $escopo,
            enteId: $enteId,
            calculadaEm: $agora,
            carenciaDias: $carencia,
            maduro: (int) $creditos->maduro,
            emCarencia: (int) $creditos->em_carencia,
            proximaLiberacao: $maisAntiga !== null
                ? (new DateTimeImmutable((string) $maisAntiga))->modify("+{$carencia} days")
                : null,
            emAjuste: (int) $creditos->em_ajuste,
            demonstracao: (int) $creditos->demonstracao,
            reservado: (int) $movimentos->reservado,
            debitado: (int) $movimentos->debitado,
            comprometidoDemonstracao: (int) $movimentos->comprometido_demo,
        );
    }

    private function conexao(): Connection
    {
        return DB::connection((string) config('resgate.conexao'));
    }
}
