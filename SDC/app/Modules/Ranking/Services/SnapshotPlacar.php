<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\DTOs\FiltroPlacar;
use App\Modules\Ranking\Enums\EscopoPlacar;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use RuntimeException;

/**
 * CONGELAMENTO do placar: publica uma revisao imutavel de ranking.snapshots
 * com os itens classificados em ranking.snapshot_itens.
 *
 * O LIVRO E A VERDADE, O SNAPSHOT E UMA PUBLICACAO
 * ranking.lancamentos e append-only e continua sendo a fonte; ranking.saldos e
 * projecao reconstruivel; o snapshot e o placar que foi DIVULGADO num instante.
 * Por isso ele nunca e recalculado no lugar: uma correcao tardia no livro
 * publica a revisao seguinte e a anterior permanece intacta, porque o que foi
 * divulgado precisa continuar auditavel depois de corrigido.
 *
 * A POSICAO E A MESMA DA CONSULTA AO VIVO - POR CONSTRUCAO
 * As linhas nao sao recalculadas aqui com um SQL paralelo. O servico reutiliza
 * o LeaderboardQuery, que e a unica definicao do placar no modulo: mesmo
 * DENSE_RANK (posicao densa - 100, 90, 90, 80 produz 1, 2, 2, 3), mesmo
 * universo (participante elegivel sem ponto entra com zero), mesmo COALESCE.
 * Duplicar aquele SQL faria o snapshot divergir do placar no primeiro ajuste
 * feito em um dos dois lados, e a divergencia so apareceria em contestacao.
 *
 * A instancia de leitura e criada aqui, SEM cache e sobre a conexao de
 * escrita, por dois motivos que o singleton do container nao atende:
 *   - o singleton le pela conexao `ranking_read`, que pode ser replica: um
 *     snapshot fechado sobre replica atrasada congelaria um placar que ninguem
 *     viu;
 *   - o singleton tem cache de 60s: congelar pagina cacheada gravaria como
 *     oficial um placar de ate um minuto atras.
 *
 * MODULO
 * ranking.snapshots nao tem coluna de modulo: o placar congelado e o TOTAL.
 * Congelar a contribuicao isolada de um modulo exigiria outra coluna na chave
 * de unicidade, entao o recorte fica fixo em 'all' em vez de ser um parametro
 * que o schema nao consegue distinguir.
 *
 * LEITURA CONSISTENTE
 * Watermark e linhas sao lidos na MESMA transacao, elevada a REPEATABLE READ.
 * Sob READ COMMITTED cada comando enxerga um instante diferente: um lancamento
 * inserido entre a leitura do placar e a do watermark faria o snapshot gravar
 * uma marca d'agua que nao corresponde ao conteudo congelado - e a execucao
 * seguinte publicaria uma revisao nova sem que nada tivesse mudado de fato.
 *
 * CUIDADO COM OCTANE: stateless. Periodo, escopo e geracao chegam por
 * argumento; a conexao e a geracao padrao vem do construtor. Nada de request
 * fica na instancia, que sobrevive entre requests do worker.
 */
final class SnapshotPlacar
{
    /** Conexao dedicada de escrita. Nunca herda a conexao default. */
    public const CONEXAO = 'ranking';

    /** Rotulo literal da linha de total em ranking.saldos. */
    public const MODULO_TOTAL = 'all';

    /** Publicou revisao nova. */
    public const STATUS_PUBLICADO = 'publicado';

    /** Livro e conteudo identicos aos da ultima revisao: nada foi publicado. */
    public const STATUS_SEM_ALTERACAO = 'sem_alteracao';

    /** --dry-run: calculou e descartou. */
    public const STATUS_SIMULADO = 'simulado';

    /** Limite de ranking.snapshots.motivo (varchar(160)). */
    private const TAMANHO_MOTIVO = 160;

    /** Insercao em lotes: um INSERT por item transformaria o snapshot em N idas ao banco. */
    private const LOTE_INSERCAO = 500;

    public function __construct(
        private readonly ConnectionInterface $conexao,
        private readonly ?int $geracaoPadrao = null,
    ) {}

    /**
     * Congela o placar do periodo/escopo e publica a proxima revisao.
     *
     * Devolve STATUS_SEM_ALTERACAO, sem gravar, quando a ultima revisao ja
     * descreve exatamente este livro (mesmo watermark, mesma geracao e mesmo
     * conteudo item a item). Revisao existe para registrar correcao, nao para
     * contar quantas vezes o agendador rodou; `$forcar` republica assim mesmo,
     * para o caso de reemissao deliberada.
     *
     * @return array{
     *     status: string,
     *     periodo_chave: string,
     *     periodo_id: int,
     *     escopo: string,
     *     snapshot_id: ?int,
     *     revisao: int,
     *     geracao: int,
     *     ledger_watermark: int,
     *     total_itens: int,
     *     revisao_anterior: ?int,
     *     linhas: array<int, array{entidade_id:int, pontos:int, posicao:int, faixa:string}>
     * }
     */
    public function gerar(
        string $periodoChave,
        EscopoPlacar $escopo,
        bool $forcar = false,
        bool $dryRun = false,
        ?string $motivo = null,
        ?int $geracao = null,
    ): array {
        if ($geracao !== null && $geracao < 1) {
            throw new RuntimeException('A geracao da projecao comeca em 1.');
        }

        // Lock de sessao adquirido ANTES da transacao. Se fosse advisory_xact
        // dentro do REPEATABLE READ, a snapshot seria fixada antes da espera e
        // o segundo publicador nao enxergaria a revisao que acabou de commitar.
        $chaveLock = $periodoChave.'|'.$escopo->value;
        $this->conexao->selectOne(
            'SELECT pg_advisory_lock(hashtextextended(?, 0))',
            [$chaveLock],
            false,
        );

        try {
            return $this->conexao->transaction(function () use (
                $periodoChave,
                $escopo,
                $forcar,
                $dryRun,
                $motivo,
                $geracao,
            ): array {
                $this->isolarLeitura();

                $periodoId = $this->resolverPeriodo($periodoChave);
                $geracaoAlvo = $geracao ?? $this->geracaoPadrao ?? $this->geracaoAtiva();
                $watermark = $this->watermarkDoPeriodo($periodoId);
                $linhas = $this->lerPlacar($periodoChave, $escopo, $geracaoAlvo);

                $anterior = $this->ultimaRevisao($periodoId, $escopo);
                $revisaoAnterior = $anterior === null ? null : (int) $anterior->revisao;

                if (! $forcar && $this->inalterado($anterior, $watermark, $geracaoAlvo, $linhas)) {
                    return $this->resultado(
                        status: self::STATUS_SEM_ALTERACAO,
                        periodoChave: $periodoChave,
                        periodoId: $periodoId,
                        escopo: $escopo,
                        snapshotId: (int) $anterior->id,
                        revisao: (int) $anterior->revisao,
                        geracao: $geracaoAlvo,
                        watermark: $watermark,
                        revisaoAnterior: $revisaoAnterior,
                        linhas: $linhas,
                    );
                }

                if ($dryRun) {
                    // Nada e gravado, mas a revisao anunciada e a que SERIA
                    // publicada: o operador precisa ver o numero real antes de
                    // decidir, nao um placeholder.
                    return $this->resultado(
                        status: self::STATUS_SIMULADO,
                        periodoChave: $periodoChave,
                        periodoId: $periodoId,
                        escopo: $escopo,
                        snapshotId: null,
                        revisao: ($revisaoAnterior ?? 0) + 1,
                        geracao: $geracaoAlvo,
                        watermark: $watermark,
                        revisaoAnterior: $revisaoAnterior,
                        linhas: $linhas,
                    );
                }

                $cabecalho = $this->publicarRevisao($periodoId, $escopo, $geracaoAlvo, $watermark, $motivo);
                $this->gravarItens((int) $cabecalho->id, $escopo, $linhas);

                return $this->resultado(
                    status: self::STATUS_PUBLICADO,
                    periodoChave: $periodoChave,
                    periodoId: $periodoId,
                    escopo: $escopo,
                    snapshotId: (int) $cabecalho->id,
                    revisao: (int) $cabecalho->revisao,
                    geracao: $geracaoAlvo,
                    watermark: $watermark,
                    revisaoAnterior: $revisaoAnterior,
                    linhas: $linhas,
                );
            });
        } finally {
            $this->conexao->selectOne(
                'SELECT pg_advisory_unlock(hashtextextended(?, 0))',
                [$chaveLock],
                false,
            );
        }
    }

    /**
     * Maior ranking.lancamentos.id cuja competencia cai dentro do periodo.
     *
     * Recortado pelo periodo de proposito: lancamento de OUTRO mes nao pode
     * invalidar o snapshot deste, ou toda publicacao mensal viraria revisao
     * nova a cada evento do sistema. Como o livro e append-only, correcao e
     * estorno tambem entram como linha nova - e portanto com id maior -, entao
     * a marca d'agua cresce sempre que este periodo muda.
     */
    public function watermarkDoPeriodo(int $periodoId): int
    {
        $linha = $this->conexao->selectOne(
            <<<'SQL'
                SELECT COALESCE(MAX(l.id), 0) AS watermark
                  FROM ranking.periodos p
                  LEFT JOIN ranking.lancamentos l
                         ON (p.inicia_em  IS NULL OR l.competencia_em >= p.inicia_em)
                        AND (p.termina_em IS NULL OR l.competencia_em <  p.termina_em)
                 WHERE p.id = :periodo_id
                SQL,
            ['periodo_id' => $periodoId],
            false,
        );

        return $linha === null ? 0 : (int) $linha->watermark;
    }

    /**
     * Ultima revisao publicada do recorte, ou null quando nunca houve.
     */
    public function ultimaRevisao(int $periodoId, EscopoPlacar $escopo): ?object
    {
        return $this->conexao->selectOne(
            <<<'SQL'
                SELECT s.id, s.revisao, s.geracao, s.ledger_watermark
                  FROM ranking.snapshots s
                 WHERE s.periodo_id = :periodo_id
                   AND s.escopo     = :escopo
                 ORDER BY s.revisao DESC
                 LIMIT 1
                SQL,
            ['periodo_id' => $periodoId, 'escopo' => $escopo->value],
            false,
        );
    }

    /**
     * Itens de uma revisao, ordenados por entidade para comparacao estavel.
     *
     * @return array<int, array{entidade_id:int, pontos:int, posicao:int, faixa:string}>
     */
    public function itensDe(int $snapshotId): array
    {
        $registros = $this->conexao->select(
            <<<'SQL'
                SELECT i.entidade_id, i.pontos, i.posicao, i.faixa
                  FROM ranking.snapshot_itens i
                 WHERE i.snapshot_id = :snapshot_id
                 ORDER BY i.entidade_id ASC
                SQL,
            ['snapshot_id' => $snapshotId],
            false,
        );

        return array_map(
            static fn (object $r): array => [
                'entidade_id' => (int) $r->entidade_id,
                'pontos' => (int) $r->pontos,
                'posicao' => (int) $r->posicao,
                'faixa' => (string) $r->faixa,
            ],
            $registros,
        );
    }

    /**
     * Placar completo do recorte, pela MESMA consulta que a tela usa.
     *
     * @return array<int, array{entidade_id:int, pontos:int, posicao:int, faixa:string}>
     */
    private function lerPlacar(string $periodoChave, EscopoPlacar $escopo, int $geracao): array
    {
        $consulta = new LeaderboardQuery($this->conexao);
        $linhas = [];
        $pagina = 1;

        do {
            $resultado = $consulta->pagina(new FiltroPlacar(
                escopo: $escopo,
                periodoChave: $periodoChave,
                modulo: self::MODULO_TOTAL,
                pagina: $pagina,
                porPagina: FiltroPlacar::POR_PAGINA_MAXIMO,
                geracao: $geracao,
            ));

            foreach ($resultado['linhas'] as $linha) {
                $linhas[] = [
                    'entidade_id' => $linha['entidade_id'],
                    'pontos' => $linha['pontos'],
                    'posicao' => $linha['posicao'],
                    'faixa' => $linha['faixa'],
                ];
            }

            $pagina++;
        } while ($pagina <= $resultado['total_paginas']);

        return $linhas;
    }

    /**
     * Nada mudou desde a ultima revisao?
     *
     * O watermark sozinho nao basta: a projecao ranking.saldos pode ser
     * reconstruida (outra geracao, reconciliacao) sem que o livro cresca, e o
     * conteudo mudaria com a marca d'agua parada. Por isso compara os dois.
     *
     * @param  array<int, array{entidade_id:int, pontos:int, posicao:int, faixa:string}>  $linhas
     */
    private function inalterado(?object $anterior, int $watermark, int $geracao, array $linhas): bool
    {
        if ($anterior === null) {
            return false;
        }

        if ((int) $anterior->ledger_watermark !== $watermark || (int) $anterior->geracao !== $geracao) {
            return false;
        }

        return $this->assinatura($this->itensDe((int) $anterior->id)) === $this->assinatura($linhas);
    }

    /**
     * Conteudo comparavel do placar, independente da ordem de leitura.
     *
     * @param  array<int, array{entidade_id:int, pontos:int, posicao:int, faixa:string}>  $linhas
     */
    private function assinatura(array $linhas): string
    {
        $itens = array_map(
            static fn (array $l): string => implode('|', [
                $l['entidade_id'],
                $l['pontos'],
                $l['posicao'],
                $l['faixa'],
            ]),
            $linhas,
        );

        sort($itens, SORT_STRING);

        return implode("\n", $itens);
    }

    private function resolverPeriodo(string $periodoChave): int
    {
        $linha = $this->conexao->selectOne(
            'SELECT p.id FROM ranking.periodos p WHERE p.chave = :chave FOR UPDATE',
            ['chave' => $periodoChave],
            false,
        );

        if ($linha === null) {
            throw new RuntimeException("Periodo desconhecido em ranking.periodos: {$periodoChave}");
        }

        return (int) $linha->id;
    }

    private function geracaoAtiva(): int
    {
        $linha = $this->conexao->selectOne(
            'SELECT COALESCE(MAX(geracao), 1) AS geracao FROM ranking.saldos',
            [],
            false,
        );

        return $linha === null ? 1 : (int) $linha->geracao;
    }

    /**
     * Insere o cabecalho com a revisao seguinte num unico comando.
     *
     * O MAX(revisao)+1 e calculado dentro do proprio INSERT em vez de num
     * SELECT anterior: entre o SELECT e o INSERT cabe outra publicacao. Mesmo
     * assim a garantia final e uq_ranking_snapshots (periodo_id, escopo,
     * revisao) - a concorrente perde por violacao de unicidade em vez de
     * sobrescrever a revisao alheia.
     */
    private function publicarRevisao(
        int $periodoId,
        EscopoPlacar $escopo,
        int $geracao,
        int $watermark,
        ?string $motivo,
    ): object {
        $linha = $this->conexao->selectOne(
            <<<'SQL'
                INSERT INTO ranking.snapshots
                    (periodo_id, escopo, revisao, geracao, ledger_watermark, motivo)
                SELECT :periodo_id,
                       :escopo,
                       COALESCE(MAX(s.revisao), 0) + 1,
                       :geracao,
                       :watermark,
                       :motivo
                  FROM ranking.snapshots s
                 WHERE s.periodo_id = :periodo_filtro
                   AND s.escopo     = :escopo_filtro
                RETURNING id, revisao
                SQL,
            [
                'periodo_id' => $periodoId,
                'escopo' => $escopo->value,
                'geracao' => $geracao,
                'watermark' => $watermark,
                'motivo' => $motivo === null ? null : mb_substr($motivo, 0, self::TAMANHO_MOTIVO),
                'periodo_filtro' => $periodoId,
                'escopo_filtro' => $escopo->value,
            ],
            false,
        );

        if ($linha === null) {
            throw new RuntimeException('Nao foi possivel publicar a revisao do snapshot.');
        }

        return $linha;
    }

    /**
     * @param  array<int, array{entidade_id:int, pontos:int, posicao:int, faixa:string}>  $linhas
     */
    private function gravarItens(int $snapshotId, EscopoPlacar $escopo, array $linhas): void
    {
        if ($linhas === []) {
            return;
        }

        // O escopo e repetido no item de proposito: sem ele, usuario 7 e orgao
        // 7 colidiriam em uq_ranking_snapshot_itens.
        $registros = array_map(
            static fn (array $l): array => [
                'snapshot_id' => $snapshotId,
                'escopo' => $escopo->value,
                'entidade_id' => $l['entidade_id'],
                'pontos' => $l['pontos'],
                'posicao' => $l['posicao'],
                'faixa' => $l['faixa'],
            ],
            $linhas,
        );

        foreach (array_chunk($registros, self::LOTE_INSERCAO) as $lote) {
            $this->conexao->table('ranking.snapshot_itens')->insert($lote);
        }
    }

    /**
     * Eleva o isolamento para que watermark e placar descrevam o MESMO
     * instante. Só se aplica quando esta transacao e a externa: dentro de uma
     * transacao ja aberta o Postgres recusa o SET, e nesse caso o isolamento e
     * responsabilidade de quem abriu.
     */
    private function isolarLeitura(): void
    {
        if (! $this->conexao instanceof Connection || $this->conexao->transactionLevel() !== 1) {
            return;
        }

        if ($this->conexao->getDriverName() !== 'pgsql') {
            return;
        }

        $this->conexao->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    }

    /**
     * @param  array<int, array{entidade_id:int, pontos:int, posicao:int, faixa:string}>  $linhas
     * @return array{
     *     status: string, periodo_chave: string, periodo_id: int, escopo: string,
     *     snapshot_id: ?int, revisao: int, geracao: int, ledger_watermark: int,
     *     total_itens: int, revisao_anterior: ?int,
     *     linhas: array<int, array{entidade_id:int, pontos:int, posicao:int, faixa:string}>
     * }
     */
    private function resultado(
        string $status,
        string $periodoChave,
        int $periodoId,
        EscopoPlacar $escopo,
        ?int $snapshotId,
        int $revisao,
        int $geracao,
        int $watermark,
        ?int $revisaoAnterior,
        array $linhas,
    ): array {
        return [
            'status' => $status,
            'periodo_chave' => $periodoChave,
            'periodo_id' => $periodoId,
            'escopo' => $escopo->value,
            'snapshot_id' => $snapshotId,
            'revisao' => $revisao,
            'geracao' => $geracao,
            'ledger_watermark' => $watermark,
            'total_itens' => count($linhas),
            'revisao_anterior' => $revisaoAnterior,
            'linhas' => $linhas,
        ];
    }
}
