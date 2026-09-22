<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Support;

use App\Modules\Ranking\Enums\DecisaoPontuacao;
use App\Modules\Ranking\Enums\EscopoPlacar;
use App\Modules\Ranking\Enums\FaixaRanking;
use App\Modules\Ranking\Services\RecordScoreTransaction;

/**
 * Agregacao do LIVRO (ranking.lancamentos) no formato da projecao
 * (ranking.saldos). Fonte unica do SQL usado pela reconstrucao e pela
 * conciliacao.
 *
 * POR QUE UM LUGAR SO
 * Reconstruir e conciliar sao a mesma pergunta feita de dois jeitos: "quanto o
 * livro diz que esta entidade tem neste recorte?". Se cada servico escrevesse o
 * proprio SELECT, a conciliacao passaria a comparar a projecao contra uma
 * segunda interpretacao do livro - e o dia em que as duas divergissem, o
 * relatorio acusaria divergencia que a reconstrucao nao corrigiria (ou pior,
 * ficaria calado sobre uma que existe).
 *
 * QUAIS LANCAMENTOS COMPOEM O SALDO
 * Apenas os de transacao cuja decisao soma ao saldo, hoje 'confirmada', mais os
 * de transacao 'estornada'. A inclusao de 'estornada' nao e excecao: o estorno
 * NAO apaga o credito, ele acrescenta um lancamento negativo ao livro apontando
 * para o credito original (ver ReverseScoreEntry). Somar os dois devolve o
 * liquido - credito 24 + estorno -24 = 0 - enquanto excluir a transacao
 * estornada apagaria tambem estorno parcial, que deixa credito de pe.
 *
 * 'pendente' fica de fora porque o credito existe no livro mas nunca foi
 * projetado: ConfirmScoreTransaction e que o promove, sem gravar lancamento
 * novo, apenas trocando a decisao. 'zero' e 'em_apuracao' nao geram lancamento.
 *
 * AS TRES EXPANSOES DE UM LANCAMENTO
 *   1. dimensao  - usuario, orgao e municipio, pulando os nulos (usuario de
 *      orgao estadual nao entra no placar municipal);
 *   2. modulo    - a linha do modulo e a linha de total 'all';
 *   3. periodo   - mes, ano e acumulado que contem a competencia.
 * Nao sao premios diferentes: sao recortes de leitura do MESMO ponto.
 */
final class LivroAgregado
{
    /** Rotulo literal da linha de total. Mesmo valor do caminho de escrita. */
    public const MODULO_TOTAL = RecordScoreTransaction::MODULO_TOTAL;

    /** Sem teto: usado quando o recorte nao tem watermark superior. */
    public const SEM_LIMITE = PHP_INT_MAX;

    /**
     * Condicao extra (interna, NUNCA texto vindo do usuario) que restringe os
     * lancamentos elegiveis ao conjunto de transacoes capturadas como pendentes
     * no inicio da reconstrucao.
     */
    public const SOMENTE_PENDENTES_CAPTURADOS = ' AND t.id IN (SELECT id FROM pg_temp.ranking_rebuild_pendentes) ';

    /**
     * Decisoes cujos lancamentos compoem o saldo.
     *
     * Derivado do enum: somaAoSaldo() responde pela decisao corrente e
     * Estornada entra porque seus lancamentos ja carregam o sinal negativo.
     *
     * @return array<int, string>
     */
    public static function decisoesNoSaldo(): array
    {
        $decisoes = array_map(
            static fn (DecisaoPontuacao $d): string => $d->value,
            array_filter(
                DecisaoPontuacao::cases(),
                static fn (DecisaoPontuacao $d): bool => $d->somaAoSaldo() || $d === DecisaoPontuacao::Estornada,
            ),
        );

        sort($decisoes);

        return $decisoes;
    }

    /**
     * CTEs `parametros`, `elegiveis`, `expandido` e `agregado`.
     *
     * O chamador concatena a propria cauda (INSERT ... SELECT, comparacao ou
     * contagem) e SEMPRE fornece os cinco bindings de parametros(), mesmo os
     * que sua cauda nao usa: eles existem na CTE e o prepare cobra todos.
     *
     * Cada placeholder aparece UMA vez, na CTE de parametros, pelo mesmo motivo
     * documentado em LeaderboardQuery: placeholder nomeado repetido nao e
     * portavel entre prepares emulados e nativos do PDO.
     *
     * @param  string  $condicaoExtra  SQL interno, nunca entrada de usuario.
     */
    public static function sqlBase(string $condicaoExtra = ''): string
    {
        $decisoes = implode(
            ', ',
            array_map(static fn (string $d): string => "'".$d."'", self::decisoesNoSaldo()),
        );

        $usuario = EscopoPlacar::Usuario->value;
        $orgao = EscopoPlacar::Orgao->value;
        $municipio = EscopoPlacar::Municipio->value;
        $total = self::MODULO_TOTAL;

        return <<<SQL
            WITH parametros AS (
                SELECT CAST(:id_minimo     AS bigint)  AS id_minimo,
                       CAST(:id_maximo     AS bigint)  AS id_maximo,
                       CAST(:periodo_chave AS varchar) AS periodo_chave,
                       CAST(:escopo        AS varchar) AS escopo,
                       CAST(:geracao       AS integer) AS geracao
            ),

            -- Recorte do livro. A janela por id e o watermark: reconstrucao
            -- completa usa [0, maior id lido], e o delta concorrente usa
            -- (watermark inicial, watermark final].
            elegiveis AS (
                SELECT l.id,
                       l.credited_user_id,
                       l.orgao_id,
                       l.municipio_id,
                       l.modulo,
                       l.pontos,
                       l.competencia_em
                  FROM ranking.lancamentos l
                  JOIN ranking.transacoes t ON t.id = l.transacao_id
                  CROSS JOIN parametros pa
                 WHERE t.decisao IN ({$decisoes})
                   AND l.id >= pa.id_minimo
                   AND l.id <= pa.id_maximo{$condicaoExtra}
            ),

            -- Dimensao x modulo. entidade_id nula nao vira linha: municipio
            -- ausente significa fora do placar municipal, nao entidade zero.
            -- O CASE no unnest evita duplicar a linha de total caso um
            -- lancamento tenha sido gravado com o proprio rotulo reservado.
            expandido AS (
                SELECT x.id,
                       d.escopo,
                       d.entidade_id,
                       m.modulo,
                       x.pontos,
                       x.competencia_em
                  FROM elegiveis x
                  CROSS JOIN LATERAL (
                      VALUES (CAST('{$usuario}' AS varchar),   x.credited_user_id),
                             (CAST('{$orgao}' AS varchar),     x.orgao_id),
                             (CAST('{$municipio}' AS varchar), x.municipio_id)
                  ) AS d(escopo, entidade_id)
                  CROSS JOIN LATERAL unnest(
                      CASE WHEN x.modulo = '{$total}'
                           THEN ARRAY['{$total}']::varchar[]
                           ELSE ARRAY['{$total}', x.modulo]::varchar[]
                      END
                  ) AS m(modulo)
                  CROSS JOIN parametros pa
                 WHERE d.entidade_id IS NOT NULL
                   AND (pa.escopo IS NULL OR d.escopo = pa.escopo)
            ),

            -- Acumulado nao tem limites e recebe todo lancamento; mes e ano
            -- recortam por [inicia_em, termina_em). Periodo inexistente
            -- simplesmente nao casa - por isso a reconstrucao materializa os
            -- periodos do livro antes de agregar.
            agregado AS (
                SELECT p.id                     AS periodo_id,
                       e.escopo                 AS escopo,
                       e.entidade_id            AS entidade_id,
                       e.modulo                 AS modulo,
                       CAST(SUM(e.pontos) AS bigint) AS pontos
                  FROM expandido e
                  JOIN ranking.periodos p
                    ON p.tipo = 'acumulado'
                    OR (p.inicia_em IS NOT NULL
                        AND p.termina_em IS NOT NULL
                        AND e.competencia_em >= p.inicia_em
                        AND e.competencia_em <  p.termina_em)
                  CROSS JOIN parametros pa
                 WHERE (pa.periodo_chave IS NULL OR p.chave = pa.periodo_chave)
                 GROUP BY p.id, e.escopo, e.entidade_id, e.modulo
            )
            SQL;
    }

    /**
     * Bindings da CTE `parametros`. Periodo e escopo nulos significam "todos".
     *
     * @return array<string, mixed>
     */
    public static function parametros(
        int $idMinimo = 0,
        int $idMaximo = self::SEM_LIMITE,
        ?string $periodoChave = null,
        ?EscopoPlacar $escopo = null,
        int $geracao = 1,
    ): array {
        return [
            'id_minimo' => $idMinimo,
            'id_maximo' => $idMaximo,
            'periodo_chave' => $periodoChave,
            'escopo' => $escopo?->value,
            'geracao' => $geracao,
        ];
    }

    /**
     * CASE SQL da faixa, derivado de FaixaRanking.
     *
     * Os limiares vivem no enum. Repeti-los em SQL literal faria a faixa da
     * projecao reconstruida divergir da faixa do dominio no primeiro ajuste de
     * limiar - e a divergencia so apareceria no placar, nunca no teste do enum.
     */
    public static function expressaoFaixa(string $expressaoPontos): string
    {
        $faixas = FaixaRanking::cases();
        usort($faixas, static fn (FaixaRanking $a, FaixaRanking $b): int => $b->pontosMinimos() <=> $a->pontosMinimos());

        $sql = 'CASE';

        foreach ($faixas as $faixa) {
            if ($faixa->pontosMinimos() <= 0) {
                continue;
            }

            $sql .= " WHEN {$expressaoPontos} >= {$faixa->pontosMinimos()} THEN '{$faixa->value}'";
        }

        return $sql." ELSE '".FaixaRanking::deSaldo(0)->value."' END";
    }
}
