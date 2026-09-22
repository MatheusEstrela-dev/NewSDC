<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\Enums\EscopoPlacar;
use Illuminate\Database\ConnectionInterface;
use RuntimeException;

/**
 * Evolucao entre dois placares CONGELADOS: posicao anterior, posicao atual e
 * delta de pontos por entidade.
 *
 * SOMENTE SNAPSHOT, NUNCA O PLACAR AO VIVO
 * Comparar uma revisao publicada contra a consulta ao vivo produziria um
 * "delta" que muda a cada request. Os dois lados da comparacao sao sempre
 * revisoes gravadas, que nao se movem depois de publicadas.
 *
 * SEM BASE COMPARAVEL, NAO HA DELTA
 * A comparacao so existe entre snapshots do MESMO escopo e de periodos
 * compativeis (mesmo periodo em revisoes diferentes, ou periodos do mesmo
 * tipo). Nos demais casos o servico devolve `comparavel => false` com o motivo,
 * e NAO uma lista de deltas. Os casos recusados nao sao teoricos:
 *
 *   - Escopos diferentes: usuario 7 e orgao 7 sao entidades distintas que
 *     compartilham o numero. Cruzar os dois produziria "o orgao 7 subiu tres
 *     posicoes" a partir do placar de uma pessoa.
 *   - Tipos de periodo diferentes: o acumulado de um municipio nao e o ponto de
 *     partida do mes dele; a diferenca entre os dois nao e evolucao, e recorte.
 *
 * Reorganizacao administrativa (orgao fundido, municipio remanejado) tambem nao
 * pode reescrever ranking antigo: o snapshot antigo continua como foi
 * publicado, e a entidade que deixou de existir aparece como 'saiu', sem delta
 * inventado.
 *
 * ENTIDADE PRESENTE EM UM SO LADO
 * Quem entrou nao tinha posicao anterior e quem saiu nao tem posicao atual.
 * Nesses casos o delta e null, nao zero: tratar ausencia como zero ponto
 * afirmaria que a entidade estava no placar com saldo zerado, o que e outra
 * informacao.
 *
 * CUIDADO COM OCTANE: stateless. Somente a conexao vem do construtor.
 */
final class CompararSnapshots
{
    public const SITUACAO_SUBIU = 'subiu';

    public const SITUACAO_DESCEU = 'desceu';

    public const SITUACAO_MANTEVE = 'manteve';

    public const SITUACAO_ENTROU = 'entrou';

    public const SITUACAO_SAIU = 'saiu';

    public const MOTIVO_SNAPSHOT_INEXISTENTE = 'snapshot_inexistente';

    public const MOTIVO_ESCOPOS_DIFERENTES = 'escopos_diferentes';

    public const MOTIVO_PERIODOS_INCOMPATIVEIS = 'periodos_incompativeis';

    public const MOTIVO_MESMO_SNAPSHOT = 'mesmo_snapshot';

    public function __construct(
        private readonly ConnectionInterface $conexao,
    ) {}

    /**
     * Compara duas revisoes ja publicadas, identificadas pelo id.
     *
     * @return array{
     *     comparavel: bool,
     *     motivo: ?string,
     *     anterior: ?array{snapshot_id:int, periodo_id:int, periodo_chave:string, periodo_tipo:string, escopo:string, revisao:int},
     *     atual: ?array{snapshot_id:int, periodo_id:int, periodo_chave:string, periodo_tipo:string, escopo:string, revisao:int},
     *     entidades: array<int, array{
     *         entidade_id:int, situacao:string,
     *         posicao_anterior:?int, posicao_atual:?int, delta_posicao:?int,
     *         pontos_anterior:?int, pontos_atual:?int, delta_pontos:?int
     *     }>,
     *     resumo: array{subiram:int, desceram:int, mantiveram:int, entraram:int, sairam:int}
     * }
     */
    public function comparar(int $snapshotAnteriorId, int $snapshotAtualId): array
    {
        $anterior = $this->cabecalho($snapshotAnteriorId);
        $atual = $this->cabecalho($snapshotAtualId);

        if ($anterior === null || $atual === null) {
            return $this->semComparacao(self::MOTIVO_SNAPSHOT_INEXISTENTE, $anterior, $atual);
        }

        if ($anterior['snapshot_id'] === $atual['snapshot_id']) {
            return $this->semComparacao(self::MOTIVO_MESMO_SNAPSHOT, $anterior, $atual);
        }

        if ($anterior['escopo'] !== $atual['escopo']) {
            return $this->semComparacao(self::MOTIVO_ESCOPOS_DIFERENTES, $anterior, $atual);
        }

        // Mesmo periodo (revisao contra revisao) ou periodos do mesmo tipo
        // (mes contra mes). Mes contra ano ou contra acumulado nao e evolucao.
        $mesmoPeriodo = $anterior['periodo_id'] === $atual['periodo_id'];

        if (! $mesmoPeriodo && $anterior['periodo_tipo'] !== $atual['periodo_tipo']) {
            return $this->semComparacao(self::MOTIVO_PERIODOS_INCOMPATIVEIS, $anterior, $atual);
        }

        return $this->montarEvolucao($anterior, $atual);
    }

    /**
     * Compara duas revisoes do mesmo periodo/escopo pelo numero da revisao.
     *
     * @return array<string, mixed>
     */
    public function entreRevisoes(
        string $periodoChave,
        EscopoPlacar $escopo,
        int $revisaoAnterior,
        int $revisaoAtual,
    ): array {
        $anterior = $this->idDaRevisao($periodoChave, $escopo, $revisaoAnterior);
        $atual = $this->idDaRevisao($periodoChave, $escopo, $revisaoAtual);

        if ($anterior === null || $atual === null) {
            return $this->semComparacao(
                self::MOTIVO_SNAPSHOT_INEXISTENTE,
                $anterior === null ? null : $this->cabecalho($anterior),
                $atual === null ? null : $this->cabecalho($atual),
            );
        }

        return $this->comparar($anterior, $atual);
    }

    /**
     * Compara a ultima revisao publicada de dois periodos, no mesmo escopo.
     *
     * Usa sempre a revisao mais recente de cada lado: comparar contra uma
     * revisao antiga do periodo anterior mostraria uma evolucao que ja foi
     * corrigida.
     *
     * @return array<string, mixed>
     */
    public function entrePeriodos(
        string $periodoChaveAnterior,
        string $periodoChaveAtual,
        EscopoPlacar $escopo,
    ): array {
        $anterior = $this->idDaUltimaRevisao($periodoChaveAnterior, $escopo);
        $atual = $this->idDaUltimaRevisao($periodoChaveAtual, $escopo);

        if ($anterior === null || $atual === null) {
            return $this->semComparacao(
                self::MOTIVO_SNAPSHOT_INEXISTENTE,
                $anterior === null ? null : $this->cabecalho($anterior),
                $atual === null ? null : $this->cabecalho($atual),
            );
        }

        return $this->comparar($anterior, $atual);
    }

    /**
     * @param  array{snapshot_id:int, periodo_id:int, periodo_chave:string, periodo_tipo:string, escopo:string, revisao:int}  $anterior
     * @param  array{snapshot_id:int, periodo_id:int, periodo_chave:string, periodo_tipo:string, escopo:string, revisao:int}  $atual
     * @return array<string, mixed>
     */
    private function montarEvolucao(array $anterior, array $atual): array
    {
        $itensAnterior = $this->itens($anterior['snapshot_id']);
        $itensAtual = $this->itens($atual['snapshot_id']);

        $ids = array_keys($itensAnterior + $itensAtual);
        sort($ids, SORT_NUMERIC);

        $entidades = [];
        $resumo = ['subiram' => 0, 'desceram' => 0, 'mantiveram' => 0, 'entraram' => 0, 'sairam' => 0];

        foreach ($ids as $entidadeId) {
            $de = $itensAnterior[$entidadeId] ?? null;
            $para = $itensAtual[$entidadeId] ?? null;

            if ($de === null) {
                $situacao = self::SITUACAO_ENTROU;
                $resumo['entraram']++;
            } elseif ($para === null) {
                $situacao = self::SITUACAO_SAIU;
                $resumo['sairam']++;
            } else {
                // Posicao menor e melhor: 3 -> 1 e um ganho de duas posicoes,
                // por isso o delta e anterior - atual e nao o inverso.
                $delta = $de['posicao'] - $para['posicao'];
                $situacao = match (true) {
                    $delta > 0 => self::SITUACAO_SUBIU,
                    $delta < 0 => self::SITUACAO_DESCEU,
                    default => self::SITUACAO_MANTEVE,
                };
                $resumo[match ($situacao) {
                    self::SITUACAO_SUBIU => 'subiram',
                    self::SITUACAO_DESCEU => 'desceram',
                    default => 'mantiveram',
                }]++;
            }

            $entidades[] = [
                'entidade_id' => $entidadeId,
                'situacao' => $situacao,
                'posicao_anterior' => $de['posicao'] ?? null,
                'posicao_atual' => $para['posicao'] ?? null,
                'delta_posicao' => ($de === null || $para === null) ? null : $de['posicao'] - $para['posicao'],
                'pontos_anterior' => $de['pontos'] ?? null,
                'pontos_atual' => $para['pontos'] ?? null,
                'delta_pontos' => ($de === null || $para === null) ? null : $para['pontos'] - $de['pontos'],
            ];
        }

        return [
            'comparavel' => true,
            'motivo' => null,
            'anterior' => $anterior,
            'atual' => $atual,
            'entidades' => $entidades,
            'resumo' => $resumo,
        ];
    }

    /**
     * @return array<int, array{pontos:int, posicao:int, faixa:string}>
     */
    private function itens(int $snapshotId): array
    {
        $registros = $this->conexao->select(
            <<<'SQL'
                SELECT i.entidade_id, i.pontos, i.posicao, i.faixa
                  FROM ranking.snapshot_itens i
                 WHERE i.snapshot_id = :snapshot_id
                 ORDER BY i.posicao ASC, i.entidade_id ASC
                SQL,
            ['snapshot_id' => $snapshotId],
            false,
        );

        $itens = [];

        foreach ($registros as $registro) {
            $itens[(int) $registro->entidade_id] = [
                'pontos' => (int) $registro->pontos,
                'posicao' => (int) $registro->posicao,
                'faixa' => (string) $registro->faixa,
            ];
        }

        return $itens;
    }

    /**
     * @return ?array{snapshot_id:int, periodo_id:int, periodo_chave:string, periodo_tipo:string, escopo:string, revisao:int}
     */
    private function cabecalho(int $snapshotId): ?array
    {
        $linha = $this->conexao->selectOne(
            <<<'SQL'
                SELECT s.id, s.periodo_id, s.escopo, s.revisao, s.geracao,
                       s.ledger_watermark, p.chave, p.tipo
                  FROM ranking.snapshots s
                  JOIN ranking.periodos p ON p.id = s.periodo_id
                 WHERE s.id = :snapshot_id
                SQL,
            ['snapshot_id' => $snapshotId],
            false,
        );

        if ($linha === null) {
            return null;
        }

        return [
            'snapshot_id' => (int) $linha->id,
            'periodo_id' => (int) $linha->periodo_id,
            'periodo_chave' => (string) $linha->chave,
            'periodo_tipo' => (string) $linha->tipo,
            'escopo' => (string) $linha->escopo,
            'revisao' => (int) $linha->revisao,
        ];
    }

    private function idDaRevisao(string $periodoChave, EscopoPlacar $escopo, int $revisao): ?int
    {
        if ($revisao < 1) {
            throw new RuntimeException('A revisao do snapshot comeca em 1.');
        }

        $linha = $this->conexao->selectOne(
            <<<'SQL'
                SELECT s.id
                  FROM ranking.snapshots s
                  JOIN ranking.periodos p ON p.id = s.periodo_id
                 WHERE p.chave   = :chave
                   AND s.escopo  = :escopo
                   AND s.revisao = :revisao
                SQL,
            ['chave' => $periodoChave, 'escopo' => $escopo->value, 'revisao' => $revisao],
            false,
        );

        return $linha === null ? null : (int) $linha->id;
    }

    private function idDaUltimaRevisao(string $periodoChave, EscopoPlacar $escopo): ?int
    {
        $linha = $this->conexao->selectOne(
            <<<'SQL'
                SELECT s.id
                  FROM ranking.snapshots s
                  JOIN ranking.periodos p ON p.id = s.periodo_id
                 WHERE p.chave  = :chave
                   AND s.escopo = :escopo
                 ORDER BY s.revisao DESC
                 LIMIT 1
                SQL,
            ['chave' => $periodoChave, 'escopo' => $escopo->value],
            false,
        );

        return $linha === null ? null : (int) $linha->id;
    }

    /**
     * @param  ?array{snapshot_id:int, periodo_id:int, periodo_chave:string, periodo_tipo:string, escopo:string, revisao:int}  $anterior
     * @param  ?array{snapshot_id:int, periodo_id:int, periodo_chave:string, periodo_tipo:string, escopo:string, revisao:int}  $atual
     * @return array<string, mixed>
     */
    private function semComparacao(string $motivo, ?array $anterior, ?array $atual): array
    {
        return [
            'comparavel' => false,
            'motivo' => $motivo,
            'anterior' => $anterior,
            'atual' => $atual,
            'entidades' => [],
            'resumo' => ['subiram' => 0, 'desceram' => 0, 'mantiveram' => 0, 'entraram' => 0, 'sairam' => 0],
        ];
    }
}
