<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\Enums\DecisaoPontuacao;
use App\Modules\Ranking\Models\Lancamento;
use App\Modules\Ranking\Models\Transacao;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Promove a transacao PENDENTE a CONFIRMADA quando a validacao do marco chega.
 *
 * O BURACO QUE ESTE SERVICO FECHA
 * Marco que exige validacao entra no livro com decisao `pendente`: o
 * lancamento ja existe com os pontos calculados, mas `pontosParaSaldo()`
 * devolve zero e o placar nao se move. Ate aqui nao havia caminho de volta - o
 * entry_key (chave_canonica, familia, 'credito') ja esta ocupado por esse
 * lancamento pendente, entao um segundo lancamento de credito para o mesmo
 * marco e impossivel por construcao, e a UNIQUE existe justamente para isso.
 *
 * COMO A CONFIRMACAO ACONTECE SEM QUEBRAR A IMUTABILIDADE
 *   - O lancamento pendente NAO e alterado nem apagado. Lancamento estende
 *     RegistroImutavel e ranking.lancamentos e append-only; o valor gravado
 *     quando o marco foi avaliado continua sendo o valor confirmado agora, o
 *     que torna a confirmacao reproduzivel (nao ha recalculo, nao ha consulta
 *     nova ao catalogo, versao de regra publicada no meio do caminho nao muda
 *     o premio).
 *   - O que muda e ranking.transacoes.decisao, de 'pendente' para 'confirmada'.
 *     Transacao NAO e RegistroImutavel de proposito: ela e a DECISAO corrente
 *     sobre o fato, e decisao evolui. O livro e que nao evolui.
 *   - O saldo anda pelo valor que ja estava no lancamento, nas mesmas tres
 *     dimensoes e nos mesmos periodos da competencia original.
 *
 * INVARIANTE QUE ISTO RESTAURA
 * Enquanto pendente, saldo (0) e soma dos lancamentos (pontos) divergem. Depois
 * da confirmacao os dois voltam a bater, que e o que permite reconstruir o
 * placar a partir do livro.
 *
 * IDEMPOTENCIA POR ESTADO, NAO POR CHAVE
 * Nao ha linha nova para ancorar um ON CONFLICT, entao a guarda e a propria
 * decisao corrente, lida com SELECT ... FOR UPDATE dentro da mesma transacao
 * de banco que move o saldo. Duas confirmacoes concorrentes serializam no lock:
 * a primeira confirma, a segunda encontra 'confirmada' e e recusada com
 * DomainException, sem somar de novo. O UPDATE ainda repete a condicao
 * `decisao = 'pendente'` e confere a contagem de linhas afetadas, para que
 * nenhum caminho fora deste servico consiga passar despercebido.
 *
 * CUIDADO COM OCTANE: stateless. Quem validou chega por argumento, jamais de
 * Auth dentro do service.
 */
class ConfirmScoreTransaction
{
    /** Motivo gravado ao promover a decisao. */
    public const MOTIVO_CONFIRMACAO = 'validacao_confirmada';

    public function __construct(
        // Mesma mecanica de saldo do caminho de ingestao, por injecao - como o
        // ReverseScoreEntry ja faz. Reimplementar o UPSERT das tres dimensoes
        // aqui criaria uma segunda verdade sobre como o placar e projetado, e
        // as duas divergiriam no primeiro ajuste de faixa.
        private readonly RecordScoreTransaction $livro,
    ) {}

    /**
     * Confirma a transacao pendente e projeta os pontos no placar.
     *
     * @param int      $transacaoId      ranking.transacoes.id
     * @param int|null $validadorUserId  quem validou; nulo preserva o que ja estava
     *
     * @throws DomainException quando a transacao nao existe ou nao esta pendente
     */
    public function confirmar(
        int $transacaoId,
        ?int $validadorUserId = null,
        ?int $geracao = null,
    ): Transacao {
        if ($validadorUserId !== null && $validadorUserId <= 0) {
            throw new DomainException('Identificador de validador invalido.');
        }

        return DB::connection(RecordScoreTransaction::CONEXAO)->transaction(
            function () use ($transacaoId, $validadorUserId, $geracao): Transacao {
                $this->travarPendente($transacaoId);

                $lancamento = $this->creditoDaTransacao($transacaoId);
                $valor = $lancamento === null ? 0 : $this->valorAConfirmar($lancamento);

                $this->promover($transacaoId, $validadorUserId);

                if ($lancamento !== null && $valor > 0) {
                    // Mesma competencia do credito, nao a de hoje: confirmar em
                    // outubro uma entrega de setembro nao pode mover o placar de
                    // outubro nem deixar setembro sem o ponto.
                    $this->livro->aplicarNoPlacar(
                        $valor,
                        $this->inteiroOuNulo($lancamento->getAttribute('credited_user_id')),
                        $this->inteiroOuNulo($lancamento->getAttribute('orgao_id')),
                        $this->inteiroOuNulo($lancamento->getAttribute('municipio_id')),
                        (string) $lancamento->getAttribute('modulo'),
                        $this->competencia($lancamento->getAttribute('competencia_em')),
                        $geracao,
                    );
                }

                return Transacao::query()->findOrFail($transacaoId);
            }
        );
    }

    /**
     * Le a decisao corrente sob lock de linha e recusa tudo que nao esta
     * pendente.
     *
     * O lock e o que torna a operacao idempotente: sem ele, duas confirmacoes
     * simultaneas leriam 'pendente' ao mesmo tempo e ambas somariam o saldo.
     */
    private function travarPendente(int $transacaoId): void
    {
        $linha = DB::connection(RecordScoreTransaction::CONEXAO)->selectOne(
            'SELECT id, decisao FROM ranking.transacoes WHERE id = ? FOR UPDATE',
            [$transacaoId],
        );

        if ($linha === null) {
            throw new DomainException("Transacao {$transacaoId} nao existe no livro de pontos.");
        }

        $decisao = (string) $linha->decisao;

        if ($decisao !== DecisaoPontuacao::Pendente->value) {
            throw new DomainException(
                "Transacao {$transacaoId} nao esta pendente (decisao atual: '{$decisao}'); "
                . 'so uma transacao pendente pode ser confirmada.'
            );
        }
    }

    /**
     * UPDATE com a condicao repetida e contagem conferida.
     *
     * COALESCE no validador: confirmar sem informar quem validou nao apaga o
     * validador que o adaptador ja havia registrado no evento de origem.
     */
    private function promover(int $transacaoId, ?int $validadorUserId): void
    {
        $afetadas = DB::connection(RecordScoreTransaction::CONEXAO)->update(
            'UPDATE ranking.transacoes
                SET decisao = ?,
                    motivo = ?,
                    validador_user_id = COALESCE(?, validador_user_id)
              WHERE id = ?
                AND decisao = ?',
            [
                DecisaoPontuacao::Confirmada->value,
                self::MOTIVO_CONFIRMACAO,
                $validadorUserId,
                $transacaoId,
                DecisaoPontuacao::Pendente->value,
            ],
        );

        if ($afetadas !== 1) {
            throw new RuntimeException(
                "Confirmacao da transacao {$transacaoId} afetou {$afetadas} linhas apesar do lock de pendencia."
            );
        }
    }

    /**
     * Credito original da transacao (estorno_de_id nulo).
     *
     * Devolve null quando a transacao pendente nao gerou lancamento - caso de
     * regra com pontos_base zero. A decisao ainda assim e promovida: deixar a
     * transacao presa em 'pendente' para sempre seria pior, e nao ha saldo a
     * mover.
     */
    private function creditoDaTransacao(int $transacaoId): ?Lancamento
    {
        return Lancamento::query()
            ->where('transacao_id', $transacaoId)
            ->whereNull('estorno_de_id')
            ->orderBy('id')
            ->first();
    }

    /**
     * Valor liquido a projetar: o credito menos o que ja foi estornado dele.
     *
     * No caminho normal nao ha estorno sobre um credito pendente e o valor e o
     * proprio `pontos` do lancamento. A subtracao cobre o caso degenerado em
     * que alguem estornou o lancamento antes da validacao chegar: confirmar o
     * bruto ali creditaria pontos que ja haviam sido retirados.
     */
    private function valorAConfirmar(Lancamento $credito): int
    {
        $linha = DB::connection(RecordScoreTransaction::CONEXAO)->selectOne(
            'SELECT COALESCE(SUM(pontos), 0) AS total
               FROM ranking.lancamentos
              WHERE estorno_de_id = ?',
            [(int) $credito->getAttribute('id')],
        );

        // Estornos sao negativos no livro, entao a soma ja vem com o sinal.
        return max(0, (int) $credito->getAttribute('pontos') + (int) $linha->total);
    }

    private function competencia(mixed $valor): DateTimeImmutable
    {
        if ($valor instanceof DateTimeImmutable) {
            return $valor;
        }

        $momento = date_create_immutable((string) $valor, new DateTimeZone('UTC'));

        if ($momento === false) {
            throw new RuntimeException("Competencia ilegivel no lancamento confirmado: '{$valor}'.");
        }

        return $momento;
    }

    private function inteiroOuNulo(mixed $valor): ?int
    {
        return $valor === null ? null : (int) $valor;
    }
}
