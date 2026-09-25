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
    private const ATIVOS = ['reservado', 'aprovado'];

    public function __construct(
        private readonly SaldoResgatavel $saldo,
        private readonly FaixaDoEnte $faixa,
        private readonly ModoDemonstracao $modoDemonstracao,
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

            if ($custo > 0) {
                $this->movimentar($db, $escopo, $enteId, 'reserva', -$custo, $id, $demonstracao);
            }
            if ($unidadeId !== null) {
                $db->update("UPDATE resgate.unidades SET estado = 'reservada' WHERE id = ?", [$unidadeId]);
            }
            $this->registrarEvento($db, $id, 'solicitar', null, 'reservado', $autorId, 'resgate.solicitar', null, $rastro);

            return $id;
        });
    }

    public function decidir(int $pedidoId, bool $aprovar, string $justificativa, int $decisorId, Rastro $rastro): void
    {
        $this->conexao()->transaction(function (Connection $db) use ($pedidoId, $aprovar, $justificativa, $decisorId, $rastro): void {
            $pedido = $this->pedidoReservado($db, $pedidoId);
            if ((int) $pedido->solicitado_por === $decisorId) {
                throw new RegraDoResgate('Quem solicitou não pode decidir o próprio pedido.', 'pedido');
            }

            $novo = $aprovar ? 'aprovado' : 'recusado';
            $db->update('UPDATE resgate.pedidos SET status = ?, atualizado_em = now() WHERE id = ?', [$novo, $pedidoId]);
            if (! $aprovar) {
                $this->liberar($db, $pedido);
            }
            $this->registrarEvento($db, $pedidoId, $aprovar ? 'aprovar' : 'recusar', 'reservado', $novo, $decisorId, 'resgate.aprovar', $justificativa, $rastro);
        });
    }

    public function cancelar(int $pedidoId, string $justificativa, int $autorId, Rastro $rastro): void
    {
        $this->conexao()->transaction(function (Connection $db) use ($pedidoId, $justificativa, $autorId, $rastro): void {
            $pedido = $this->pedidoReservado($db, $pedidoId);
            if ((int) $pedido->solicitado_por !== $autorId) {
                throw new RegraDoResgate('Só quem solicitou pode cancelar o pedido.', 'pedido');
            }

            $db->update("UPDATE resgate.pedidos SET status = 'cancelado', atualizado_em = now() WHERE id = ?", [$pedidoId]);
            $this->liberar($db, $pedido);
            $this->registrarEvento($db, $pedidoId, 'cancelar', 'reservado', 'cancelado', $autorId, 'resgate.solicitar', $justificativa, $rastro);
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
                $db->update("UPDATE resgate.pedidos SET status = 'expirado', atualizado_em = now() WHERE id = ?", [$id]);
                $this->liberar($db, $pedido);
                $this->registrarEvento($db, $id, 'expirar', 'reservado', 'expirado', null, null, 'Prazo da reserva vencido sem decisão.', Rastro::doSistema('resgate:expirar-reservas'));
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

    /** @return array{pedido: array<string, mixed>, eventos: list<array<string, mixed>>, adulterado_em: ?int}|null */
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

        return ['pedido' => $this->pedido($linha), 'eventos' => $eventos, 'adulterado_em' => HashEvento::verificar($eventos)];
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
                  WHERE ente_escopo = ? AND ente_id = ? AND item_codigo = ? AND temporada_referencia = ? AND status IN ('reservado', 'aprovado')",
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
                "SELECT count(*) AS n FROM resgate.pedidos WHERE item_codigo = ? AND status IN ('reservado', 'aprovado')",
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

    private function pedidoReservado(Connection $db, int $pedidoId): object
    {
        $pedido = $db->selectOne('SELECT * FROM resgate.pedidos WHERE id = ? FOR UPDATE', [$pedidoId]);
        if ($pedido === null) {
            throw new RegraDoResgate('Pedido não encontrado.', 'pedido');
        }
        if ($pedido->status !== 'reservado') {
            throw new RegraDoResgate('Este pedido não está mais reservado.', 'pedido');
        }

        return $pedido;
    }

    /** Devolve pontos (liberacao) e unidade. Idempotente pela chave do movimento. */
    private function liberar(Connection $db, object $pedido): void
    {
        $custo = (int) $pedido->custo_pontos;
        if ($custo > 0) {
            $this->movimentar($db, EscopoCarteira::from((string) $pedido->ente_escopo), (int) $pedido->ente_id, 'liberacao', $custo, (int) $pedido->id, $this->booleano($pedido->demonstracao));
        }
        if ($pedido->unidade_id !== null) {
            $db->update("UPDATE resgate.unidades SET estado = 'disponivel' WHERE id = ? AND estado = 'reservada'", [(int) $pedido->unidade_id]);
        }
    }

    private function movimentar(Connection $db, EscopoCarteira $escopo, int $enteId, string $tipo, int $pontos, int $pedidoId, bool $demonstracao): void
    {
        $db->insert(
            'INSERT INTO resgate.movimentos (ente_escopo, ente_id, tipo, pontos, pedido_id, demonstracao, chave)
             VALUES (?, ?, ?, ?, ?, ?, ?) ON CONFLICT (chave) DO NOTHING',
            [$escopo->value, $enteId, $tipo, $pontos, $pedidoId, $demonstracao, "pedido:{$pedidoId}:{$tipo}"],
        );
    }

    private function registrarEvento(Connection $db, int $pedidoId, string $etapa, ?string $de, string $para, ?int $atorId, ?string $permissao, ?string $justificativa, Rastro $rastro): void
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
