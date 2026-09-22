<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\Enums\EscopoPlacar;
use App\Modules\Ranking\Support\LivroAgregado;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * CONCILIACAO: livro contra projecao. So compara - corrigir e ato explicito.
 *
 * O QUE ESTE SERVICO PROTEGE
 * ranking.saldos e projecao derivada de ranking.lancamentos. A projecao e
 * mantida por soma incremental (UPSERT com `pontos = saldos.pontos + EXCLUDED`)
 * no caminho de escrita, e soma incremental erra em silencio: um lancamento
 * gravado com a projecao fora do ar, uma confirmacao que moveu o saldo duas
 * vezes, um estorno aplicado na geracao errada. Nenhuma dessas falhas aparece
 * no placar - ele continua respondendo, com o numero errado.
 *
 * A unica forma de descobrir e refazer a pergunta pelo livro e comparar. E o
 * que esta classe faz, e por isso ela e o gate de monitoramento: `ranking:
 * reconcile` sai com codigo diferente de zero quando ha divergencia.
 *
 * NAO CORRIGE POR PADRAO
 * Divergencia e sintoma. Corrigir calado apagaria a evidencia de um defeito no
 * caminho de escrita, que voltaria a produzir a mesma divergencia no dia
 * seguinte. corrigir() existe, e explicito, e reporta quantas linhas mexeu.
 *
 * A CORRECAO E SEMPRE PELO LIVRO
 * O livro e append-only e auditavel; a projecao e descartavel. Entao o valor do
 * livro sobrescreve o da projecao, nunca o contrario - inclusive quando o livro
 * diz zero para uma linha que a projecao inventou.
 *
 * CUIDADO COM OCTANE: stateless. Recorte, geracao e limite chegam por
 * argumento; a conexao e infraestrutura, igual para todo request.
 */
class ReconcileLeaderboard
{
    /** Conexao dedicada. Nunca herda a conexao default. */
    public const CONEXAO = RecordScoreTransaction::CONEXAO;

    /** Teto de linhas detalhadas devolvidas; o total vem sempre completo. */
    public const LIMITE_PADRAO = 500;

    /** Mesmo lock usado pelo rebuild ao publicar uma geracao. */
    public const LOCK_PUBLICACAO = 1919247470;

    public function __construct(
        private readonly ?ConnectionInterface $conexao = null,
    ) {}

    /**
     * Compara o livro com a projecao de uma geracao e devolve as divergencias.
     *
     * @param  string|null  $periodoChave  'mes:2026-09', 'ano:2026', 'acumulado'; null compara todos
     * @param  int|null  $geracao  null usa a geracao ativa (a maior publicada)
     * @return array{
     *     geracao: int,
     *     periodo: string|null,
     *     escopo: string|null,
     *     total: int,
     *     truncado: bool,
     *     limite: int,
     *     divergencias: array<int, array{
     *         periodo_id:int, periodo_chave:string, escopo:string, entidade_id:int,
     *         modulo:string, pontos_livro:int, pontos_projecao:int, diferenca:int
     *     }>
     * }
     */
    public function conciliar(
        ?string $periodoChave = null,
        ?EscopoPlacar $escopo = null,
        ?int $geracao = null,
        int $limite = self::LIMITE_PADRAO,
    ): array {
        $geracao ??= $this->geracaoAtiva();
        $parametros = LivroAgregado::parametros(
            periodoChave: $periodoChave,
            escopo: $escopo,
            geracao: $geracao,
        );

        $total = (int) $this->conexao()->selectOne(
            LivroAgregado::sqlBase()."\n".$this->sqlDivergencias()."\n"
            .'SELECT COUNT(*) AS total FROM divergencias',
            $parametros,
        )->total;

        $linhas = $total === 0 ? [] : $this->conexao()->select(
            LivroAgregado::sqlBase()."\n".$this->sqlDivergencias()."\n".<<<SQL
                SELECT d.periodo_id,
                       p.chave AS periodo_chave,
                       d.escopo,
                       d.entidade_id,
                       d.modulo,
                       d.pontos_livro,
                       d.pontos_projecao
                  FROM divergencias d
                  JOIN ranking.periodos p ON p.id = d.periodo_id
                 ORDER BY abs(d.pontos_livro - d.pontos_projecao) DESC, d.periodo_id, d.escopo, d.entidade_id, d.modulo
                 LIMIT {$this->limiteValido($limite)}
                SQL,
            $parametros,
        );

        return [
            'geracao' => $geracao,
            'periodo' => $periodoChave,
            'escopo' => $escopo?->value,
            'total' => $total,
            'truncado' => $total > $this->limiteValido($limite),
            'limite' => $this->limiteValido($limite),
            'divergencias' => array_map(
                static function (object $linha): array {
                    $livro = (int) $linha->pontos_livro;
                    $projecao = (int) $linha->pontos_projecao;

                    return [
                        'periodo_id' => (int) $linha->periodo_id,
                        'periodo_chave' => (string) $linha->periodo_chave,
                        'escopo' => (string) $linha->escopo,
                        'entidade_id' => (int) $linha->entidade_id,
                        'modulo' => (string) $linha->modulo,
                        'pontos_livro' => $livro,
                        'pontos_projecao' => $projecao,
                        'diferenca' => $livro - $projecao,
                    ];
                },
                $linhas,
            ),
        ];
    }

    /**
     * Sobrescreve a projecao com o valor do livro, no recorte informado.
     *
     * Um unico INSERT ... ON CONFLICT resolve os dois casos: linha que existe na
     * projecao com valor errado (DO UPDATE) e linha que o livro tem e a projecao
     * nunca criou (INSERT). Linha que a projecao tem e o livro desconhece vira
     * zero em vez de ser apagada - o placar le COALESCE e trata ausencia como
     * zero, e manter a linha preserva a evidencia de que ela existiu.
     *
     * Roda em transacao: correcao parcial de placar e pior que placar errado,
     * porque esconde o sintoma sem restaurar a invariante.
     *
     * @return int linhas corrigidas
     */
    public function corrigir(
        ?string $periodoChave = null,
        ?EscopoPlacar $escopo = null,
        ?int $geracao = null,
    ): int {
        $faixa = LivroAgregado::expressaoFaixa('d.pontos_livro');

        return $this->conexao()->transaction(function () use (
            $periodoChave, $escopo, $geracao, $faixa
        ): int {
            $this->conexao()->selectOne(
                'SELECT pg_advisory_xact_lock(?)',
                [self::LOCK_PUBLICACAO],
            );
            $this->conexao()->statement(
                'LOCK TABLE ranking.lancamentos, ranking.transacoes IN SHARE ROW EXCLUSIVE MODE'
            );

            // A geracao precisa ser resolvida depois dos locks: um rebuild que
            // terminou enquanto a correcao esperava pode ter publicado N+1.
            $geracaoAlvo = $geracao ?? $this->geracaoAtiva();
            $parametros = LivroAgregado::parametros(
                periodoChave: $periodoChave,
                escopo: $escopo,
                geracao: $geracaoAlvo,
            );

            return $this->conexao()->affectingStatement(
                LivroAgregado::sqlBase()."\n".$this->sqlDivergencias()."\n".<<<SQL
                    INSERT INTO ranking.saldos
                        (geracao, periodo_id, escopo, entidade_id, modulo, pontos, faixa, atualizado_em)
                    SELECT pa.geracao, d.periodo_id, d.escopo, d.entidade_id, d.modulo,
                           d.pontos_livro, {$faixa}, now()
                      FROM divergencias d
                      CROSS JOIN parametros pa
                    ON CONFLICT (geracao, periodo_id, escopo, entidade_id, modulo) DO UPDATE SET
                        pontos = EXCLUDED.pontos,
                        faixa = EXCLUDED.faixa,
                        atualizado_em = now()
                    SQL,
                $parametros,
            );
        });
    }

    /**
     * Geracao ativa da projecao: a maior ja publicada.
     *
     * O ponteiro ativo NAO e uma coluna a parte - e o proprio maximo de
     * ranking.saldos.geracao. A reconstrucao insere a geracao nova inteira
     * dentro de uma transacao, entao o maximo passa de N para N+1 no COMMIT,
     * de uma vez: nenhum leitor enxerga geracao nova pela metade. Ver
     * RebuildLeaderboard.
     */
    public function geracaoAtiva(): int
    {
        $linha = $this->conexao()->selectOne('SELECT MAX(geracao) AS geracao FROM ranking.saldos');

        return $linha === null || $linha->geracao === null ? 1 : (int) $linha->geracao;
    }

    /**
     * FULL OUTER JOIN entre o livro agregado e a projecao da geracao.
     *
     * FULL, e nao LEFT: divergencia tanto e ponto que o livro tem e a projecao
     * perdeu quanto ponto que a projecao tem e o livro nunca registrou - o
     * segundo caso e o mais grave dos dois, e um LEFT JOIN nao o veria.
     *
     * COALESCE nos dois lados: ausencia e zero, e linha de saldo zero em um
     * lado com ausencia no outro NAO e divergencia (credito 24 + estorno -24
     * projeta zero e o livro tambem soma zero).
     */
    private function sqlDivergencias(): string
    {
        return <<<'SQL'
            , projecao AS (
                SELECT s.periodo_id, s.escopo, s.entidade_id, s.modulo, s.pontos
                  FROM ranking.saldos s
                  JOIN ranking.periodos p ON p.id = s.periodo_id
                  CROSS JOIN parametros pa
                 WHERE s.geracao = pa.geracao
                   AND (pa.periodo_chave IS NULL OR p.chave = pa.periodo_chave)
                   AND (pa.escopo        IS NULL OR s.escopo = pa.escopo)
            ),
            divergencias AS (
                SELECT COALESCE(a.periodo_id,  pr.periodo_id)  AS periodo_id,
                       COALESCE(a.escopo,      pr.escopo)      AS escopo,
                       COALESCE(a.entidade_id, pr.entidade_id) AS entidade_id,
                       COALESCE(a.modulo,      pr.modulo)      AS modulo,
                       COALESCE(a.pontos,  0)                  AS pontos_livro,
                       COALESCE(pr.pontos, 0)                  AS pontos_projecao
                  FROM agregado a
                  FULL OUTER JOIN projecao pr
                    ON  pr.periodo_id  = a.periodo_id
                    AND pr.escopo      = a.escopo
                    AND pr.entidade_id = a.entidade_id
                    AND pr.modulo      = a.modulo
                 WHERE COALESCE(a.pontos, 0) <> COALESCE(pr.pontos, 0)
            )
            SQL;
    }

    private function limiteValido(int $limite): int
    {
        return max(1, min($limite, 10000));
    }

    private function conexao(): ConnectionInterface
    {
        return $this->conexao ?? DB::connection(self::CONEXAO);
    }
}
