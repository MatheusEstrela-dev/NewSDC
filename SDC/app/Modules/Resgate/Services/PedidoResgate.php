<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Services;

use App\Modules\Ranking\Enums\TipoPeriodo;
use App\Modules\Resgate\Contracts\SaldoResgatavel;
use App\Modules\Resgate\Enums\EscopoCarteira;
use App\Modules\Resgate\Enums\TipoItem;
use App\Modules\Resgate\Exceptions\RegraDoResgate;
use App\Modules\Resgate\Support\FaixaDoEnte;
use App\Modules\Resgate\Support\HashEvento;
use App\Modules\Resgate\Support\ModoDemonstracao;
use App\Modules\Resgate\Support\Rastro;
use App\Modules\Resgate\Support\TrilhaDoPedido;
use DateTimeImmutable;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Pedido de resgate - Fase 3 (plano, secao 4).
 *
 *   solicitar -> RESERVADO (pontos e unidade presos)
 *     RESERVADO -> APROVADO  (CEDEC aprova; a reserva continua ate a entrega)
 *     RESERVADO -> RECUSADO  (CEDEC recusa; pontos e unidade liberados)
 *     RESERVADO -> CANCELADO (o proprio solicitante desiste; liberados)
 *     RESERVADO -> EXPIRADO  (prazo sem decisao; liberados pelo sistema)
 *
 * GARANTIAS
 *  - Saldo: lock consultivo por ente; o saldo e recalculado DENTRO da
 *    transacao, e nunca da view. Dois pedidos simultaneos nao passam do saldo.
 *  - Unidade: FOR UPDATE SKIP LOCKED + indice unico de pedido ativo por
 *    unidade: a mesma viatura nao vai para dois pedidos.
 *  - Idempotencia: chave por solicitacao; reenviar o mesmo formulario
 *    devolve o pedido ja criado.
 *  - Segregacao: quem solicitou nao decide (servico e trigger no banco).
 *  - Trilha: cada passo e um evento append-only com hash encadeado.
 *  - Demonstracao: pedido demo so usa item e pontos demo, e so com o modo
 *    ligado fora de producao; os saldos nunca se misturam.
 *
 * CUIDADO COM OCTANE: stateless; conexao resolvida por nome a cada chamada.
 */
final class PedidoResgate
{
    /**
     * Status que ocupam vaga e contam para o limite da temporada: tudo que nao
     * terminou mal. O concluido conta - o item foi de fato entregue.
     */
    private const ATIVOS = ['reservado', 'aprovado', 'termo_emitido', 'termo_assinado', 'entregue', 'contestado', 'concluido'];

    public function __construct(
        private readonly SaldoResgatavel $saldo,
        private readonly FaixaDoEnte $faixa,
        private readonly ModoDemonstracao $modoDemonstracao,
        private readonly TrilhaDoPedido $trilha,
        private readonly MovimentosDaCarteira $movimentos,
    ) {}

    public function solicitar(EscopoCarteira $escopo, int $enteId, string $codigo, string $chaveIdempotencia, int $autorId, Rastro $rastro): int
    {
        $agora = new DateTimeImmutable();
        $temporada = TipoPeriodo::Trimestre->chave($agora);
        // Fora da transacao: le o placar por outra conexao e nao depende do lock.
        $faixaFechada = $this->faixa->naTemporadaFechada($escopo, $enteId, $agora);

        return $this->conexao()->transaction(function (Connection $db) use ($escopo, $enteId, $codigo, $chaveIdempotencia, $autorId, $rastro, $agora, $temporada, $faixaFechada): int {
            $db->statement('SELECT pg_advisory_xact_lock(hashtext(?), hashtext(?))', ['resgate.ente', "{$escopo->value}:{$enteId}"]);

            $existente = $db->selectOne('SELECT id FROM resgate.pedidos WHERE chave_idempotencia = ?', [$chaveIdempotencia]);
            if ($existente !== null) {
                return (int) $existente->id;
            }

            $avaliacao = $this->checar($db, $escopo, $enteId, $codigo, $faixaFechada, $temporada, $agora, true);
            if ($avaliacao['impedimentos'] !== []) {
                throw new RegraDoResgate($avaliacao['impedimentos'][0], 'item');
            }
            $item = $avaliacao['item'];
            $unidadeId = $avaliacao['unidade_id'];
            $demonstracao = $avaliacao['demonstracao'];
            $custo = (int) $item->custo_pontos;

            $id = (int) $db->selectOne("SELECT nextval('resgate.pedidos_id_seq') AS id")->id;
            $db->insert(
                "INSERT INTO resgate.pedidos
                    (id, protocolo, ente_escopo, ente_id, item_id, item_codigo, item_versao, unidade_id, custo_pontos,
                     faixa_exigida, faixa_do_ente, temporada_referencia, status, demonstracao, chave_idempotencia,
                     solicitado_por, expira_em)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'reservado', ?, ?, ?, now() + (? || ' days')::interval)",
                [$id, sprintf('RSG-%s-%06d', $agora->format('Y'), $id), $escopo->value, $enteId, (int) $item->id, $item->codigo,
                    (int) $item->versao, $unidadeId, $custo, $item->faixa_minima, $faixaFechada['faixa'], $temporada,
                    $demonstracao, $chaveIdempotencia, $autorId, (string) (int) $item->prazo_reserva_dias],
            );

            $this->movimentos->reservar($db, $db->selectOne('SELECT * FROM resgate.pedidos WHERE id = ?', [$id]));
            if ($unidadeId !== null) {
                $db->update("UPDATE resgate.unidades SET estado = 'reservada' WHERE id = ?", [$unidadeId]);
            }
            $this->trilha->registrar($db, $id, 'solicitar', null, 'reservado', $autorId, 'resgate.solicitar', null, $rastro);

            return $id;
        });
    }

    public function decidir(int $pedidoId, bool $aprovar, string $justificativa, int $decisorId, Rastro $rastro): void
    {
        $this->conexao()->transaction(function (Connection $db) use ($pedidoId, $aprovar, $justificativa, $decisorId, $rastro): void {
            $pedido = $this->trilha->travar($db, $pedidoId, ['reservado']);
            if ((int) $pedido->solicitado_por === $decisorId) {
                throw new RegraDoResgate('Quem solicitou não pode decidir o próprio pedido.', 'pedido');
            }

            $novo = $aprovar ? 'aprovado' : 'recusado';
            $this->trilha->mudarStatus($db, $pedidoId, $novo);
            if (! $aprovar) {
                $this->movimentos->liberar($db, $pedido);
            }
            $this->trilha->registrar($db, $pedidoId, $aprovar ? 'aprovar' : 'recusar', 'reservado', $novo, $decisorId, 'resgate.aprovar', $justificativa, $rastro);
        });
    }

    public function cancelar(int $pedidoId, string $justificativa, int $autorId, Rastro $rastro): void
    {
        $this->conexao()->transaction(function (Connection $db) use ($pedidoId, $justificativa, $autorId, $rastro): void {
            $pedido = $this->trilha->travar($db, $pedidoId, ['reservado']);
            if ((int) $pedido->solicitado_por !== $autorId) {
                throw new RegraDoResgate('Só quem solicitou pode cancelar o pedido.', 'pedido');
            }

            $this->trilha->mudarStatus($db, $pedidoId, 'cancelado');
            $this->movimentos->liberar($db, $pedido);
            $this->trilha->registrar($db, $pedidoId, 'cancelar', 'reservado', 'cancelado', $autorId, 'resgate.solicitar', $justificativa, $rastro);
        });
    }

    /** Reservas vencidas sem decisao: expiram e liberam pontos e unidade. @return int quantas */
    public function expirarVencidos(): int
    {
        $ids = array_map(static fn (object $l): int => (int) $l->id, $this->conexao()->select(
            "SELECT id FROM resgate.pedidos WHERE status = 'reservado' AND expira_em < now() ORDER BY id"
        ));

        $expirados = 0;
        foreach ($ids as $id) {
            $this->conexao()->transaction(function (Connection $db) use ($id, &$expirados): void {
                $pedido = $db->selectOne("SELECT * FROM resgate.pedidos WHERE id = ? AND status = 'reservado' AND expira_em < now() FOR UPDATE SKIP LOCKED", [$id]);
                if ($pedido === null) {
                    return;
                }
                $this->trilha->mudarStatus($db, $id, 'expirado');
                $this->movimentos->liberar($db, $pedido);
                $this->trilha->registrar($db, $id, 'expirar', 'reservado', 'expirado', null, null, 'Prazo da reserva vencido sem decisão.', Rastro::doSistema('resgate:expirar-reservas'));
                $expirados++;
            });
        }

        return $expirados;
    }

    /**
     * Pedidos com o titulo do item (da versao congelada).
     *
     * @return list<array<string, mixed>>
     */
    public function listar(?EscopoCarteira $escopo = null, ?int $enteId = null, ?string $status = null): array
    {
        $filtros = [];
        $valores = [];
        if ($escopo !== null && $enteId !== null) {
            $filtros[] = 'p.ente_escopo = ? AND p.ente_id = ?';
            array_push($valores, $escopo->value, $enteId);
        }
        if ($status !== null) {
            $filtros[] = 'p.status = ?';
            $valores[] = $status;
        }
        $onde = $filtros === [] ? '' : 'WHERE ' . implode(' AND ', $filtros);

        return array_map(fn (object $l): array => $this->pedido($l), $this->conexao()->select(
            "SELECT p.*, i.titulo AS item_titulo, i.tipo AS item_tipo, u.patrimonio AS unidade_patrimonio
               FROM resgate.pedidos p
               JOIN resgate.catalogo_itens i ON i.id = p.item_id
               LEFT JOIN resgate.unidades u ON u.id = p.unidade_id
               {$onde}
              ORDER BY p.solicitado_em DESC
              LIMIT 200",
            $valores,
        ));
    }

    /** @return array{pedido: array<string, mixed>, eventos: list<array<string, mixed>>, adulterado_em: ?int, documentos: list<array<string, mixed>>, consumos: list<array<string, mixed>>}|null */
    public function detalhe(int $pedidoId): ?array
    {
        $linha = $this->conexao()->selectOne(
            'SELECT p.*, i.titulo AS item_titulo, i.tipo AS item_tipo, u.patrimonio AS unidade_patrimonio
               FROM resgate.pedidos p
               JOIN resgate.catalogo_itens i ON i.id = p.item_id
               LEFT JOIN resgate.unidades u ON u.id = p.unidade_id
              WHERE p.id = ?',
            [$pedidoId],
        );
        if ($linha === null) {
            return null;
        }

        $eventos = array_map(static fn (object $e): array => (array) $e, $this->conexao()->select(
            'SELECT pedido_id, sequencia, etapa, de_status, para_status, ator_user_id, permissao, justificativa,
                    ip_address, user_agent, ocorrido_em::text AS ocorrido_em, hash, hash_anterior
               FROM resgate.pedido_eventos WHERE pedido_id = ? ORDER BY sequencia',
            [$pedidoId],
        ));

        // De onde sairam os pontos (plano, secao 2.3): lancamentos consumidos.
        $consumos = array_map(static fn (object $c): array => (array) $c, $this->conexao()->select(
            'SELECT c.lancamento_id, c.pontos, l.competencia_em, l.modulo, t.chave_canonica
               FROM resgate.consumos c
               JOIN ranking.lancamentos l ON l.id = c.lancamento_id
               JOIN ranking.transacoes t ON t.id = l.transacao_id
              WHERE c.pedido_id = ? ORDER BY l.competencia_em, c.lancamento_id',
            [$pedidoId],
        ));

        return [
            'pedido' => $this->pedido($linha),
            'eventos' => $eventos,
            'adulterado_em' => HashEvento::verificar($eventos),
            'documentos' => app(DocumentosDoPedido::class)->listar($this->conexao(), $pedidoId),
            'consumos' => $consumos,
        ];
    }

    /**
     * Pre-avaliacao para a tela: TODOS os impedimentos de pedir o item agora,
     * sem travar nada. A solicitacao reavalia com lock; esta resposta so
     * orienta o usuario.
     *
     * @return array{impedimentos: list<string>, saldo: int, custo: int, demonstracao: bool, faixa: ?array}
     */
    public function avaliar(EscopoCarteira $escopo, int $enteId, string $codigo): array
    {
        $agora = new DateTimeImmutable();
        $faixaFechada = $this->faixa->naTemporadaFechada($escopo, $enteId, $agora);
        $r = $this->checar($this->conexao(), $escopo, $enteId, $codigo, $faixaFechada, TipoPeriodo::Trimestre->chave($agora), $agora, false);

        return [
            'impedimentos' => $r['impedimentos'],
            'saldo' => $r['saldo'],
            'custo' => $r['item'] !== null ? (int) $r['item']->custo_pontos : 0,
            'demonstracao' => $r['demonstracao'],
            'faixa' => $faixaFechada,
        ];
    }

    /**
     * Regras de elegibilidade do pedido, num lugar so. Com `$travar`, bloqueia
     * item (FOR SHARE) e unidade (FOR UPDATE SKIP LOCKED) - uso da solicitacao,
     * dentro da transacao e do lock do ente.
     *
     * @return array{impedimentos: list<string>, item: ?object, unidade_id: ?int, demonstracao: bool, saldo: int}
     */
    private function checar(Connection $db, EscopoCarteira $escopo, int $enteId, string $codigo, ?array $faixaFechada, string $temporada, DateTimeImmutable $agora, bool $travar): array
    {
        $impedimentos = [];
        $item = $db->selectOne('SELECT * FROM resgate.catalogo_itens WHERE codigo = ? AND vigente_ate IS NULL' . ($travar ? ' FOR SHARE' : ''), [strtoupper($codigo)]);
        if ($item === null) {
            return ['impedimentos' => ['Item não está vigente no catálogo.'], 'item' => null, 'unidade_id' => null, 'demonstracao' => false, 'saldo' => 0];
        }

        $demonstracao = $this->booleano($item->demonstracao);
        if ($demonstracao && ! $this->modoDemonstracao->ativo()) {
            $impedimentos[] = 'Item de demonstração não pode ser resgatado.';
        }
        if ($item->beneficiario !== $escopo->value) {
            $impedimentos[] = 'Este item não é destinado a este tipo de ente.';
        }
        if ($faixaFechada === null || ! FaixaDoEnte::alcanca($faixaFechada['faixa'], (string) $item->faixa_minima)) {
            $impedimentos[] = 'A faixa do ente na temporada fechada não libera este item.';
        }

        if ($item->limite_por_ente_temporada !== null) {
            $jaPedidos = (int) $db->selectOne(
                "SELECT count(*) AS n FROM resgate.pedidos
                  WHERE ente_escopo = ? AND ente_id = ? AND item_codigo = ? AND temporada_referencia = ? AND status IN ('" . implode("', '", self::ATIVOS) . "')",
                [$escopo->value, $enteId, $item->codigo, $temporada],
            )->n;
            if ($jaPedidos >= (int) $item->limite_por_ente_temporada) {
                $impedimentos[] = 'O ente já atingiu o limite deste item nesta temporada.';
            }
        }

        $unidadeId = null;
        if (TipoItem::from((string) $item->tipo)->individualizado()) {
            $unidade = $db->selectOne(
                "SELECT id FROM resgate.unidades
                  WHERE item_codigo = ? AND estado = 'disponivel' AND demonstracao = ?
                  ORDER BY id LIMIT 1" . ($travar ? ' FOR UPDATE SKIP LOCKED' : ''),
                [$item->codigo, $demonstracao],
            );
            if ($unidade === null) {
                $impedimentos[] = 'Não há unidade disponível deste item.';
            }
            $unidadeId = $unidade !== null ? (int) $unidade->id : null;
        } elseif ($item->quantidade !== null) {
            $ativos = (int) $db->selectOne(
                "SELECT count(*) AS n FROM resgate.pedidos WHERE item_codigo = ? AND status IN ('" . implode("', '", self::ATIVOS) . "')",
                [$item->codigo],
            )->n;
            if ($ativos >= (int) $item->quantidade) {
                $impedimentos[] = 'As vagas/unidades deste item se esgotaram.';
            }
        }

        // Na solicitacao, lido sob o lock do ente: nunca confiar em saldo anterior.
        $carteira = $this->saldo->carteira($escopo, $enteId, $agora);
        if (! $demonstracao && $carteira->saldoResgatavel() < 0) {
            $impedimentos[] = 'A carteira do ente está negativa; novos resgates ficam bloqueados até compensar.';
        }
        $custo = (int) $item->custo_pontos;
        $saldo = $carteira->saldoPara($demonstracao);
        if ($custo > 0 && $saldo < $custo) {
            $impedimentos[] = "Saldo insuficiente: o item custa {$custo} pontos e o saldo é {$saldo}.";
        }

        return ['impedimentos' => $impedimentos, 'item' => $item, 'unidade_id' => $unidadeId, 'demonstracao' => $demonstracao, 'saldo' => $saldo];
    }

    /** @return array<string, mixed> */
    private function pedido(object $l): array
    {
        return [
            'id' => (int) $l->id,
            'protocolo' => $l->protocolo,
            'ente_escopo' => $l->ente_escopo,
            'ente_id' => (int) $l->ente_id,
            'item_codigo' => $l->item_codigo,
            'item_versao' => (int) $l->item_versao,
            'item_titulo' => $l->item_titulo,
            'item_tipo' => $l->item_tipo,
            'unidade_patrimonio' => $l->unidade_patrimonio ?? null,
            'custo_pontos' => (int) $l->custo_pontos,
            'faixa_exigida' => $l->faixa_exigida,
            'faixa_do_ente' => $l->faixa_do_ente,
            'temporada_referencia' => $l->temporada_referencia,
            'status' => $l->status,
            'demonstracao' => $this->booleano($l->demonstracao),
            'solicitado_por' => (int) $l->solicitado_por,
            'solicitado_em' => $l->solicitado_em,
            'expira_em' => $l->expira_em,
            'processo_sei' => $l->processo_sei ?? null,
            'termo_documento_sei' => $l->termo_documento_sei ?? null,
            'assinado_estado_por' => isset($l->assinado_estado_por) ? (int) $l->assinado_estado_por : null,
            'assinado_municipio_por' => isset($l->assinado_municipio_por) ? (int) $l->assinado_municipio_por : null,
            'entregue_em' => $l->entregue_em ?? null,
            'concluido_em' => $l->concluido_em ?? null,
        ];
    }

    private function booleano(mixed $valor): bool
    {
        return is_bool($valor) ? $valor : in_array(strtolower(trim((string) $valor)), ['t', 'true', '1'], true);
    }

    private function conexao(): Connection
    {
        return DB::connection((string) config('resgate.conexao'));
    }
}
