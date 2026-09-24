<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\DTOs\ContextoInstitucional;
use App\Modules\Ranking\Enums\EscopoPlacar;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Sincroniza ranking.vinculos (usuario -> orgao -> municipio) com o cadastro
 * operacional e garante os participantes dos periodos correntes.
 *
 * POR QUE EXISTE
 * O InstitutionalContextResolver so credita um fato se houver vinculo cuja
 * janela [valido_de, valido_ate) cubra o instante do fato. Sem sincronizacao,
 * quem muda de orgao continua com o vinculo antigo aberto (credito vai para o
 * orgao errado) e quem entra depois do bootstrap nao tem vinculo nenhum (fato
 * cai em apuracao).
 *
 * NUNCA RETROAGIR
 * valido_de e SEMPRE o instante desta sincronizacao. O cadastro operacional so
 * sabe o orgao ATUAL; copia-lo para tras inventaria um vinculo historico que
 * ninguem documentou. Por isso a evidencia 'comprovado' vale daqui para frente:
 * o que ela afirma e "neste instante o cadastro dizia isto". Fato anterior ao
 * primeiro vinculo continua em apuracao, que e o comportamento correto.
 *
 * TROCA DE ORGAO SEM BURACO NEM SOBREPOSICAO
 * O vinculo antigo fecha com valido_ate = T e o novo abre com valido_de = T,
 * mesmo T. Como a janela tem fim exclusivo, o instante T pertence so ao novo:
 * nenhum fato fica sem vinculo e nenhum fato tem dois vinculos (o que o
 * resolver trataria como ambiguidade e jogaria em apuracao).
 *
 * PLANEJAR x EXECUTAR
 * planejar() e planejarParticipantes() sao funcoes puras: recebem o estado das
 * duas bases e devolvem as acoes. Isso permite testar todas as regras sem banco
 * e faz o --dry-run mostrar exatamente o que a execucao faria, porque os dois
 * modos compartilham o mesmo plano.
 *
 * IDEMPOTENCIA
 * Rodar duas vezes seguidas produz zero acoes na segunda: o que foi aberto vira
 * "inalterado" e o que foi fechado nao esta mais entre os abertos. Abertura usa
 * insertOrIgnore contra uq_ranking_vinculos e fechamento so atinge linha ainda
 * aberta (valido_ate IS NULL), entao repetir um lote tambem nao tem efeito.
 *
 * CONEXOES
 * Recebe o NOME das conexoes, nunca ConnectionInterface por autowiring: o alias
 * de core resolveria para a conexao default, que e a base operacional, e a
 * escrita iria parar no lugar errado sem erro nenhum.
 *
 * OCTANE: sem estado de instancia alem de configuracao readonly.
 */
final class SincronizarVinculos
{
    public const ACAO_ABRIR = 'abrir';

    public const ACAO_FECHAR = 'fechar';

    public const ACAO_INALTERADOS = 'inalterados';

    /** Mesmo formato do RankingModel::$dateFormat, com offset explicito. */
    private const FORMATO_INSTANTE = 'Y-m-d H:i:sP';

    /**
     * Chave do advisory lock. Duas sincronizacoes simultaneas abririam, cada
     * uma, um vinculo para o mesmo usuario em instantes diferentes - a UNIQUE
     * nao pega porque valido_de difere - e o resolver passaria a ver dois
     * comprovados abertos, ou seja, apuracao.
     */
    private const CHAVE_LOCK = 'ranking:sincronizar-vinculos';

    public function __construct(
        private readonly PeriodoService $periodos,
        private readonly string $conexao = 'ranking',
        private readonly string $conexaoOrigem = 'ranking_source_ro',
        private readonly int $tamanhoLote = 500,
    ) {}

    /**
     * Executa (ou simula, com $seco) a sincronizacao completa.
     *
     * @return array{
     *     instante: string,
     *     origem: int,
     *     abertos: int,
     *     abrir: int,
     *     fechar: int,
     *     inalterados: int,
     *     participantes: list<array{chave: string, escopo: string, entidades: int, novos: int}>
     * }
     */
    public function executar(bool $seco, ?DateTimeImmutable $agora = null): array
    {
        // Segundos inteiros: fechamento e abertura precisam do MESMO valor
        // gravado, e microssegundo truncado de forma diferente em cada lado
        // abriria um buraco (ou sobreposicao) de fracao de segundo.
        $agora = ($agora ?? new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('UTC'));
        $agora = $agora->setTime((int) $agora->format('H'), (int) $agora->format('i'), (int) $agora->format('s'));

        if ($seco) {
            return $this->sincronizar(true, $agora);
        }

        $this->adquirirLock();

        try {
            return $this->sincronizar(false, $agora);
        } finally {
            $this->liberarLock();
        }
    }

    /**
     * Decide as acoes sobre os vinculos. Funcao pura.
     *
     * Um usuario pode ter mais de um vinculo aberto (bootstrap A/B). Mantem-se
     * no maximo UM que coincide com a origem; os demais fecham, porque a origem
     * so reconhece um orgao principal e manter o outro aberto perpetuaria a
     * ambiguidade que o resolver resolve jogando o fato em apuracao.
     *
     * @param  array<int, array{orgao_id: int, municipio_id: ?int}>  $origem  user_id => orgao atual, so usuarios ativos com orgao
     * @param  list<array{id: int, user_id: int, orgao_id: int, municipio_id: ?int, evidencia: string}>  $abertos  vinculos com valido_ate nulo
     * @return array{
     *     abrir: list<array{user_id: int, orgao_id: int, municipio_id: ?int, valido_de: string, evidencia: string}>,
     *     fechar: list<array{id: int, user_id: int, valido_ate: string}>,
     *     inalterados: list<array{id: int, user_id: int, orgao_id: int, municipio_id: ?int, evidencia: string}>
     * }
     */
    public function planejar(array $origem, array $abertos, DateTimeImmutable $agora): array
    {
        $instante = $this->instante($agora);

        $abertosPorUsuario = [];
        foreach ($abertos as $vinculo) {
            $abertosPorUsuario[(int) $vinculo['user_id']][] = $vinculo;
        }

        $plano = [self::ACAO_ABRIR => [], self::ACAO_FECHAR => [], self::ACAO_INALTERADOS => []];

        // Todo usuario que aparece em qualquer um dos lados. Quem so aparece
        // nos abertos ficou inativo, perdeu o orgao ou foi removido: fecha.
        $usuarios = array_unique(array_merge(array_keys($origem), array_keys($abertosPorUsuario)));
        sort($usuarios);

        foreach ($usuarios as $userId) {
            $alvo = $origem[$userId] ?? null;
            $mantido = false;

            foreach ($abertosPorUsuario[$userId] ?? [] as $vinculo) {
                if (! $mantido && $alvo !== null && $this->mesmoVinculo($vinculo, $alvo)) {
                    $plano[self::ACAO_INALTERADOS][] = $vinculo;
                    $mantido = true;

                    continue;
                }

                $plano[self::ACAO_FECHAR][] = [
                    'id' => (int) $vinculo['id'],
                    'user_id' => (int) $userId,
                    'valido_ate' => $instante,
                ];
            }

            if ($alvo !== null && ! $mantido) {
                $plano[self::ACAO_ABRIR][] = [
                    'user_id' => (int) $userId,
                    'orgao_id' => (int) $alvo['orgao_id'],
                    'municipio_id' => $alvo['municipio_id'] === null ? null : (int) $alvo['municipio_id'],
                    'valido_de' => $instante,
                    'evidencia' => ContextoInstitucional::EVIDENCIA_COMPROVADO,
                ];
            }
        }

        return $plano;
    }

    /**
     * Entidades que devem estar na base de comparacao, a partir dos vinculos
     * que ficam abertos apos o plano. Funcao pura.
     *
     * Vinculo em apuracao nao gera participante: ele nao sustenta credito, e o
     * schema documenta que apuracao fica fora da classificacao. Quem teve o
     * vinculo fechado simplesmente nao e adicionado - nada e removido, porque
     * participante de periodo ja registrado e historico.
     *
     * @param  list<array{orgao_id: int, municipio_id: ?int, evidencia: string, user_id: int}>  $vigentes
     * @return array<string, list<int>> escopo => ids distintos e ordenados
     */
    public function planejarParticipantes(array $vigentes): array
    {
        $entidades = [
            EscopoPlacar::Usuario->value => [],
            EscopoPlacar::Orgao->value => [],
            EscopoPlacar::Municipio->value => [],
        ];

        foreach ($vigentes as $vinculo) {
            if ($vinculo['evidencia'] === ContextoInstitucional::EVIDENCIA_EM_APURACAO) {
                continue;
            }

            $entidades[EscopoPlacar::Usuario->value][(int) $vinculo['user_id']] = true;
            $entidades[EscopoPlacar::Orgao->value][(int) $vinculo['orgao_id']] = true;

            // Orgao estadual sem municipio nao gera participante municipal.
            if ($vinculo['municipio_id'] !== null) {
                $entidades[EscopoPlacar::Municipio->value][(int) $vinculo['municipio_id']] = true;
            }
        }

        return array_map(static function (array $ids): array {
            $lista = array_keys($ids);
            sort($lista);

            return $lista;
        }, $entidades);
    }

    /**
     * Vinculos que ficam abertos depois de aplicado o plano.
     *
     * @param  array{abrir: list<array<string, mixed>>, inalterados: list<array<string, mixed>>}  $plano
     * @return list<array<string, mixed>>
     */
    public function vigentesApos(array $plano): array
    {
        return array_merge($plano[self::ACAO_INALTERADOS], $plano[self::ACAO_ABRIR]);
    }

    private function sincronizar(bool $seco, DateTimeImmutable $agora): array
    {
        $origem = $this->lerOrigem();
        $abertos = $this->lerAbertos();

        $plano = $this->planejar($origem, $abertos, $agora);

        if (! $seco) {
            $this->aplicar($plano);
        }

        $participantes = $this->garantirParticipantes(
            $this->planejarParticipantes($this->vigentesApos($plano)),
            $agora,
            $seco,
        );

        return [
            'instante' => $this->instante($agora),
            'origem' => count($origem),
            'abertos' => count($abertos),
            'abrir' => count($plano[self::ACAO_ABRIR]),
            'fechar' => count($plano[self::ACAO_FECHAR]),
            'inalterados' => count($plano[self::ACAO_INALTERADOS]),
            'participantes' => $participantes,
        ];
    }

    /**
     * Estado atual do cadastro: usuarios ativos, nao removidos, cujo orgao
     * principal existe e nao foi removido. JOIN (e nao LEFT JOIN) de proposito:
     * orgao inexistente com municipio nulo seria lido pelo resolver como
     * "orgao estadual comprovado", credito para um orgao que nao existe.
     *
     * @return array<int, array{orgao_id: int, municipio_id: ?int}>
     */
    private function lerOrigem(): array
    {
        $origem = [];

        $linhas = $this->origem()->table('users as u')
            ->join('compdec_orgaos as o', 'o.id', '=', 'u.orgao_principal_id')
            ->select(['u.id as user_id', 'u.orgao_principal_id as orgao_id', 'o.municipio_id'])
            ->where('u.active', true)
            ->whereNull('u.deleted_at')
            ->whereNull('o.deleted_at')
            ->lazyById(1000, 'u.id', 'user_id');

        foreach ($linhas as $linha) {
            $origem[(int) $linha->user_id] = [
                'orgao_id' => (int) $linha->orgao_id,
                'municipio_id' => $linha->municipio_id === null ? null : (int) $linha->municipio_id,
            ];
        }

        return $origem;
    }

    /**
     * @return list<array{id: int, user_id: int, orgao_id: int, municipio_id: ?int, evidencia: string}>
     */
    private function lerAbertos(): array
    {
        $abertos = [];

        $linhas = $this->ranking()->table('ranking.vinculos')
            ->select(['id', 'user_id', 'orgao_id', 'municipio_id', 'evidencia'])
            ->whereNull('valido_ate')
            ->lazyById(1000, 'id');

        foreach ($linhas as $linha) {
            $abertos[] = [
                'id' => (int) $linha->id,
                'user_id' => (int) $linha->user_id,
                'orgao_id' => (int) $linha->orgao_id,
                'municipio_id' => $linha->municipio_id === null ? null : (int) $linha->municipio_id,
                'evidencia' => (string) $linha->evidencia,
            ];
        }

        return $abertos;
    }

    /**
     * Grava o plano em lotes de USUARIOS, uma transacao por lote. Fechamento e
     * abertura do mesmo usuario ficam sempre no mesmo lote: um commit entre os
     * dois deixaria o usuario momentaneamente sem vinculo aberto.
     */
    private function aplicar(array $plano): void
    {
        $porUsuario = [];

        foreach ($plano[self::ACAO_FECHAR] as $fechar) {
            $porUsuario[$fechar['user_id']][self::ACAO_FECHAR][] = $fechar;
        }

        foreach ($plano[self::ACAO_ABRIR] as $abrir) {
            $porUsuario[$abrir['user_id']][self::ACAO_ABRIR][] = $abrir;
        }

        foreach (array_chunk($porUsuario, $this->tamanhoLote, true) as $lote) {
            $this->ranking()->transaction(function (ConnectionInterface $conexao) use ($lote): void {
                $fechar = array_merge(...array_map(static fn (array $a): array => $a[self::ACAO_FECHAR] ?? [], array_values($lote)));
                $abrir = array_merge(...array_map(static fn (array $a): array => $a[self::ACAO_ABRIR] ?? [], array_values($lote)));

                // Todos os fechamentos da rodada tem o mesmo instante, entao um
                // UPDATE por lote basta. valido_ate IS NULL impede reescrever o
                // fim de um vinculo ja fechado se o lote for repetido.
                if ($fechar !== []) {
                    $conexao->table('ranking.vinculos')
                        ->whereIn('id', array_column($fechar, 'id'))
                        ->whereNull('valido_ate')
                        ->update(['valido_ate' => $fechar[0]['valido_ate']]);
                }

                if ($abrir !== []) {
                    $conexao->table('ranking.vinculos')->insertOrIgnore($abrir);
                }
            });
        }
    }

    /**
     * Garante as entidades nos periodos correntes (mes, ano e acumulado).
     *
     * Insercao set-based com insertOrIgnore contra uq_ranking_participantes:
     * participante existente nao e tocado, entao regiao/tipo gravados no
     * registro original ficam congelados. Aqui eles nascem nulos porque a base
     * do ranking nao tem essa informacao e buscar na operacional acoplaria o
     * placar a ela.
     *
     * Em dry-run nada e criado, nem o periodo: se ele ainda nao existe, todas
     * as entidades contam como novas.
     *
     * @param  array<string, list<int>>  $entidades
     * @return list<array{chave: string, escopo: string, entidades: int, novos: int}>
     */
    private function garantirParticipantes(array $entidades, DateTimeImmutable $agora, bool $seco): array
    {
        $resultado = [];

        // janela(agora, agora) devolve exatamente mes, ano e acumulado do
        // instante: a regra de chave e limites continua so no PeriodoService.
        foreach ($this->periodos->janela($agora, $agora) as $descricao) {
            $periodoId = $seco
                ? $this->idPeriodo($descricao['chave'])
                : (int) $this->periodos->resolverPeriodo($descricao['tipo'], $descricao['referencia'])->getKey();

            foreach ($entidades as $escopo => $ids) {
                $novos = $seco
                    ? $this->contarAusentes($periodoId, $escopo, $ids)
                    : $this->inserirParticipantes($periodoId, $escopo, $ids);

                $resultado[] = [
                    'chave' => $descricao['chave'],
                    'escopo' => $escopo,
                    'entidades' => count($ids),
                    'novos' => $novos,
                ];
            }
        }

        return $resultado;
    }

    /**
     * @param  list<int>  $ids
     */
    private function inserirParticipantes(int $periodoId, string $escopo, array $ids): int
    {
        $inseridos = 0;

        foreach (array_chunk($ids, $this->tamanhoLote) as $lote) {
            $linhas = array_map(static fn (int $id): array => [
                'periodo_id' => $periodoId,
                'escopo' => $escopo,
                'entidade_id' => $id,
                'regiao' => null,
                'tipo' => null,
                'elegivel' => true,
            ], $lote);

            $inseridos += $this->ranking()->table('ranking.participantes')->insertOrIgnore($linhas);
        }

        return $inseridos;
    }

    /**
     * @param  list<int>  $ids
     */
    private function contarAusentes(?int $periodoId, string $escopo, array $ids): int
    {
        if ($periodoId === null) {
            return count($ids);
        }

        $existentes = 0;

        foreach (array_chunk($ids, $this->tamanhoLote) as $lote) {
            $existentes += $this->ranking()->table('ranking.participantes')
                ->where('periodo_id', $periodoId)
                ->where('escopo', $escopo)
                ->whereIn('entidade_id', $lote)
                ->count();
        }

        return count($ids) - $existentes;
    }

    private function idPeriodo(string $chave): ?int
    {
        $id = $this->ranking()->table('ranking.periodos')->where('chave', $chave)->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Compara so orgao e municipio. Evidencia diferente nao reabre: trocar a
     * evidencia de um vinculo existente fecharia a janela antiga e a nova so
     * valeria daqui para frente, sem ganho de prova para o passado.
     *
     * @param  array{orgao_id: int, municipio_id: ?int}  $vinculo
     * @param  array{orgao_id: int, municipio_id: ?int}  $alvo
     */
    private function mesmoVinculo(array $vinculo, array $alvo): bool
    {
        $municipioVinculo = $vinculo['municipio_id'] === null ? null : (int) $vinculo['municipio_id'];
        $municipioAlvo = $alvo['municipio_id'] === null ? null : (int) $alvo['municipio_id'];

        return (int) $vinculo['orgao_id'] === (int) $alvo['orgao_id'] && $municipioVinculo === $municipioAlvo;
    }

    private function adquirirLock(): void
    {
        $linha = $this->ranking()->selectOne(
            'SELECT pg_try_advisory_lock(hashtext(?)) AS obtido',
            [self::CHAVE_LOCK],
        );

        if ($linha === null || ! $linha->obtido) {
            throw new RuntimeException('Outra sincronizacao de vinculos esta em andamento.');
        }
    }

    private function liberarLock(): void
    {
        $this->ranking()->selectOne('SELECT pg_advisory_unlock(hashtext(?))', [self::CHAVE_LOCK]);
    }

    /**
     * Instante gravado sempre em UTC com offset explicito: a coluna e
     * timestamptz e texto sem fuso dependeria do TimeZone da sessao.
     */
    private function instante(DateTimeImmutable $valor): string
    {
        return $valor->setTimezone(new DateTimeZone('UTC'))->format(self::FORMATO_INSTANTE);
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
