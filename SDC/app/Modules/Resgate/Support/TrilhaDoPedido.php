<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Support;

use App\Modules\Resgate\Exceptions\RegraDoResgate;
use Illuminate\Database\Connection;

/**
 * Trilha do pedido: trava o pedido para a transicao, aplica a segregacao de
 * funcoes e grava cada passo como evento append-only com hash encadeado.
 * Unico ponto de escrita de eventos, para pedido e execucao.
 */
final class TrilhaDoPedido
{
    private const ROTULOS_PAPEL = [
        'solicitante' => 'solicitante', 'decisor' => 'quem decide na CEDEC', 'formalizador' => 'quem formaliza o termo pelo Estado',
        'assinante_municipio' => 'quem assina pelo município', 'entregador' => 'quem entrega', 'recebedor' => 'quem recebe pelo município',
    ];

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

    /**
     * Segregacao de funcoes (P4): quem ja ocupou um papel no pedido nao ocupa
     * outro. O mapeamento etapa -> papel e a funcao resgate.papel_da_etapa do
     * banco - fonte unica; o trigger recusa de novo se o servico for contornado.
     */
    public function exigirPapelLivre(Connection $db, int $pedidoId, string $etapa, int $atorId): void
    {
        $conflito = $db->selectOne(
            'SELECT resgate.papel_da_etapa(e.etapa) AS anterior, resgate.papel_da_etapa(?) AS pretendido
               FROM resgate.pedido_eventos e
              WHERE e.pedido_id = ? AND e.ator_user_id = ?
                AND resgate.papel_da_etapa(e.etapa) IS NOT NULL
                AND resgate.papel_da_etapa(e.etapa) <> resgate.papel_da_etapa(?)
              LIMIT 1',
            [$etapa, $pedidoId, $atorId, $etapa],
        );
        if ($conflito !== null) {
            throw new RegraDoResgate(sprintf(
                'Você já atuou neste pedido como %s; esta etapa (%s) precisa de outra pessoa.',
                self::ROTULOS_PAPEL[$conflito->anterior] ?? $conflito->anterior,
                self::ROTULOS_PAPEL[$conflito->pretendido] ?? $conflito->pretendido,
            ), 'pedido');
        }
    }

    public function registrar(Connection $db, int $pedidoId, string $etapa, ?string $de, string $para, ?int $atorId, ?string $permissao, ?string $justificativa, Rastro $rastro): void
    {
        if ($atorId !== null) {
            $this->exigirPapelLivre($db, $pedidoId, $etapa, $atorId);
        }
        $pedido = $db->selectOne('SELECT * FROM resgate.pedidos WHERE id = ?', [$pedidoId]);
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
            'user_agent' => $rastro->userAgent,
            'session_id' => $rastro->sessao,
            'request_id' => $rastro->requisicao,
            'ocorrido_em' => (string) $db->selectOne('SELECT ' . HashEvento::instante('clock_timestamp()') . ' AS agora')->agora,
        ];
        $anterior = $ultimo?->hash;

        $db->insert(
            'INSERT INTO resgate.pedido_eventos
                (pedido_id, sequencia, etapa, de_status, para_status, ator_user_id, permissao, justificativa,
                 ip_address, user_agent, session_id, request_id, hash, hash_anterior, ocorrido_em)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?::timestamptz)',
            [$pedidoId, $evento['sequencia'], $etapa, $de, $para, $atorId, $permissao, $justificativa,
                $rastro->ip, $rastro->userAgent, $rastro->sessao, $rastro->requisicao,
                HashEvento::calcular($evento, HashEvento::retrato($pedido), $anterior), $anterior, $evento['ocorrido_em']],
        );
    }

    /**
     * Eventos do pedido prontos para verificacao (instante canonico).
     *
     * @return list<array<string, mixed>>
     */
    public function eventos(Connection $db, int $pedidoId): array
    {
        return array_map(static fn (object $e): array => (array) $e, $db->select(
            'SELECT pedido_id, sequencia, etapa, de_status, para_status, ator_user_id, permissao, justificativa,
                    ip_address, user_agent, session_id, request_id, ' . HashEvento::instante('ocorrido_em') . ' AS ocorrido_em,
                    hash, hash_anterior
               FROM resgate.pedido_eventos WHERE pedido_id = ? ORDER BY sequencia',
            [$pedidoId],
        ));
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
