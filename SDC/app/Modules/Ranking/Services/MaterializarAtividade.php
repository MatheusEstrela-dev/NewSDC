<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\DTOs\ContextoInstitucional;
use App\Modules\Ranking\Enums\EscopoPlacar;
use App\Modules\Ranking\Enums\TipoPeriodo;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Materializa ranking.atividade (dias distintos com login por periodo) a
 * partir de audit_logs da base operacional. E o criterio de DESEMPATE do
 * placar: o LeaderboardQuery le so a base do ranking e nao pode fazer JOIN na
 * operacional, entao o uso precisa existir como copia local.
 *
 * DIAS DISTINTOS, NAO LOGINS
 * Contar login cru deixaria o empate ser comprado entrando e saindo. Um dia
 * com um login ou com cinquenta vale o mesmo: um dia ativo.
 *
 * FUSO
 * audit_logs.created_at e timestamp SEM fuso gravado pela aplicacao com
 * now() no fuso da app (America/Sao_Paulo), ou seja, ja e o relogio de
 * parede local. Por isso o dia e created_at::date direto - aplicar AT TIME
 * ZONE sobre um timestamp sem fuso converteria de novo e deslocaria 3h. Os
 * limites do periodo (UTC) sao convertidos para o mesmo relogio local antes
 * de filtrar, o que tambem mantem o filtro sargavel em idx_audit_created.
 *
 * ORGAO / MUNICIPIO
 * Soma dos dias ativos dos usuarios com vinculo ABERTO (valido_ate nulo) em
 * ranking.vinculos, fora os vinculos em apuracao - o mesmo criterio que gera
 * participante em SincronizarVinculos. Escolha consciente: usa o vinculo
 * atual e nao o historico por dia, porque o periodo e recalculado inteiro a
 * cada rodada e o desempate premia o engajamento de quem hoje compoe a
 * entidade. Soma (e nao dias distintos da entidade) de proposito: entidade
 * com mais gente usando o sistema demonstra mais uso.
 *
 * ESCRITA
 * UPSERT em lote contra uq_ranking_atividade, com atualizado_em = instante da
 * rodada; em seguida remove as linhas do periodo nao tocadas pela rodada
 * (usuario que saiu do orgao, por exemplo). Tudo numa transacao por periodo,
 * entao o leitor nunca ve o periodo pela metade.
 *
 * CONEXOES
 * Recebe o NOME das conexoes, nunca ConnectionInterface por autowiring: o alias
 * de core resolveria para a base operacional.
 *
 * OCTANE: sem estado de instancia alem de configuracao readonly.
 */
final class MaterializarAtividade
{
    private const FORMATO_INSTANTE = 'Y-m-d H:i:sP';

    /** Mesmo formato da coluna timestamp(0) sem fuso de audit_logs. */
    private const FORMATO_LOCAL = 'Y-m-d H:i:s';

    private const EVENTO_LOGIN = 'login';

    private const CHAVE_LOCK = 'ranking:materializar-atividade';

    public function __construct(
        private readonly PeriodoService $periodos,
        private readonly string $conexao = 'ranking',
        private readonly string $conexaoOrigem = 'ranking_source_ro',
        private readonly int $tamanhoLote = 500,
    ) {}

    /**
     * @return array{
     *     instante: string,
     *     periodos: list<array{chave: string, escopo: string, entidades: int, soma_dias: int, max_dias: int, removidas: int}>
     * }
     */
    public function executar(bool $seco, ?DateTimeImmutable $agora = null): array
    {
        $agora = ($agora ?? new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('UTC'));

        if ($seco) {
            return $this->materializar(true, $agora);
        }

        $this->adquirirLock();

        try {
            return $this->materializar(false, $agora);
        } finally {
            $this->liberarLock();
        }
    }

    /**
     * Agrega os dias por usuario para orgao e municipio. Funcao pura.
     *
     * @param  array<int, int>  $diasPorUsuario  user_id => dias ativos
     * @param  list<array{user_id: int, orgao_id: int, municipio_id: ?int, evidencia: string}>  $vigentes
     * @return array<string, array<int, int>> escopo => entidade_id => dias
     */
    public function agregar(array $diasPorUsuario, array $vigentes): array
    {
        $resultado = [
            EscopoPlacar::Usuario->value => $diasPorUsuario,
            EscopoPlacar::Orgao->value => [],
            EscopoPlacar::Municipio->value => [],
        ];

        // Um usuario pode ter mais de um vinculo aberto (bootstrap A/B): conta
        // uma vez por entidade, senao o mesmo uso somaria em dobro.
        $vistos = [];

        foreach ($vigentes as $vinculo) {
            if ($vinculo['evidencia'] === ContextoInstitucional::EVIDENCIA_EM_APURACAO) {
                continue;
            }

            $dias = $diasPorUsuario[$vinculo['user_id']] ?? 0;
            if ($dias === 0) {
                continue;
            }

            $alvos = [EscopoPlacar::Orgao->value => $vinculo['orgao_id']];
            if ($vinculo['municipio_id'] !== null) {
                $alvos[EscopoPlacar::Municipio->value] = $vinculo['municipio_id'];
            }

            foreach ($alvos as $escopo => $entidadeId) {
                $chave = $escopo . ':' . $entidadeId . ':' . $vinculo['user_id'];
                if (isset($vistos[$chave])) {
                    continue;
                }
                $vistos[$chave] = true;

                $resultado[$escopo][$entidadeId] = ($resultado[$escopo][$entidadeId] ?? 0) + $dias;
            }
        }

        return $resultado;
    }

    private function materializar(bool $seco, DateTimeImmutable $agora): array
    {
        $instante = $agora->format(self::FORMATO_INSTANTE);
        $vigentes = $this->lerVigentes();
        $resumo = [];

        foreach ($this->periodos->janela($agora, $agora) as $descricao) {
            $porEscopo = $this->agregar(
                $this->lerDiasPorUsuario($descricao['inicia_em'], $descricao['termina_em']),
                $vigentes,
            );

            $removidas = array_fill_keys(array_keys($porEscopo), 0);

            if (! $seco) {
                $periodoId = (int) $this->periodos
                    ->resolverPeriodo($descricao['tipo'], $descricao['referencia'])
                    ->getKey();
                $removidas = $this->gravar($periodoId, $porEscopo, $instante);
            }

            foreach ($porEscopo as $escopo => $dias) {
                $resumo[] = [
                    'chave' => $descricao['chave'],
                    'escopo' => $escopo,
                    'entidades' => count($dias),
                    'soma_dias' => array_sum($dias),
                    'max_dias' => $dias === [] ? 0 : max($dias),
                    'removidas' => $removidas[$escopo],
                ];
            }
        }

        return ['instante' => $instante, 'periodos' => $resumo];
    }

    /**
     * Leitura set-based: uma linha por usuario, ja com os dias distintos.
     *
     * @return array<int, int>
     */
    private function lerDiasPorUsuario(?DateTimeImmutable $inicio, ?DateTimeImmutable $fim): array
    {
        $consulta = $this->origem()->table('audit_logs')
            ->selectRaw('user_id, COUNT(DISTINCT created_at::date) AS dias')
            ->where('event', self::EVENTO_LOGIN)
            ->whereNotNull('user_id')
            ->groupBy('user_id');

        // Acumulado nao tem limites: le todo o historico.
        if ($inicio !== null) {
            $consulta->where('created_at', '>=', $this->relogioLocal($inicio));
        }
        if ($fim !== null) {
            $consulta->where('created_at', '<', $this->relogioLocal($fim));
        }

        $dias = [];
        foreach ($consulta->get() as $linha) {
            $dias[(int) $linha->user_id] = (int) $linha->dias;
        }

        return $dias;
    }

    /**
     * @return list<array{user_id: int, orgao_id: int, municipio_id: ?int, evidencia: string}>
     */
    private function lerVigentes(): array
    {
        $vigentes = [];

        $linhas = $this->ranking()->table('ranking.vinculos')
            ->select(['id', 'user_id', 'orgao_id', 'municipio_id', 'evidencia'])
            ->whereNull('valido_ate')
            ->lazyById(1000, 'id');

        foreach ($linhas as $linha) {
            $vigentes[] = [
                'user_id' => (int) $linha->user_id,
                'orgao_id' => (int) $linha->orgao_id,
                'municipio_id' => $linha->municipio_id === null ? null : (int) $linha->municipio_id,
                'evidencia' => (string) $linha->evidencia,
            ];
        }

        return $vigentes;
    }

    /**
     * @param  array<string, array<int, int>>  $porEscopo
     * @return array<string, int> escopo => linhas obsoletas removidas
     */
    private function gravar(int $periodoId, array $porEscopo, string $instante): array
    {
        return $this->ranking()->transaction(function (ConnectionInterface $conexao) use ($periodoId, $porEscopo, $instante): array {
            $removidas = [];

            foreach ($porEscopo as $escopo => $dias) {
                $linhas = [];
                foreach ($dias as $entidadeId => $quantidade) {
                    $linhas[] = [
                        'periodo_id' => $periodoId,
                        'escopo' => $escopo,
                        'entidade_id' => $entidadeId,
                        'dias_ativos' => $quantidade,
                        'atualizado_em' => $instante,
                    ];
                }

                foreach (array_chunk($linhas, $this->tamanhoLote) as $lote) {
                    $conexao->table('ranking.atividade')->upsert(
                        $lote,
                        ['periodo_id', 'escopo', 'entidade_id'],
                        ['dias_ativos', 'atualizado_em'],
                    );
                }

                // Linha nao tocada nesta rodada nao tem mais atividade no
                // periodo (ex.: usuario saiu do orgao) e nao pode seguir
                // desempatando com um numero velho.
                $removidas[$escopo] = $conexao->table('ranking.atividade')
                    ->where('periodo_id', $periodoId)
                    ->where('escopo', $escopo)
                    ->where('atualizado_em', '<', $instante)
                    ->delete();
            }

            return $removidas;
        });
    }

    private function relogioLocal(DateTimeImmutable $valor): string
    {
        return $valor->setTimezone(new DateTimeZone(TipoPeriodo::FUSO_CALENDARIO))->format(self::FORMATO_LOCAL);
    }

    private function adquirirLock(): void
    {
        $linha = $this->ranking()->selectOne(
            'SELECT pg_try_advisory_lock(hashtext(?)) AS obtido',
            [self::CHAVE_LOCK],
        );

        if ($linha === null || ! $linha->obtido) {
            throw new RuntimeException('Outra materializacao de atividade esta em andamento.');
        }
    }

    private function liberarLock(): void
    {
        $this->ranking()->selectOne('SELECT pg_advisory_unlock(hashtext(?))', [self::CHAVE_LOCK]);
    }

    private function ranking(): ConnectionInterface
    {
        return DB::connection($this->conexao);
    }

    private function origem(): ConnectionInterface
    {
        return DB::connection($this->conexaoOrigem);
    }
}
