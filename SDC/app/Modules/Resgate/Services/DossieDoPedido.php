<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Services;

use Illuminate\Support\Facades\DB;

/**
 * Dossie do pedido para auditoria, controle externo e processo (plano, secao 6).
 *
 * Reune num documento so: o pedido, a VERSAO do item congelada, a trilha com
 * a verificacao da cadeia, os anexos com hash, os bloqueios e - o elo que
 * sustenta a defesa - cada lancamento consumido ate a transacao e a evidencia
 * de origem no ledger.
 *
 * O proprio dossie leva o SHA-256 do seu conteudo canonico: quem recebe o
 * arquivo confere que nada foi alterado depois da emissao.
 */
final class DossieDoPedido
{
    public function __construct(
        private readonly PedidoResgate $pedidos,
        private readonly BloqueiosDoResgate $bloqueios,
    ) {}

    /** @return array<string, mixed>|null */
    public function montar(int $pedidoId): ?array
    {
        $detalhe = $this->pedidos->detalhe($pedidoId);
        if ($detalhe === null) {
            return null;
        }
        $db = DB::connection((string) config('resgate.conexao'));

        $item = (array) $db->selectOne(
            'SELECT i.codigo, i.versao, i.tipo, i.titulo, i.descricao, i.beneficiario, i.faixa_minima, i.custo_pontos,
                    i.instrumento, i.base_normativa, i.unidade_responsavel, i.documentos_exigidos, i.demonstracao,
                    i.vigente_de, i.vigente_ate, i.proposto_por, i.aprovado_por, i.proposta_id
               FROM resgate.catalogo_itens i JOIN resgate.pedidos p ON p.item_id = i.id WHERE p.id = ?',
            [$pedidoId],
        );

        // Origem de cada ponto: lancamento -> transacao -> evento de negocio.
        $origem = array_map(static fn (object $o): array => (array) $o, $db->select(
            'SELECT c.lancamento_id, c.pontos AS pontos_consumidos, l.competencia_em, l.modulo,
                    t.id AS transacao_id, t.event_id, t.event_name, t.chave_canonica, t.familia, t.decisao,
                    t.actor_user_id, t.credited_user_id, t.validador_user_id, t.contexto, t.ocorrido_em
               FROM resgate.consumos c
               JOIN ranking.lancamentos l ON l.id = c.lancamento_id
               JOIN ranking.transacoes t ON t.id = l.transacao_id
              WHERE c.pedido_id = ?
              ORDER BY l.competencia_em, c.lancamento_id',
            [$pedidoId],
        ));

        $conteudo = [
            'emitido_em' => now()->toAtomString(),
            'pedido' => $detalhe['pedido'],
            'item_versao_congelada' => $item,
            'trilha' => [
                'eventos' => $detalhe['eventos'],
                'cadeia_integra' => $detalhe['adulterado_em'] === null,
                'adulterado_no_evento' => $detalhe['adulterado_em'],
                'algoritmo' => 'sha256(json(campos do evento) + hash_anterior)',
            ],
            'documentos' => $detalhe['documentos'],
            'bloqueios' => $this->bloqueios->listar($pedidoId),
            'origem_dos_pontos' => $origem,
            'totais' => [
                'custo_pontos' => $detalhe['pedido']['custo_pontos'],
                'pontos_consumidos' => array_sum(array_column($origem, 'pontos_consumidos')),
                'lancamentos_consumidos' => count($origem),
            ],
        ];

        return $conteudo + ['sha256_do_dossie' => hash('sha256', json_encode($conteudo, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE))];
    }

    /** Confere um dossie recebido: recalcula o hash do conteudo sem o campo do hash. */
    public static function conferir(array $dossie): bool
    {
        $hash = $dossie['sha256_do_dossie'] ?? null;
        unset($dossie['sha256_do_dossie']);

        return $hash !== null && hash_equals($hash, hash('sha256', json_encode($dossie, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)));
    }
}
