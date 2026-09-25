<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Support;

use App\Modules\Resgate\Exceptions\RegraDoResgate;
use Illuminate\Database\Connection;

/**
 * Trilha do pedido: trava o pedido para a transicao e grava cada passo como
 * evento append-only com hash encadeado (HashEvento). Unico ponto de escrita
 * de eventos, para pedido (Fase 3) e execucao (Fase 4).
 */
final class TrilhaDoPedido
{
    /**
     * Pedido travado (FOR UPDATE) e num dos status aceitos para a transicao.
     *
     * @param list<string> $statusAceitos
     */
    public function travar(Connection $db, int $pedidoId, array $statusAceitos): object
    {
        $pedido = $db->selectOne('SELECT * FROM resgate.pedidos WHERE id = ? FOR UPDATE', [$pedidoId]);
        if ($pedido === null) {
            throw new RegraDoResgate('Pedido não encontrado.', 'pedido');
        }
        if (! in_array($pedido->status, $statusAceitos, true)) {
            throw new RegraDoResgate('O pedido não está na etapa que esta ação exige.', 'pedido');
        }

        return $pedido;
    }

    public function registrar(Connection $db, int $pedidoId, string $etapa, ?string $de, string $para, ?int $atorId, ?string $permissao, ?string $justificativa, Rastro $rastro): void
    {
        $ultimo = $db->selectOne('SELECT sequencia, hash FROM resgate.pedido_eventos WHERE pedido_id = ? ORDER BY sequencia DESC LIMIT 1', [$pedidoId]);
        $evento = [
            'pedido_id' => $pedidoId,
            'sequencia' => $ultimo !== null ? (int) $ultimo->sequencia + 1 : 1,
            'etapa' => $etapa,
            'de_status' => $de,
            'para_status' => $para,
            'ator_user_id' => $atorId,
            'permissao' => $permissao,
            'justificativa' => $justificativa,
            'ip_address' => $rastro->ip,
            'ocorrido_em' => (string) $db->selectOne('SELECT clock_timestamp()::text AS agora')->agora,
        ];
        $anterior = $ultimo?->hash;

        $db->insert(
            'INSERT INTO resgate.pedido_eventos
                (pedido_id, sequencia, etapa, de_status, para_status, ator_user_id, permissao, justificativa,
                 ip_address, user_agent, session_id, request_id, hash, hash_anterior, ocorrido_em)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?::timestamptz)',
            [$pedidoId, $evento['sequencia'], $etapa, $de, $para, $atorId, $permissao, $justificativa,
                $rastro->ip, $rastro->userAgent, $rastro->sessao, $rastro->requisicao,
                HashEvento::calcular($evento, $anterior), $anterior, $evento['ocorrido_em']],
        );
    }

    /** Atualiza o status do pedido; sempre acompanhado de registrar(). */
    public function mudarStatus(Connection $db, int $pedidoId, string $status, array $colunas = []): void
    {
        $sets = ['status = ?', 'atualizado_em = now()'];
        $valores = [$status];
        foreach ($colunas as $coluna => $valor) {
            // Nomes de coluna vem do codigo, nunca da requisicao.
            $sets[] = $valor === 'now()' ? "{$coluna} = now()" : "{$coluna} = ?";
            if ($valor !== 'now()') {
                $valores[] = $valor;
            }
        }
        $valores[] = $pedidoId;
        $db->update('UPDATE resgate.pedidos SET ' . implode(', ', $sets) . ' WHERE id = ?', $valores);
    }
}
