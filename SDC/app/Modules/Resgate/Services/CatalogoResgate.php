<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Services;

use App\Modules\Resgate\Enums\AcaoProposta;
use App\Modules\Resgate\Enums\TipoItem;
use App\Modules\Resgate\Exceptions\RegraDoResgate;
use App\Modules\Resgate\Support\Rastro;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Catalogo de premios versionado, com quatro olhos (plano, secao 3).
 *
 * PROPOR -> DECIDIR
 * Toda mudanca nasce como proposta de uma pessoa e so e publicada com a
 * aprovacao de OUTRA. O servico recusa quem decide a propria proposta, e o
 * banco recusa de novo (CHECK proposto_por <> decidido_por): as duas camadas
 * existem porque a regra e o que torna o catalogo defensavel.
 *
 * VERSAO NUNCA E EDITADA
 * Aprovar nova versao fecha a vigente e abre v+1 no MESMO instante
 * (clock_timestamp), como nas regras de pontuacao. Pedido de resgate congela
 * a versao vigente na solicitacao.
 *
 * CONCORRENCIA
 * Lock consultivo por codigo antes de ler a vigente: duas aprovacoes
 * simultaneas do mesmo codigo nao publicam duas versoes.
 *
 * CUIDADO COM OCTANE: stateless; conexao resolvida por nome a cada chamada.
 */
final class CatalogoResgate
{
    /** Campos do item que uma proposta de criar/nova versao carrega. */
    public const CAMPOS_ITEM = [
        'tipo', 'titulo', 'descricao', 'beneficiario', 'faixa_minima', 'custo_pontos',
        'quantidade', 'limite_por_ente_temporada', 'prazo_reserva_dias', 'instrumento',
        'base_normativa', 'unidade_responsavel', 'documentos_exigidos', 'demonstracao',
    ];

    public function propor(AcaoProposta $acao, string $codigo, array $dados, string $justificativa, int $autorId, Rastro $rastro): int
    {
        $codigo = $this->normalizarCodigo($codigo);
        $dados = $acao->publicaVersao() ? $this->filtrarDados($dados) : [];

        return $this->conexao()->transaction(function (Connection $db) use ($acao, $codigo, $dados, $justificativa, $autorId, $rastro): int {
            $this->travar($db, $codigo);
            $this->exigirEstado($db, $acao, $codigo);

            $pendente = $db->selectOne(
                "SELECT id FROM resgate.catalogo_propostas WHERE codigo = ? AND status = 'pendente' LIMIT 1",
                [$codigo],
            );
            if ($pendente !== null) {
                throw new RegraDoResgate("Já existe proposta pendente para {$codigo} (#{$pendente->id}). Decida-a antes de propor outra.", 'codigo');
            }

            return (int) $db->selectOne(
                'INSERT INTO resgate.catalogo_propostas
                    (codigo, acao, dados, justificativa, proposto_por, proposto_ip, proposto_user_agent, proposto_sessao, proposto_requisicao)
                 VALUES (?, ?, ?::jsonb, ?, ?, ?, ?, ?, ?) RETURNING id',
                [$codigo, $acao->value, json_encode($dados, JSON_THROW_ON_ERROR), $justificativa, $autorId,
                    $rastro->ip, $rastro->userAgent, $rastro->sessao, $rastro->requisicao],
            )->id;
        });
    }

    /** @return int|null Id da versao publicada; null quando recusada ou so encerrada. */
    public function decidir(int $propostaId, bool $aprovar, string $justificativa, int $decisorId, Rastro $rastro): ?int
    {
        return $this->conexao()->transaction(function (Connection $db) use ($propostaId, $aprovar, $justificativa, $decisorId, $rastro): ?int {
            $proposta = $db->selectOne('SELECT * FROM resgate.catalogo_propostas WHERE id = ? FOR UPDATE', [$propostaId]);
            if ($proposta === null) {
                throw new RegraDoResgate('Proposta não encontrada.');
            }
            if ($proposta->status !== 'pendente') {
                throw new RegraDoResgate('Esta proposta já foi decidida.');
            }
            if ((int) $proposta->proposto_por === $decisorId) {
                throw new RegraDoResgate('Quem propôs não pode decidir a própria proposta. A decisão precisa de outra pessoa.');
            }

            $itemId = null;
            if ($aprovar) {
                $acao = AcaoProposta::from((string) $proposta->acao);
                $this->travar($db, (string) $proposta->codigo);
                // Reavaliado na decisao: o estado pode ter mudado desde a proposta.
                $this->exigirEstado($db, $acao, (string) $proposta->codigo);
                $itemId = $this->aplicar($db, $proposta, $acao, $decisorId);
            }

            $db->update(
                'UPDATE resgate.catalogo_propostas
                    SET status = ?, decidido_por = ?, decidido_em = clock_timestamp(), decisao_justificativa = ?,
                        decidido_ip = ?, decidido_user_agent = ?, decidido_sessao = ?, decidido_requisicao = ?, item_id = ?
                  WHERE id = ?',
                [$aprovar ? 'aprovada' : 'recusada', $decisorId, $justificativa,
                    $rastro->ip, $rastro->userAgent, $rastro->sessao, $rastro->requisicao, $itemId, $propostaId],
            );

            return $itemId;
        });
    }

    /**
     * Versoes vigentes com a disponibilidade: unidades disponiveis no bem
     * permanente; quantidade (nula = sem limite) nos demais.
     *
     * @return list<array<string, mixed>>
     */
    public function vigentes(): array
    {
        $linhas = $this->conexao()->select(
            "SELECT i.*,
                    (SELECT count(*) FROM resgate.unidades u WHERE u.item_codigo = i.codigo AND u.estado = 'disponivel') AS unidades_disponiveis,
                    (SELECT count(*) FROM resgate.unidades u WHERE u.item_codigo = i.codigo) AS unidades_total
               FROM resgate.catalogo_itens i
              WHERE i.vigente_ate IS NULL
              ORDER BY i.tipo, i.custo_pontos, i.codigo"
        );

        return array_map(fn (object $linha): array => $this->item($linha), $linhas);
    }

    /** @return list<array<string, mixed>> */
    public function pendentes(): array
    {
        return array_map(static function (object $p): array {
            return [
                'id' => (int) $p->id,
                'codigo' => $p->codigo,
                'acao' => $p->acao,
                'dados' => json_decode((string) $p->dados, true, 512, JSON_THROW_ON_ERROR),
                'justificativa' => $p->justificativa,
                'proposto_por' => (int) $p->proposto_por,
                'proposto_em' => $p->proposto_em,
            ];
        }, $this->conexao()->select(
            "SELECT * FROM resgate.catalogo_propostas WHERE status = 'pendente' ORDER BY proposto_em"
        ));
    }

    /** @return list<array<string, mixed>> Versoes do codigo, da mais nova para a mais antiga. */
    public function historico(string $codigo): array
    {
        return array_map(fn (object $linha): array => $this->item($linha), $this->conexao()->select(
            'SELECT * FROM resgate.catalogo_itens WHERE codigo = ? ORDER BY versao DESC',
            [$this->normalizarCodigo($codigo)],
        ));
    }

    /** Unidade de bem permanente; so para item vigente do tipo individualizado. */
    public function cadastrarUnidade(string $codigo, array $dados, int $autorId, Rastro $rastro): int
    {
        $codigo = $this->normalizarCodigo($codigo);

        return $this->conexao()->transaction(function (Connection $db) use ($codigo, $dados, $autorId, $rastro): int {
            $item = $db->selectOne('SELECT tipo, demonstracao FROM resgate.catalogo_itens WHERE codigo = ? AND vigente_ate IS NULL', [$codigo]);
            if ($item === null || ! TipoItem::from((string) $item->tipo)->individualizado()) {
                throw new RegraDoResgate('Unidades só se cadastram em bem permanente vigente.', 'codigo');
            }
            $patrimonio = trim((string) ($dados['patrimonio'] ?? ''));
            if ($db->selectOne('SELECT 1 FROM resgate.unidades WHERE patrimonio = ?', [$patrimonio]) !== null) {
                throw new RegraDoResgate("O patrimônio {$patrimonio} já está cadastrado.", 'patrimonio');
            }

            return (int) $db->selectOne(
                'INSERT INTO resgate.unidades
                    (item_codigo, patrimonio, descricao, placa, renavam, chassi, numero_serie, inventario_equipamento_id,
                     demonstracao, cadastrado_por, cadastrado_ip, cadastrado_user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id',
                [$codigo, $patrimonio, (string) $dados['descricao'], $dados['placa'] ?? null, $dados['renavam'] ?? null,
                    $dados['chassi'] ?? null, $dados['numero_serie'] ?? null, $dados['inventario_equipamento_id'] ?? null,
                    $this->booleano($item->demonstracao), $autorId, $rastro->ip, $rastro->userAgent],
            )->id;
        });
    }

    private function aplicar(Connection $db, object $proposta, AcaoProposta $acao, int $decisorId): ?int
    {
        $codigo = (string) $proposta->codigo;
        $agora = (string) $db->selectOne('SELECT clock_timestamp()::text AS agora')->agora;

        if ($acao !== AcaoProposta::Criar) {
            $db->update(
                'UPDATE resgate.catalogo_itens SET vigente_ate = ?::timestamptz WHERE codigo = ? AND vigente_ate IS NULL',
                [$agora, $codigo],
            );
        }
        if (! $acao->publicaVersao()) {
            return null;
        }

        $dados = json_decode((string) $proposta->dados, true, 512, JSON_THROW_ON_ERROR);
        $versao = (int) $db->selectOne('SELECT COALESCE(MAX(versao), 0) + 1 AS v FROM resgate.catalogo_itens WHERE codigo = ?', [$codigo])->v;

        return (int) $db->selectOne(
            'INSERT INTO resgate.catalogo_itens
                (codigo, versao, tipo, titulo, descricao, beneficiario, faixa_minima, custo_pontos, quantidade,
                 limite_por_ente_temporada, prazo_reserva_dias, instrumento, base_normativa, unidade_responsavel,
                 documentos_exigidos, demonstracao, vigente_de, proposta_id, proposto_por, aprovado_por)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?::jsonb, ?, ?::timestamptz, ?, ?, ?) RETURNING id',
            [$codigo, $versao, $dados['tipo'], $dados['titulo'], $dados['descricao'], $dados['beneficiario'],
                $dados['faixa_minima'], (int) $dados['custo_pontos'], $dados['quantidade'] ?? null,
                $dados['limite_por_ente_temporada'] ?? null, (int) ($dados['prazo_reserva_dias'] ?? 30),
                $dados['instrumento'], $dados['base_normativa'], $dados['unidade_responsavel'],
                json_encode(array_values($dados['documentos_exigidos'] ?? []), JSON_THROW_ON_ERROR),
                (bool) ($dados['demonstracao'] ?? false), $agora, (int) $proposta->id, (int) $proposta->proposto_por, $decisorId],
        )->id;
    }

    /** Criar exige codigo livre; nova versao e encerrar exigem item vigente. */
    private function exigirEstado(Connection $db, AcaoProposta $acao, string $codigo): void
    {
        $vigente = $db->selectOne('SELECT id FROM resgate.catalogo_itens WHERE codigo = ? AND vigente_ate IS NULL', [$codigo]);

        if ($acao === AcaoProposta::Criar && $vigente !== null) {
            throw new RegraDoResgate("O código {$codigo} já tem item vigente; proponha uma nova versão.", 'codigo');
        }
        if ($acao !== AcaoProposta::Criar && $vigente === null) {
            throw new RegraDoResgate("O código {$codigo} não tem item vigente.", 'codigo');
        }
    }

    private function travar(Connection $db, string $codigo): void
    {
        $db->statement('SELECT pg_advisory_xact_lock(hashtext(?), hashtext(?))', ['resgate.catalogo', $codigo]);
    }

    private function normalizarCodigo(string $codigo): string
    {
        $codigo = strtoupper(trim($codigo));
        if (! preg_match('/^[A-Z0-9-]{3,40}$/', $codigo)) {
            throw new RegraDoResgate('Código inválido: use de 3 a 40 letras maiúsculas, números ou hífen.', 'codigo');
        }

        return $codigo;
    }

    /** @return array<string, mixed> */
    private function filtrarDados(array $dados): array
    {
        return array_intersect_key($dados, array_flip(self::CAMPOS_ITEM));
    }

    /** @return array<string, mixed> */
    private function item(object $linha): array
    {
        $tipo = TipoItem::from((string) $linha->tipo);

        return [
            'id' => (int) $linha->id,
            'codigo' => $linha->codigo,
            'versao' => (int) $linha->versao,
            'tipo' => $tipo->value,
            'titulo' => $linha->titulo,
            'descricao' => $linha->descricao,
            'beneficiario' => $linha->beneficiario,
            'faixa_minima' => $linha->faixa_minima,
            'custo_pontos' => (int) $linha->custo_pontos,
            'quantidade' => $linha->quantidade !== null ? (int) $linha->quantidade : null,
            'limite_por_ente_temporada' => $linha->limite_por_ente_temporada !== null ? (int) $linha->limite_por_ente_temporada : null,
            'prazo_reserva_dias' => (int) $linha->prazo_reserva_dias,
            'instrumento' => $linha->instrumento,
            'base_normativa' => $linha->base_normativa,
            'unidade_responsavel' => $linha->unidade_responsavel,
            'documentos_exigidos' => json_decode((string) $linha->documentos_exigidos, true) ?? [],
            'demonstracao' => $this->booleano($linha->demonstracao),
            'vigente_de' => $linha->vigente_de,
            'vigente_ate' => $linha->vigente_ate,
            'proposto_por' => (int) $linha->proposto_por,
            'aprovado_por' => (int) $linha->aprovado_por,
            // Individualizado: disponivel = unidades livres. Demais: quantidade.
            'disponivel' => $tipo->individualizado()
                ? (int) ($linha->unidades_disponiveis ?? 0)
                : ($linha->quantidade !== null ? (int) $linha->quantidade : null),
            'unidades_total' => $tipo->individualizado() ? (int) ($linha->unidades_total ?? 0) : null,
        ];
    }

    /** Booleano do PostgreSQL chega como bool, 't'/'f' ou '1'/'0'. */
    private function booleano(mixed $valor): bool
    {
        return is_bool($valor) ? $valor : in_array(strtolower(trim((string) $valor)), ['t', 'true', '1'], true);
    }

    private function conexao(): Connection
    {
        return DB::connection((string) config('resgate.conexao'));
    }
}
