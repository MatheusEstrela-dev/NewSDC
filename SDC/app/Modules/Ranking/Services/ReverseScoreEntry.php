<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\Models\Lancamento;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Estorno AUDITADO de credito ja lancado no livro de pontos.
 *
 * Corrigir pontuacao aqui nunca significa apagar ou editar o credito original:
 * ranking.lancamentos e append-only e Lancamento estende RegistroImutavel, que
 * recusa save() sobre registro existente e recusa delete(). A correcao e um
 * lancamento NOVO, com pontos negativos, apontando para o credito que anula em
 * estorno_de_id. A cadeia inteira continua legivel depois da correcao, que e o
 * proposito de um livro.
 *
 * O TETO E O CREDITO ORIGINAL, BONUS INCLUIDO
 * O somatorio dos estornos de um lancamento nunca pode ultrapassar o valor
 * creditado. Sem esse limite, um estorno repetido (dupla entrega do comando,
 * clique duplo do analista, replay de fila) levaria o saldo a negativo e
 * inventaria penalidade que ninguem decidiu. Tentativa de exceder e recusada
 * com DomainException, e a transacao de banco inteira volta atras.
 *
 * O credito original e travado com SELECT ... FOR UPDATE antes da soma dos
 * estornos existentes: dois estornos concorrentes do mesmo lancamento seriam,
 * sem o lock, ambos aprovados contra o mesmo saldo lido.
 *
 * CUIDADO COM OCTANE: stateless, como todo service deste modulo. O contexto do
 * pedido chega por argumento, jamais de Auth dentro do service.
 */
class ReverseScoreEntry
{
    public function __construct(
        private readonly RecordScoreTransaction $livro,
    ) {}

    /**
     * Estorna (total ou parcialmente) um credito ja lancado.
     *
     * @param int      $lancamentoId credito original a anular
     * @param int|null $pontos       quanto estornar; null estorna o saldo restante
     *
     * @throws DomainException quando o estorno e invalido, repetido ou excede o credito
     */
    public function estornar(
        int $lancamentoId,
        ?int $pontos = null,
        ?int $geracao = null,
    ): Lancamento {
        if ($pontos !== null && $pontos <= 0) {
            throw new DomainException('O valor a estornar deve ser positivo; o sinal negativo e aplicado pelo servico.');
        }

        return DB::connection(RecordScoreTransaction::CONEXAO)->transaction(
            function () use ($lancamentoId, $pontos, $geracao): Lancamento {
                $original = $this->travarOriginal($lancamentoId);

                $creditado = (int) $original->pontos;
                $jaEstornado = $this->totalJaEstornado($lancamentoId);
                $restante = $creditado - $jaEstornado;

                if ($restante <= 0) {
                    throw new DomainException(
                        "Lancamento {$lancamentoId} ja foi estornado integralmente ({$jaEstornado} de {$creditado})."
                    );
                }

                $valor = $pontos ?? $restante;

                if ($valor > $restante) {
                    throw new DomainException(
                        "Estorno de {$valor} excede o saldo estornavel do lancamento {$lancamentoId}: "
                        . "creditado {$creditado}, ja estornado {$jaEstornado}, restante {$restante}."
                    );
                }

                // Estorno integral em uma vez espelha a decomposicao do credito
                // (base e bonus); estorno parcial concentra tudo na base, porque
                // dividir o bonus proporcionalmente inventaria centavo de ponto
                // que a regra nunca concedeu. A CHECK ck_ranking_lancamentos_soma
                // exige pontos = pontos_base + pontos_bonus nos dois casos.
                $integral = $jaEstornado === 0 && $valor === $creditado;
                $base = $integral ? -((int) $original->pontos_base) : -$valor;
                $bonus = $integral ? -((int) $original->pontos_bonus) : 0;

                $sequencia = $this->proximaSequencia($lancamentoId);
                $natureza = $sequencia === 1
                    ? RecordScoreTransaction::NATUREZA_ESTORNO
                    : RecordScoreTransaction::NATUREZA_ESTORNO . ':' . $sequencia;

                $competencia = $this->competencia($original->competencia_em);

                $estornoId = $this->livro->gravarLancamento(
                    transacaoId: (int) $original->transacao_id,
                    entryKey: $this->livro->entryKey(
                        (string) $original->chave_canonica,
                        (string) $original->familia,
                        $natureza,
                    ),
                    creditedUserId: $this->inteiroOuNulo($original->credited_user_id),
                    orgaoId: $this->inteiroOuNulo($original->orgao_id),
                    municipioId: $this->inteiroOuNulo($original->municipio_id),
                    modulo: (string) $original->modulo,
                    regraId: $this->inteiroOuNulo($original->regra_id),
                    regraVersao: $this->inteiroOuNulo($original->regra_versao),
                    pontosBase: $base,
                    pontosBonus: $bonus,
                    competenciaEm: $competencia,
                    estornoDeId: $lancamentoId,
                );

                // entry_key ocupado apesar do lock e da conferencia acima: o
                // livro esta num estado que este servico nao sabe corrigir, e
                // insistir criaria lancamento fora da cadeia de auditoria.
                if ($estornoId === null) {
                    throw new DomainException(
                        "Estorno '{$natureza}' do lancamento {$lancamentoId} ja existe no livro."
                    );
                }

                // Mesmas tres dimensoes, mesmos periodos, sinal invertido. O
                // saldo e a soma dos lancamentos, nunca uma subtracao feita na
                // leitura do placar.
                $this->livro->aplicarNoPlacar(
                    -$valor,
                    $this->inteiroOuNulo($original->credited_user_id),
                    $this->inteiroOuNulo($original->orgao_id),
                    $this->inteiroOuNulo($original->municipio_id),
                    (string) $original->modulo,
                    $competencia,
                    $geracao,
                );

                return Lancamento::query()->findOrFail($estornoId);
            }
        );
    }

    /**
     * Le o credito original sob lock de linha e recusa, antes de qualquer
     * escrita, o que nao pode ser estornado.
     *
     * FOR UPDATE OF l trava somente ranking.lancamentos: ranking.transacoes
     * entra apenas para trazer chave_canonica e familia, que compoem o
     * entry_key do estorno, e travar essa linha bloquearia o caminho de
     * ingestao sem necessidade.
     */
    private function travarOriginal(int $lancamentoId): object
    {
        $linha = DB::connection(RecordScoreTransaction::CONEXAO)->selectOne(
            'SELECT l.id, l.transacao_id, l.credited_user_id, l.orgao_id, l.municipio_id,
                    l.modulo, l.regra_id, l.regra_versao, l.pontos_base, l.pontos_bonus,
                    l.pontos, l.competencia_em, l.estorno_de_id,
                    t.chave_canonica, t.familia
               FROM ranking.lancamentos l
               JOIN ranking.transacoes t ON t.id = l.transacao_id
              WHERE l.id = ?
                FOR UPDATE OF l',
            [$lancamentoId],
        );

        if ($linha === null) {
            throw new DomainException("Lancamento {$lancamentoId} nao existe no livro de pontos.");
        }

        if ($linha->estorno_de_id !== null) {
            throw new DomainException(
                "Lancamento {$lancamentoId} ja e um estorno; estornar estorno nao restaura credito."
            );
        }

        if ((int) $linha->pontos <= 0) {
            throw new DomainException(
                "Lancamento {$lancamentoId} nao tem credito a estornar (pontos = {$linha->pontos})."
            );
        }

        return $linha;
    }

    /** Valor absoluto ja estornado deste credito. Os estornos sao negativos no livro. */
    private function totalJaEstornado(int $lancamentoId): int
    {
        $linha = DB::connection(RecordScoreTransaction::CONEXAO)->selectOne(
            'SELECT COALESCE(SUM(pontos), 0) AS total
               FROM ranking.lancamentos
              WHERE estorno_de_id = ?',
            [$lancamentoId],
        );

        return abs((int) $linha->total);
    }

    private function proximaSequencia(int $lancamentoId): int
    {
        $linha = DB::connection(RecordScoreTransaction::CONEXAO)->selectOne(
            'SELECT COUNT(*) AS total FROM ranking.lancamentos WHERE estorno_de_id = ?',
            [$lancamentoId],
        );

        return ((int) $linha->total) + 1;
    }

    /**
     * A competencia do estorno e a do credito, nao a de hoje: a correcao tem de
     * cair no mesmo mes e no mesmo ano que o credito, senao o placar do periodo
     * fechado continuaria com o ponto anulado.
     */
    private function competencia(mixed $valor): DateTimeImmutable
    {
        if ($valor instanceof DateTimeImmutable) {
            return $valor;
        }

        $momento = DateTimeImmutable::createFromFormat('Y-m-d H:i:sP', (string) $valor)
            ?: date_create_immutable((string) $valor, new DateTimeZone('UTC'));

        if ($momento === false) {
            throw new RuntimeException("Competencia ilegivel no lancamento estornado: '{$valor}'.");
        }

        return $momento;
    }

    private function inteiroOuNulo(mixed $valor): ?int
    {
        return $valor === null ? null : (int) $valor;
    }
}
