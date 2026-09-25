<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Services;

use App\Modules\Resgate\Enums\EscopoCarteira;
use App\Modules\Resgate\Exceptions\RegraDoResgate;
use App\Modules\Resgate\Support\CreditosDoEnte;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Connection;

/**
 * Movimentos da carteira de resgate: reserva, liberacao e o DEBITO definitivo
 * com rastreio de origem (plano, secao 2.3).
 *
 * Toda chave de movimento e 'pedido:{id}:{tipo}': reprocessar a mesma
 * transicao nao duplica pontos (ON CONFLICT DO NOTHING).
 */
final class MovimentosDaCarteira
{
    public function reservar(Connection $db, object $pedido): void
    {
        $this->movimentar($db, $pedido, 'reserva', -(int) $pedido->custo_pontos);
    }

    /** Devolve pontos e unidade de pedido que nao seguiu (recusa, cancelamento, expiracao). */
    public function liberar(Connection $db, object $pedido): void
    {
        $this->movimentar($db, $pedido, 'liberacao', (int) $pedido->custo_pontos);
        if ($pedido->unidade_id !== null) {
            $db->update("UPDATE resgate.unidades SET estado = 'disponivel' WHERE id = ? AND estado = 'reservada'", [(int) $pedido->unidade_id]);
        }
    }

    /**
     * Converte a reserva em DEBITO definitivo e grava de quais lancamentos do
     * ledger os pontos sairam: FIFO pela competencia mais antiga, so credito
     * elegivel (real: maduro e sem ajuste; demonstracao: so demo).
     */
    public function debitar(Connection $db, object $pedido, DateTimeImmutable $agora): void
    {
        $custo = (int) $pedido->custo_pontos;
        if ($custo <= 0) {
            return;
        }

        // Reserva baixada e debito lancado no mesmo passo: o saldo nao "pisca".
        $this->movimentar($db, $pedido, 'liberacao', $custo);
        $debitoId = $this->movimentar($db, $pedido, 'debito', -$custo);
        if ($debitoId === null) {
            return; // ja debitado antes: idempotente
        }

        $escopo = EscopoCarteira::from((string) $pedido->ente_escopo);
        // Mesmo lock da reserva: dois debitos do mesmo ente nao consomem o
        // mesmo credito ao mesmo tempo.
        $db->statement('SELECT pg_advisory_xact_lock(hashtext(?), hashtext(?))', ['resgate.ente', "{$escopo->value}:{$pedido->ente_id}"]);
        $demonstracao = $this->booleano($pedido->demonstracao);
        $carencia = max(0, (int) config('resgate.carencia_dias'));
        $corte = $agora->setTimezone(new DateTimeZone('UTC'))->modify("-{$carencia} days")->format(DATE_ATOM);

        $candidatos = $db->select(
            'WITH ' . CreditosDoEnte::cte($escopo) . '
             SELECT id, liquido - consumido AS disponivel
               FROM creditos
              WHERE liquido - consumido > 0
                AND ' . ($demonstracao ? 'demo' : "NOT demo AND NOT em_ajuste AND competencia_em <= ?::timestamptz") . '
              ORDER BY competencia_em, id',
            $demonstracao ? [(int) $pedido->ente_id] : [(int) $pedido->ente_id, $corte],
        );

        $restante = $custo;
        foreach ($candidatos as $credito) {
            if ($restante === 0) {
                break;
            }
            $parte = min($restante, (int) $credito->disponivel);
            $db->insert(
                'INSERT INTO resgate.consumos (movimento_id, pedido_id, lancamento_id, pontos) VALUES (?, ?, ?, ?)',
                [$debitoId, (int) $pedido->id, (int) $credito->id, $parte],
            );
            $restante -= $parte;
        }

        if ($restante > 0) {
            // A reserva garantia o saldo; faltar credito aqui e sinal de
            // estorno no meio do caminho. Aborta a transacao inteira.
            throw new RegraDoResgate("Não há créditos elegíveis suficientes para debitar {$custo} pontos; faltam {$restante}.", 'pedido');
        }
    }

    /** @return int|null id do movimento criado; null se ja existia (idempotencia). */
    private function movimentar(Connection $db, object $pedido, string $tipo, int $pontos): ?int
    {
        if ($pontos === 0) {
            return null;
        }
        $linha = $db->selectOne(
            'INSERT INTO resgate.movimentos (ente_escopo, ente_id, tipo, pontos, pedido_id, demonstracao, chave)
             VALUES (?, ?, ?, ?, ?, ?, ?) ON CONFLICT (chave) DO NOTHING RETURNING id',
            [$pedido->ente_escopo, (int) $pedido->ente_id, $tipo, $pontos, (int) $pedido->id,
                $this->booleano($pedido->demonstracao), "pedido:{$pedido->id}:{$tipo}"],
        );

        return $linha !== null ? (int) $linha->id : null;
    }

    private function booleano(mixed $valor): bool
    {
        return is_bool($valor) ? $valor : in_array(strtolower(trim((string) $valor)), ['t', 'true', '1'], true);
    }
}
