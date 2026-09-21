<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\DTOs\FiltroPlacar;
use App\Modules\Ranking\Enums\FaixaRanking;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\ConnectionInterface;

/**
 * Consulta paginada do placar. CAMINHO DE LEITURA, somente leitura.
 *
 * LE EXCLUSIVAMENTE A PROJECAO
 * A consulta toca apenas ranking.saldos, ranking.participantes e
 * ranking.periodos, todas na database independente sdc_ranking. Nao ha JOIN
 * nem subconsulta contra a base operacional `sdc` - nem para buscar nome de
 * usuario. O placar precisa responder com o coletor de eventos parado e com a
 * base operacional indisponivel; nome e demais rotulos sao hidratados depois,
 * pela borda, a partir dos ids devolvidos aqui.
 *
 * POSICAO DENSA
 * DENSE_RANK() OVER (ORDER BY pontos DESC): 100, 90, 90, 80 produz 1, 2, 2, 3.
 * Com RANK() sairia 1, 2, 2, 4, que e o comportamento errado - dois empatados
 * em segundo nao consomem o terceiro lugar.
 *
 * ESTABILIDADE DE PAGINACAO x POSICAO
 * A ordenacao final e (pontos DESC, entidade_id ASC). O entidade_id entra
 * SOMENTE como desempate deterministico de ordenacao: sem ele o Postgres pode
 * devolver empatados em ordem diferente a cada pagina e a mesma linha
 * apareceria em duas paginas enquanto outra sumiria. Ele NAO participa do
 * ORDER BY da janela do DENSE_RANK, entao nunca decide posicao: empatados
 * continuam compartilhando a mesma posicao.
 *
 * PARTICIPANTE SEM PONTO
 * O universo de linhas e a uniao dos participantes elegiveis do periodo com as
 * entidades que ja tem saldo, com LEFT JOIN no saldo e COALESCE para zero.
 * Participante elegivel que ainda nao pontuou aparece no placar, na ultima
 * posicao - o placar e base de comparacao, nao lista de quem ja pontuou.
 * Participante marcado como inelegivel fica fora, mesmo que tenha saldo.
 *
 * OCTANE
 * Este service e singleton e sobrevive entre requests do worker. Nenhuma
 * propriedade de instancia guarda estado de request: escopo, periodo, modulo,
 * geracao e pagina chegam sempre por argumento, dentro do FiltroPlacar. As
 * dependencias do construtor (conexao, cache, TTL) sao infraestrutura, iguais
 * para todos os requests.
 *
 * CACHE E AUTORIZACAO
 * A chave de cache e formada por escopo, periodo, modulo, geracao e pagina - e
 * por nada mais. Identidade e autorizacao do solicitante NUNCA entram na chave
 * nem no filtro: autorizacao se resolve na borda, antes de chegar aqui, e nao
 * por acerto de chave de cache. Se o usuario pudesse participar da chave, uma
 * entrada gravada para quem pode ver seria reaproveitada por quem nao pode
 * (ou, no sentido inverso, cada usuario invalidaria o cache do proximo).
 */
class LeaderboardQuery
{
    private const PREFIXO_CACHE = 'ranking:placar';

    public function __construct(
        private readonly ConnectionInterface $conexao,
        private readonly ?CacheRepository $cache = null,
        private readonly int $cacheSegundos = 0,
    ) {}

    /**
     * Uma pagina do placar, ja com posicao densa e faixa.
     *
     * @return array{
     *     linhas: array<int, array{entidade_id:int, pontos:int, posicao:int, faixa:string, faixa_label:string}>,
     *     pagina: int, por_pagina: int, total: int, total_paginas: int
     * }
     */
    public function pagina(FiltroPlacar $filtro): array
    {
        if ($this->cache === null || $this->cacheSegundos < 1) {
            return $this->consultarPagina($filtro);
        }

        return $this->cache->remember(
            $this->chaveCache($filtro),
            $this->cacheSegundos,
            fn (): array => $this->consultarPagina($filtro),
        );
    }

    /**
     * Posicao da entidade consultada, mesmo quando ela esta fora da pagina
     * atual - e o "voce esta em 47o" exibido junto do topo do placar.
     *
     * Devolve null quando a entidade nao pertence ao recorte (nao e
     * participante elegivel do periodo e nao tem saldo nele).
     *
     * Nao passa por cache: o resultado depende de um id especifico e a pagina
     * cacheada nao o cobre. A consulta e uma leitura indexada sobre a mesma
     * projecao, entao sai barata mesmo sem cache.
     */
    public function posicaoDe(int $entidadeId, FiltroPlacar $filtro): ?int
    {
        $sql = $this->sqlBase() . "\n" . <<<'SQL'
            SELECT posicao
              FROM placar
             WHERE entidade_id = :entidade_id
            SQL;

        $linha = $this->conexao->selectOne(
            $sql,
            $this->bindingsBase($filtro) + ['entidade_id' => $entidadeId],
        );

        return $linha === null ? null : (int) $linha->posicao;
    }

    /**
     * Chave de cache do recorte + pagina.
     *
     * Publica de proposito: quem invalida o placar apos recalculo precisa
     * montar a mesma chave. Note o que NAO esta aqui - nenhum dado do
     * solicitante.
     */
    public function chaveCache(FiltroPlacar $filtro): string
    {
        return implode(':', [
            self::PREFIXO_CACHE,
            $filtro->assinaturaRecorte(),
            'p' . $filtro->pagina,
            'n' . $filtro->porPagina,
        ]);
    }

    /**
     * @return array{
     *     linhas: array<int, array{entidade_id:int, pontos:int, posicao:int, faixa:string, faixa_label:string}>,
     *     pagina: int, por_pagina: int, total: int, total_paginas: int
     * }
     */
    private function consultarPagina(FiltroPlacar $filtro): array
    {
        // COUNT(*) OVER () e avaliado antes do LIMIT, entao o total do recorte
        // sai na mesma ida ao banco - sem uma segunda consulta que poderia
        // enxergar um placar ja atualizado e desencontrar total e linhas.
        $sql = $this->sqlBase() . "\n" . <<<'SQL'
            SELECT entidade_id,
                   pontos,
                   posicao,
                   COUNT(*) OVER () AS total_recorte
              FROM placar
             ORDER BY pontos DESC, entidade_id ASC
             LIMIT :limite OFFSET :deslocamento
            SQL;

        $registros = $this->conexao->select(
            $sql,
            $this->bindingsBase($filtro) + [
                'limite' => $filtro->porPagina,
                'deslocamento' => $filtro->offset(),
            ],
        );

        $total = $registros === [] ? 0 : (int) $registros[0]->total_recorte;

        return [
            'linhas' => array_map(
                static function (object $registro): array {
                    $pontos = (int) $registro->pontos;
                    $faixa = FaixaRanking::deSaldo($pontos);

                    return [
                        'entidade_id' => (int) $registro->entidade_id,
                        'pontos' => $pontos,
                        'posicao' => (int) $registro->posicao,
                        'faixa' => $faixa->value,
                        'faixa_label' => $faixa->label(),
                    ];
                },
                $registros,
            ),
            'pagina' => $filtro->pagina,
            'por_pagina' => $filtro->porPagina,
            'total' => $total,
            'total_paginas' => (int) ceil($total / $filtro->porPagina),
        ];
    }

    /**
     * CTEs comuns a toda leitura de placar. O select final e apenas a cauda:
     * a pagina ordena e recorta, o posicaoDe filtra por id. Manter uma unica
     * definicao do placar e o que garante que a posicao mostrada para a
     * entidade fora da pagina seja a MESMA que apareceria na pagina dela.
     *
     * Cada parametro e referenciado uma unica vez, via a CTE `parametros`:
     * repetir placeholder nomeado nao e portavel entre prepares emulados e
     * nativos do PDO.
     */
    private function sqlBase(): string
    {
        return <<<'SQL'
            WITH parametros AS (
                SELECT CAST(:periodo_chave AS varchar) AS chave,
                       CAST(:escopo        AS varchar) AS escopo,
                       CAST(:modulo        AS varchar) AS modulo,
                       CAST(:geracao       AS integer) AS geracao
            ),

            -- Periodo resolvido pela chave estavel ('mes:2026-09'), nunca por
            -- id vindo do request: a chave e o contrato publico do recorte.
            periodo AS (
                SELECT p.id
                  FROM ranking.periodos p
                  CROSS JOIN parametros pa
                 WHERE p.chave = pa.chave
            ),

            -- Projecao, ja recortada por geracao. A reconstrucao do placar
            -- grava uma geracao nova em paralelo; enquanto o ponteiro ativo
            -- nao troca, nada dela vaza para esta leitura.
            saldos_periodo AS (
                SELECT s.entidade_id,
                       s.pontos
                  FROM ranking.saldos s
                  CROSS JOIN parametros pa
                  JOIN periodo pe ON pe.id = s.periodo_id
                 WHERE s.geracao = pa.geracao
                   AND s.escopo  = pa.escopo
                   AND s.modulo  = pa.modulo
            ),

            -- escopo entra no filtro de participantes tambem: usuario 7 e
            -- orgao 7 sao entidades distintas e nao podem se misturar.
            participantes_periodo AS (
                SELECT pt.entidade_id,
                       pt.elegivel
                  FROM ranking.participantes pt
                  CROSS JOIN parametros pa
                  JOIN periodo pe ON pe.id = pt.periodo_id
                 WHERE pt.escopo = pa.escopo
            ),

            -- Universo classificavel: todo participante elegivel (pontuando ou
            -- nao) mais quem tem saldo sem estar marcado como inelegivel. A
            -- segunda metade cobre saldo cuja linha de participante ainda nao
            -- foi materializada - some do placar so quem foi explicitamente
            -- excluido.
            universo AS (
                SELECT pt.entidade_id
                  FROM participantes_periodo pt
                 WHERE pt.elegivel = true

                UNION

                SELECT sp.entidade_id
                  FROM saldos_periodo sp
                 WHERE NOT EXISTS (
                       SELECT 1
                         FROM participantes_periodo pt
                        WHERE pt.entidade_id = sp.entidade_id
                          AND pt.elegivel = false
                 )
            ),

            -- COALESCE(pontos, 0): ausencia de saldo e zero, nao exclusao.
            -- A janela do DENSE_RANK ordena SO por pontos - por isso empatados
            -- compartilham posicao e o desempate por entidade_id, aplicado
            -- apenas no ORDER BY externo, nao contamina a classificacao.
            placar AS (
                SELECT u.entidade_id,
                       COALESCE(sp.pontos, 0) AS pontos,
                       DENSE_RANK() OVER (ORDER BY COALESCE(sp.pontos, 0) DESC) AS posicao
                  FROM universo u
                  LEFT JOIN saldos_periodo sp ON sp.entidade_id = u.entidade_id
            )
            SQL;
    }

    /** @return array<string, scalar> */
    private function bindingsBase(FiltroPlacar $filtro): array
    {
        return [
            'periodo_chave' => $filtro->periodoChave,
            'escopo' => $filtro->escopo->value,
            'modulo' => $filtro->modulo,
            'geracao' => $filtro->geracao,
        ];
    }
}
