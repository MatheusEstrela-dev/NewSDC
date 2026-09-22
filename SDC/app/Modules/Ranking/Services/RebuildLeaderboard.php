<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\Enums\EscopoPlacar;
use App\Modules\Ranking\Enums\TipoPeriodo;
use App\Modules\Ranking\Support\LivroAgregado;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * RECONSTRUCAO da projecao de leitura a partir do livro de pontos.
 *
 * ranking.saldos e derivada: some os lancamentos e o placar reaparece. Esta
 * classe faz exatamente isso - e faz SEM tirar o placar do ar.
 *
 * GERACAO NOVA, NUNCA TRUNCATE
 * A reconstrucao grava numa geracao NOVA (max(geracao) + 1) e nao encosta na
 * geracao em uso. Truncar para reconstruir deixaria o placar vazio durante todo
 * o recalculo, e vazio e uma resposta errada entregue com confianca: o usuario
 * veria "0 pontos" em vez de "indisponivel".
 *
 * O PONTEIRO ATIVO E max(geracao), E A TROCA E O COMMIT
 * Nao existe coluna de ponteiro no schema, e nao precisa existir: a geracao
 * ativa e a maior publicada, e a geracao nova inteira e inserida dentro de UMA
 * transacao. No PostgreSQL um leitor enxerga o estado anterior ao COMMIT ou o
 * posterior, jamais o meio - entao a troca do ponteiro e atomica por
 * construcao, sem DDL, sem lock no caminho de leitura e sem janela em que o
 * placar responda pela geracao incompleta. Se a reconstrucao falhar, o ROLLBACK
 * devolve o ponteiro a geracao anterior sem nenhum passo de limpeza.
 *
 * WATERMARK E O DELTA CONCORRENTE
 * O watermark inicial e o maior ranking.lancamentos.id considerado, lido sob
 * lock breve numa transacao propria (ver watermarkEstavel): sem esse lock, um
 * INSERT em voo com id MENOR que o maximo visivel poderia commitar depois e
 * ficar fora do intervalo do delta, que e uma perda silenciosa de evento.
 *
 * DECISAO: INCORPORAR O DELTA, NAO RECUSAR A TROCA.
 * Recusar a troca sempre que um evento chegasse durante o rebuild tornaria a
 * reconstrucao impossivel em producao - o ranking consome eventos o tempo todo,
 * e quanto maior o livro, maior a chance de a corrida acontecer; a operacao
 * entraria num ciclo de tentar e desistir. A incorporacao e barata e exata: o
 * delta e a MESMA agregacao do livro, restrita a (watermark inicial, watermark
 * final], somada a geracao nova. A recusa continua disponivel (--recusar-delta)
 * para quando se quer uma reconstrucao provadamente isolada, por exemplo ao
 * investigar divergencia com o ingestao pausada.
 *
 * O delta roda com ranking.lancamentos e ranking.transacoes travadas em SHARE
 * ROW EXCLUSIVE: isso bloqueia os tres caminhos de escrita do modulo (registro,
 * confirmacao e estorno) por alguns instantes e NAO bloqueia leitura - o placar
 * continua respondendo. Com a escrita parada, o watermark final e definitivo e
 * o intervalo do delta nao pode ganhar linha nova enquanto e lido.
 *
 * CONFIRMACAO TARDIA TAMBEM E DELTA
 * ConfirmScoreTransaction promove 'pendente' para 'confirmada' SEM gravar
 * lancamento novo: o id do livro nao muda e o delta por id nao veria nada,
 * embora o ponto passe a valer. Por isso a agregacao principal captura, no
 * MESMO comando (data-modifying CTE, portanto na mesma snapshot), as transacoes
 * que estavam pendentes; na fase travada, as que ja foram promovidas sao
 * somadas. Capturar em comando separado criaria janela para contar duas vezes
 * ou nenhuma, dependendo da ordem.
 *
 * CUIDADO COM OCTANE: stateless. Recorte, geracao e modo chegam por argumento.
 */
class RebuildLeaderboard
{
    /** Conexao dedicada. Nunca herda a conexao default. */
    public const CONEXAO = RecordScoreTransaction::CONEXAO;

    /** Temporaria de sessao, derrubada no COMMIT (ON COMMIT DROP). */
    private const TABELA_PENDENTES = 'ranking_rebuild_pendentes';

    /** Serializa a publicacao de geracoes sem bloquear consultas do placar. */
    public function __construct(
        private readonly ReconcileLeaderboard $conciliacao = new ReconcileLeaderboard,
        private readonly RecordScoreTransaction $livro = new RecordScoreTransaction,
        private readonly ?ConnectionInterface $conexao = null,

        // Falha rapido em vez de ficar pendurado esperando a fase travada: um
        // rebuild travado indefinidamente prende worker e mascara contencao.
        private readonly string $lockTimeout = '15s',
    ) {}

    /**
     * Reconstroi a projecao e publica a geracao nova.
     *
     * @param  string|null  $periodoChave  'mes:2026-09', 'ano:2026', 'acumulado'; null reconstroi todos
     * @param  bool  $recusarDelta  aborta (ROLLBACK) se algo chegar durante a reconstrucao
     * @return array{
     *     dry_run: bool, publicado: bool,
     *     geracao_ativa: int|null, geracao_nova: int|null,
     *     periodo: string|null, escopo: string|null,
     *     watermark_inicial: int, watermark_final: int,
     *     delta_lancamentos: int, delta_confirmacoes: int,
     *     periodos_criados: int, periodos_ausentes: array<int, string>,
     *     linhas_projetadas: int, linhas_delta: int, linhas_copiadas: int,
     *     divergencias_total: int, divergencias: array<int, array<string, mixed>>
     * }
     */
    public function executar(
        ?string $periodoChave = null,
        ?EscopoPlacar $escopo = null,
        bool $dryRun = false,
        bool $recusarDelta = false,
        int $limiteDivergencias = ReconcileLeaderboard::LIMITE_PADRAO,
    ): array {
        $ativa = $this->geracaoPublicada();

        if ($dryRun) {
            return $this->simular($ativa, $periodoChave, $escopo, $limiteDivergencias);
        }

        // Fora da transacao principal, de proposito: e um lock de leitura
        // instantaneo, e mante-lo aberto pelo rebuild inteiro bloquearia a
        // ingestao por todo o recalculo.
        $watermarkInicial = $this->watermarkEstavel();

        $criados = $this->garantirPeriodos($watermarkInicial);

        $resultado = $this->conexao()->transaction(function () use (
            $watermarkInicial, $periodoChave, $escopo, $recusarDelta, $limiteDivergencias
        ): array {
            $this->conexao()->statement("SET LOCAL lock_timeout = '{$this->lockTimeout}'");
            $this->conexao()->selectOne(
                'SELECT pg_advisory_xact_lock(?)',
                [ReconcileLeaderboard::LOCK_PUBLICACAO],
            );

            // Resolvidos somente depois do lock. Dois rebuilds iniciados em
            // paralelo nao podem escolher a mesma geracao nova.
            $ativa = $this->geracaoPublicada();
            $nova = $ativa === null ? 1 : $ativa + 1;

            $this->prepararCapturaDePendentes();

            // A geracao nova nao esta em uso por ninguem (ninguem le acima do
            // ponteiro), entao limpar restos de uma tentativa anterior aqui e
            // seguro - diferente de truncar a geracao ativa.
            $this->conexao()->affectingStatement(
                'DELETE FROM ranking.saldos WHERE geracao = ?',
                [$nova],
            );

            $principal = $this->projetarEcapturarPendentes($nova, $watermarkInicial, $periodoChave, $escopo);

            // --- fase travada: escrita do modulo parada, leitura livre ---
            $this->travarEscrita();

            $watermarkFinal = $this->watermark();
            $deltaLancamentos = $this->contarLancamentos($watermarkInicial + 1, $watermarkFinal);
            $deltaConfirmacoes = $this->contarConfirmacoesTardias();

            if ($recusarDelta && ($deltaLancamentos > 0 || $deltaConfirmacoes > 0)) {
                throw new RuntimeException(
                    "Troca recusada por --recusar-delta: {$deltaLancamentos} lancamento(s) e "
                    ."{$deltaConfirmacoes} confirmacao(oes) chegaram durante a reconstrucao "
                    ."(watermark {$watermarkInicial} -> {$watermarkFinal}). Nada foi publicado."
                );
            }

            $linhasDelta = 0;

            if ($deltaLancamentos > 0) {
                $linhasDelta += $this->somarNaGeracao(
                    $nova,
                    LivroAgregado::parametros(
                        idMinimo: $watermarkInicial + 1,
                        idMaximo: $watermarkFinal,
                        periodoChave: $periodoChave,
                        escopo: $escopo,
                        geracao: $nova,
                    ),
                );
            }

            if ($deltaConfirmacoes > 0) {
                $linhasDelta += $this->somarNaGeracao(
                    $nova,
                    LivroAgregado::parametros(
                        idMaximo: $watermarkInicial,
                        periodoChave: $periodoChave,
                        escopo: $escopo,
                        geracao: $nova,
                    ),
                    LivroAgregado::SOMENTE_PENDENTES_CAPTURADOS,
                );
            }

            // Recorte parcial: o que nao foi reconstruido e copiado da geracao
            // ativa AGORA, com a escrita travada, para que a geracao publicada
            // esteja completa e coerente. Publicar uma geracao so com o recorte
            // reconstruido zeraria o placar de todos os outros.
            $copiadas = $ativa === null || ($periodoChave === null && $escopo === null)
                ? 0
                : $this->copiarRecorteNaoAlvo($ativa, $nova, $periodoChave, $escopo);

            // Ainda dentro da transacao e com os escritores bloqueados: uma
            // geracao divergente jamais fica visivel como MAX(geracao). A
            // conferencia e integral mesmo em rebuild parcial, pois o recorte
            // nao alvo copiado tambem passara a ser a geracao publicada.
            $conferencia = $this->conciliacao->conciliar(
                null,
                null,
                $nova,
                $limiteDivergencias,
            );

            if ($conferencia['total'] > 0) {
                throw new RuntimeException(sprintf(
                    'Geracao %d diverge do livro em %d linha(s); publicacao cancelada.',
                    $nova,
                    $conferencia['total'],
                ));
            }

            return [
                'geracao_ativa' => $ativa,
                'geracao_nova' => $nova,
                'watermark_final' => $watermarkFinal,
                'delta_lancamentos' => $deltaLancamentos,
                'delta_confirmacoes' => $deltaConfirmacoes,
                'linhas_projetadas' => $principal['linhas'],
                'linhas_delta' => $linhasDelta,
                'linhas_copiadas' => $copiadas,
                'conferencia' => $conferencia,
            ];
        });

        $conferencia = $resultado['conferencia'];

        return [
            'dry_run' => false,
            'publicado' => true,
            'geracao_ativa' => $resultado['geracao_ativa'],
            'geracao_nova' => $resultado['geracao_nova'],
            'periodo' => $periodoChave,
            'escopo' => $escopo?->value,
            'watermark_inicial' => $watermarkInicial,
            'watermark_final' => $resultado['watermark_final'],
            'delta_lancamentos' => $resultado['delta_lancamentos'],
            'delta_confirmacoes' => $resultado['delta_confirmacoes'],
            'periodos_criados' => $criados,
            'periodos_ausentes' => [],
            'linhas_projetadas' => $resultado['linhas_projetadas'],
            'linhas_delta' => $resultado['linhas_delta'],
            'linhas_copiadas' => $resultado['linhas_copiadas'],
            'divergencias_total' => $conferencia['total'],
            'divergencias' => $conferencia['divergencias'],
        ];
    }

    /**
     * Quantas linhas da geracao `b` diferem da geracao `a` no recorte.
     *
     * Serve para responder "a reconstrucao mudaria alguma coisa?" comparando
     * projecao contra projecao - a pergunta complementar a conciliacao, que
     * compara livro contra projecao.
     */
    public function compararGeracoes(
        int $a,
        int $b,
        ?string $periodoChave = null,
        ?EscopoPlacar $escopo = null,
    ): int {
        $linha = $this->conexao()->selectOne(
            <<<'SQL'
                WITH parametros AS (
                    SELECT CAST(:geracao_a     AS integer) AS geracao_a,
                           CAST(:geracao_b     AS integer) AS geracao_b,
                           CAST(:periodo_chave AS varchar) AS periodo_chave,
                           CAST(:escopo        AS varchar) AS escopo
                ),
                lado_a AS (
                    SELECT s.periodo_id, s.escopo, s.entidade_id, s.modulo, s.pontos
                      FROM ranking.saldos s
                      JOIN ranking.periodos p ON p.id = s.periodo_id
                      CROSS JOIN parametros pa
                     WHERE s.geracao = pa.geracao_a
                       AND (pa.periodo_chave IS NULL OR p.chave = pa.periodo_chave)
                       AND (pa.escopo        IS NULL OR s.escopo = pa.escopo)
                ),
                lado_b AS (
                    SELECT s.periodo_id, s.escopo, s.entidade_id, s.modulo, s.pontos
                      FROM ranking.saldos s
                      JOIN ranking.periodos p ON p.id = s.periodo_id
                      CROSS JOIN parametros pa
                     WHERE s.geracao = pa.geracao_b
                       AND (pa.periodo_chave IS NULL OR p.chave = pa.periodo_chave)
                       AND (pa.escopo        IS NULL OR s.escopo = pa.escopo)
                )
                SELECT COUNT(*) AS total
                  FROM lado_a a
                  FULL OUTER JOIN lado_b b
                    ON  b.periodo_id  = a.periodo_id
                    AND b.escopo      = a.escopo
                    AND b.entidade_id = a.entidade_id
                    AND b.modulo      = a.modulo
                 WHERE COALESCE(a.pontos, 0) <> COALESCE(b.pontos, 0)
                SQL,
            [
                'geracao_a' => $a,
                'geracao_b' => $b,
                'periodo_chave' => $periodoChave,
                'escopo' => $escopo?->value,
            ],
        );

        return $linha === null ? 0 : (int) $linha->total;
    }

    /**
     * --dry-run: calcula e compara SEM escrever.
     *
     * Nao abre transacao para depois desfazer: um ROLLBACK ainda consumiria
     * sequence, tomaria lock e gravaria WAL. A pergunta do dry-run - "o que a
     * reconstrucao mudaria?" - e respondida comparando o livro com a projecao
     * ATIVA, que e precisamente a conciliacao.
     *
     * @return array<string, mixed>
     */
    private function simular(
        ?int $ativa,
        ?string $periodoChave,
        ?EscopoPlacar $escopo,
        int $limiteDivergencias,
    ): array {
        $watermark = $this->watermark();
        $ausentes = $this->periodosAusentes($watermark);
        $conferencia = $this->conciliacao->conciliar($periodoChave, $escopo, $ativa ?? 1, $limiteDivergencias);

        return [
            'dry_run' => true,
            'publicado' => false,
            'geracao_ativa' => $ativa,
            'geracao_nova' => null,
            'periodo' => $periodoChave,
            'escopo' => $escopo?->value,
            'watermark_inicial' => $watermark,
            'watermark_final' => $watermark,
            'delta_lancamentos' => 0,
            'delta_confirmacoes' => 0,
            'periodos_criados' => 0,
            'periodos_ausentes' => $ausentes,
            'linhas_projetadas' => 0,
            'linhas_delta' => 0,
            'linhas_copiadas' => 0,
            'divergencias_total' => $conferencia['total'],
            'divergencias' => $conferencia['divergencias'],
        ];
    }

    /**
     * Agrega o livro na geracao nova E captura as transacoes pendentes no MESMO
     * comando.
     *
     * As duas escritas sao CTEs data-modifying do mesmo statement, logo
     * enxergam a mesma snapshot: nao existe instante entre "o que foi somado" e
     * "o que ficou de fora por estar pendente". Em dois comandos, uma
     * confirmacao no meio faria a transacao ser somada pela agregacao E
     * promovida pelo delta (ponto em dobro) ou escapar das duas (ponto
     * perdido).
     *
     * @return array{linhas:int, pendentes:int}
     */
    private function projetarEcapturarPendentes(
        int $geracao,
        int $watermark,
        ?string $periodoChave,
        ?EscopoPlacar $escopo,
    ): array {
        $faixa = LivroAgregado::expressaoFaixa('a.pontos');
        $tabela = self::TABELA_PENDENTES;

        $linha = $this->conexao()->selectOne(
            LivroAgregado::sqlBase()."\n".<<<SQL
                , projetado AS (
                    INSERT INTO ranking.saldos
                        (geracao, periodo_id, escopo, entidade_id, modulo, pontos, faixa, atualizado_em)
                    SELECT pa.geracao, a.periodo_id, a.escopo, a.entidade_id, a.modulo,
                           a.pontos, {$faixa}, now()
                      FROM agregado a
                      CROSS JOIN parametros pa
                    ON CONFLICT (geracao, periodo_id, escopo, entidade_id, modulo) DO UPDATE SET
                        pontos = EXCLUDED.pontos,
                        faixa = EXCLUDED.faixa,
                        atualizado_em = now()
                    RETURNING 1
                ),
                pendentes AS (
                    INSERT INTO pg_temp.{$tabela} (id)
                    SELECT t.id
                      FROM ranking.transacoes t
                     WHERE t.decisao = 'pendente'
                    ON CONFLICT (id) DO NOTHING
                    RETURNING 1
                )
                SELECT (SELECT COUNT(*) FROM projetado) AS linhas,
                       (SELECT COUNT(*) FROM pendentes) AS pendentes
                SQL,
            LivroAgregado::parametros(
                idMaximo: $watermark,
                periodoChave: $periodoChave,
                escopo: $escopo,
                geracao: $geracao,
            ),
        );

        return [
            'linhas' => $linha === null ? 0 : (int) $linha->linhas,
            'pendentes' => $linha === null ? 0 : (int) $linha->pendentes,
        ];
    }

    /**
     * Soma um recorte do livro na geracao informada (delta).
     *
     * `pontos = saldos.pontos + EXCLUDED.pontos`, nunca read-modify-write: o
     * delta acrescenta ao que a agregacao principal ja projetou. A faixa e
     * recalculada sobre o TOTAL resultante, senao a linha ficaria com a faixa
     * do incremento.
     *
     * @param  array<string, mixed>  $parametros
     */
    private function somarNaGeracao(int $geracao, array $parametros, string $condicaoExtra = ''): int
    {
        $total = 'saldos.pontos + EXCLUDED.pontos';

        // Duas expressoes de faixa: a da linha inserida sai do proprio
        // incremento; a da linha ja existente sai do TOTAL resultante. Usar a
        // mesma nos dois lugares deixaria a linha atualizada com a faixa do
        // incremento, e `saldos.pontos` nem sequer existe na lista do SELECT.
        $faixaInserida = LivroAgregado::expressaoFaixa('a.pontos');
        $faixaTotal = LivroAgregado::expressaoFaixa($total);

        return $this->conexao()->affectingStatement(
            LivroAgregado::sqlBase($condicaoExtra)."\n".<<<SQL
                INSERT INTO ranking.saldos
                    (geracao, periodo_id, escopo, entidade_id, modulo, pontos, faixa, atualizado_em)
                SELECT pa.geracao, a.periodo_id, a.escopo, a.entidade_id, a.modulo,
                       a.pontos, {$faixaInserida}, now()
                  FROM agregado a
                  CROSS JOIN parametros pa
                ON CONFLICT (geracao, periodo_id, escopo, entidade_id, modulo) DO UPDATE SET
                    pontos = {$total},
                    faixa = {$faixaTotal},
                    atualizado_em = now()
                SQL,
            $parametros,
        );
    }

    /**
     * Copia para a geracao nova o que ficou FORA do recorte reconstruido.
     *
     * Sem isto, `ranking:rebuild --periodo=mes:2026-09` publicaria uma geracao
     * que so tem setembro e zeraria o resto do placar.
     */
    private function copiarRecorteNaoAlvo(
        int $ativa,
        int $nova,
        ?string $periodoChave,
        ?EscopoPlacar $escopo,
    ): int {
        return $this->conexao()->affectingStatement(
            <<<'SQL'
                WITH parametros AS (
                    SELECT CAST(:geracao_ativa AS integer) AS geracao_ativa,
                           CAST(:geracao_nova  AS integer) AS geracao_nova,
                           CAST(:periodo_chave AS varchar) AS periodo_chave,
                           CAST(:escopo        AS varchar) AS escopo
                )
                INSERT INTO ranking.saldos
                    (geracao, periodo_id, escopo, entidade_id, modulo, pontos, faixa, atualizado_em)
                SELECT pa.geracao_nova, s.periodo_id, s.escopo, s.entidade_id, s.modulo,
                       s.pontos, s.faixa, s.atualizado_em
                  FROM ranking.saldos s
                  JOIN ranking.periodos p ON p.id = s.periodo_id
                  CROSS JOIN parametros pa
                 WHERE s.geracao = pa.geracao_ativa
                   AND NOT (
                       (pa.periodo_chave IS NULL OR p.chave  = pa.periodo_chave)
                       AND (pa.escopo    IS NULL OR s.escopo = pa.escopo)
                   )
                ON CONFLICT (geracao, periodo_id, escopo, entidade_id, modulo) DO NOTHING
                SQL,
            [
                'geracao_ativa' => $ativa,
                'geracao_nova' => $nova,
                'periodo_chave' => $periodoChave,
                'escopo' => $escopo?->value,
            ],
        );
    }

    /**
     * Trava os tres caminhos de escrita do modulo sem tocar na leitura.
     *
     * SHARE ROW EXCLUSIVE conflita com ROW EXCLUSIVE (INSERT/UPDATE/DELETE) e
     * nao conflita com ACCESS SHARE (SELECT): RecordScoreTransaction,
     * ConfirmScoreTransaction e ReverseScoreEntry esperam; LeaderboardQuery
     * continua servindo o placar normalmente.
     *
     * Trava tambem ranking.transacoes porque a confirmacao move saldo mexendo
     * SO na decisao, sem inserir lancamento.
     */
    private function travarEscrita(): void
    {
        $this->conexao()->statement(
            'LOCK TABLE ranking.lancamentos, ranking.transacoes IN SHARE ROW EXCLUSIVE MODE'
        );
    }

    /**
     * Maior id do livro, lido com a escrita travada por um instante.
     *
     * O lock e curto mas indispensavel: `MAX(id)` sozinho pode devolver 105
     * enquanto o id 104, de uma transacao ainda em voo, esta invisivel. Esse
     * 104 nunca entraria na agregacao (invisivel) nem no delta (id menor que o
     * watermark) - evento perdido em silencio. Esperando os escritores em voo
     * terminarem, todo id ate o watermark esta commitado e visivel.
     *
     * A transacao propria garante que o lock e liberado imediatamente, e nao
     * segurado pela reconstrucao inteira.
     */
    private function watermarkEstavel(): int
    {
        return $this->conexao()->transaction(function (): int {
            $this->conexao()->statement("SET LOCAL lock_timeout = '{$this->lockTimeout}'");
            $this->travarEscrita();

            return $this->watermark();
        });
    }

    private function watermark(): int
    {
        $linha = $this->conexao()->selectOne('SELECT COALESCE(MAX(id), 0) AS watermark FROM ranking.lancamentos');

        return $linha === null ? 0 : (int) $linha->watermark;
    }

    private function contarLancamentos(int $de, int $ate): int
    {
        if ($de > $ate) {
            return 0;
        }

        $linha = $this->conexao()->selectOne(
            'SELECT COUNT(*) AS total FROM ranking.lancamentos WHERE id >= ? AND id <= ?',
            [$de, $ate],
        );

        return $linha === null ? 0 : (int) $linha->total;
    }

    /** Pendentes capturados na agregacao que ja foram promovidas desde entao. */
    private function contarConfirmacoesTardias(): int
    {
        $decisoes = LivroAgregado::decisoesNoSaldo();
        $marcadores = implode(', ', array_fill(0, count($decisoes), '?'));
        $tabela = self::TABELA_PENDENTES;

        $linha = $this->conexao()->selectOne(
            "SELECT COUNT(*) AS total
               FROM ranking.transacoes t
               JOIN pg_temp.{$tabela} c ON c.id = t.id
              WHERE t.decisao IN ({$marcadores})",
            $decisoes,
        );

        return $linha === null ? 0 : (int) $linha->total;
    }

    private function prepararCapturaDePendentes(): void
    {
        $tabela = self::TABELA_PENDENTES;

        // Conexao persistente pode trazer a temporaria de um rebuild anterior
        // que terminou em erro antes do COMMIT que a derrubaria.
        $this->conexao()->statement("DROP TABLE IF EXISTS pg_temp.{$tabela}");
        $this->conexao()->statement(
            "CREATE TEMP TABLE {$tabela} (id bigint PRIMARY KEY) ON COMMIT DROP"
        );
    }

    /**
     * Chaves de periodo que o livro exige e ranking.periodos ainda nao tem.
     *
     * Lancamento cuja competencia nao casa com nenhum periodo simplesmente nao
     * aparece na agregacao - some do placar sem erro nenhum. Por isso o dry-run
     * reporta a lacuna e a execucao materializa os periodos antes de agregar.
     *
     * @return array<int, string>
     */
    private function periodosAusentes(int $watermark): array
    {
        $chaves = [];

        foreach ($this->competenciasDoLivro($watermark) as $competencia) {
            foreach (TipoPeriodo::cases() as $tipo) {
                $chaves[$tipo->chave($competencia)] = true;
            }
        }

        if ($chaves === []) {
            return [];
        }

        $existentes = array_map(
            static fn (object $linha): string => (string) $linha->chave,
            $this->conexao()->select(
                'SELECT chave FROM ranking.periodos WHERE chave IN ('
                .implode(', ', array_fill(0, count($chaves), '?')).')',
                array_keys($chaves),
            ),
        );

        return array_values(array_diff(array_keys($chaves), $existentes));
    }

    /**
     * Materializa mes, ano e acumulado de cada competencia presente no livro.
     *
     * Reaproveita RecordScoreTransaction::resolverPeriodos - o mesmo codigo que
     * cria periodo no caminho de escrita. Duplicar a criacao aqui abriria a
     * porta para o rebuild recortar o calendario de um jeito e a ingestao de
     * outro, e a divergencia so apareceria na virada do mes.
     */
    private function garantirPeriodos(int $watermark): int
    {
        $antes = count($this->periodosAusentes($watermark));

        foreach ($this->competenciasDoLivro($watermark) as $competencia) {
            $this->livro->resolverPeriodos($competencia);
        }

        return $antes;
    }

    /**
     * Uma competencia representativa por mes do calendario local. Mes, ano e
     * acumulado derivam dela pelo proprio TipoPeriodo.
     *
     * @return array<int, DateTimeImmutable>
     */
    private function competenciasDoLivro(int $watermark): array
    {
        $fuso = TipoPeriodo::FUSO_CALENDARIO;

        $linhas = $this->conexao()->select(
            "SELECT DISTINCT date_trunc('month', l.competencia_em AT TIME ZONE ?) AS mes
               FROM ranking.lancamentos l
              WHERE l.id <= ?
              ORDER BY 1",
            [$fuso, $watermark],
        );

        return array_map(
            static function (object $linha) use ($fuso): DateTimeImmutable {
                $momento = date_create_immutable((string) $linha->mes, new DateTimeZone($fuso));

                if ($momento === false) {
                    throw new RuntimeException("Competencia ilegivel no livro: '{$linha->mes}'.");
                }

                return $momento;
            },
            $linhas,
        );
    }

    /** Maior geracao ja publicada, ou null quando a projecao esta vazia. */
    private function geracaoPublicada(): ?int
    {
        $linha = $this->conexao()->selectOne('SELECT MAX(geracao) AS geracao FROM ranking.saldos');

        return $linha === null || $linha->geracao === null ? null : (int) $linha->geracao;
    }

    private function conexao(): ConnectionInterface
    {
        return $this->conexao ?? DB::connection(self::CONEXAO);
    }
}
