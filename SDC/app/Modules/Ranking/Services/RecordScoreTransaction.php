<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\DTOs\ScoreDecisionData;
use App\Modules\Ranking\Enums\DecisaoPontuacao;
use App\Modules\Ranking\Enums\EscopoPlacar;
use App\Modules\Ranking\Enums\FaixaRanking;
use App\Modules\Ranking\Enums\TipoPeriodo;
use App\Modules\Ranking\Models\Transacao;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Caminho de ESCRITA do livro de pontos.
 *
 * Grava, numa unica transacao de banco na conexao `ranking`:
 *   1. a decisao em ranking.transacoes;
 *   2. o lancamento correspondente em ranking.lancamentos (livro append-only);
 *   3. a projecao de leitura em ranking.saldos, nas tres dimensoes.
 *
 * IDEMPOTENCIA POR DUPLA BARREIRA
 * O schema tem UNIQUE em transacoes.event_id (barreira tecnica: mesmo evento
 * reentregue, replay de fila, reprocessamento manual) e UNIQUE em
 * (chave_canonica, familia) (barreira de negocio: mesmo marco, mesmo ciclo,
 * mesmo recurso, ainda que por outro evento, outra rota ou outro usuario).
 *
 * As duas sao tratadas com INSERT ... ON CONFLICT DO NOTHING, e NAO capturando
 * QueryException. A diferenca importa: no PostgreSQL uma violacao de unicidade
 * aborta a transacao inteira, e um catch dentro do DB::transaction() deixaria a
 * conexao num estado em que todo comando seguinte falha com
 * "current transaction is aborted". ON CONFLICT resolve o conflito no proprio
 * comando, sem abortar nada, e ainda espera a transacao concorrente terminar
 * antes de decidir - o que torna a barreira valida tambem sob concorrencia.
 *
 * Repetir o mesmo fato devolve a transacao ja gravada e nao move saldo: o saldo
 * so anda quando o INSERT do lancamento efetivamente cria linha nova.
 *
 * CUIDADO COM OCTANE: este service e stateless de proposito. Geracao ativa e
 * rotulo de total chegam pelo construtor ou por argumento, nunca de config() ou
 * Auth em tempo de execucao, porque o singleton sobrevive entre requests do
 * worker e estado guardado aqui vazaria de um usuario para o proximo.
 */
class RecordScoreTransaction
{
    /** Conexao dedicada. Nunca herda a conexao default. */
    public const CONEXAO = 'ranking';

    /**
     * Rotulo literal da linha de total em ranking.saldos.
     *
     * Valor explicito, nunca NULL: NULL nao participa de UNIQUE no PostgreSQL e
     * permitiria duas linhas de total para a mesma entidade passando pela
     * constraint uq_ranking_saldos.
     */
    public const MODULO_TOTAL = 'all';

    public const NATUREZA_CREDITO = 'credito';

    public const NATUREZA_ESTORNO = 'estorno';

    /** Limite de ranking.lancamentos.entry_key (varchar(220)). */
    private const TAMANHO_ENTRY_KEY = 220;

    public function __construct(
        private readonly int $geracaoPadrao = 1,
    ) {}

    /**
     * Registra a decisao e projeta o saldo. Idempotente por event_id e por
     * (chave_canonica, familia).
     *
     * @param array<string, mixed> $contexto
     */
    public function registrar(
        string $eventId,
        string $eventName,
        string $chaveCanonica,
        string $familia,
        string $modulo,
        ScoreDecisionData $decisao,
        DateTimeImmutable $ocorridoEm,
        DateTimeImmutable $competenciaEm,
        ?int $regraId = null,
        ?int $regraVersao = null,
        ?int $actorUserId = null,
        ?int $creditedUserId = null,
        ?int $validadorUserId = null,
        ?int $orgaoId = null,
        ?int $municipioId = null,
        array $contexto = [],
        ?int $geracao = null,
    ): Transacao {
        $this->validarEntrada($eventId, $eventName, $chaveCanonica, $familia, $modulo, $decisao, $regraId);

        $geracao ??= $this->geracaoPadrao;

        return DB::connection(self::CONEXAO)->transaction(function () use (
            $eventId, $eventName, $chaveCanonica, $familia, $modulo, $decisao,
            $ocorridoEm, $competenciaEm, $regraId, $regraVersao, $actorUserId,
            $creditedUserId, $validadorUserId, $orgaoId, $municipioId, $contexto, $geracao
        ): Transacao {
            $transacaoId = $this->inserirTransacao(
                $eventId, $eventName, $chaveCanonica, $familia, $regraId, $decisao,
                $actorUserId, $creditedUserId, $validadorUserId, $orgaoId, $municipioId,
                $ocorridoEm, $competenciaEm, $contexto,
            );

            // Barreira disparou: o fato ja foi avaliado. Devolve a decisao
            // original sem tocar no livro nem no placar. Nao reaproveita os
            // pontos que chegaram agora porque a transacao gravada e a
            // autoridade - inclusive sobre quem foi creditado.
            if ($transacaoId === null) {
                return $this->transacaoExistente($eventId, $chaveCanonica, $familia);
            }

            // Zero e em_apuracao nao geram linha no livro: a decisao ja esta
            // registrada e o livro so guarda movimento de pontos.
            if ($decisao->pontosTotais() > 0) {
                $lancamentoId = $this->inserirLancamento(
                    $transacaoId,
                    $this->entryKey($chaveCanonica, $familia, self::NATUREZA_CREDITO),
                    $creditedUserId, $orgaoId, $municipioId, $modulo,
                    $regraId, $regraVersao,
                    $decisao->pontosBase, $decisao->pontosBonus, $decisao->pontosTotais(),
                    $competenciaEm,
                );

                // O saldo so anda quando o livro andou. Pendente, zero e
                // apuracao devolvem 0 em pontosParaSaldo(); confirmar depois e
                // um lancamento novo, nunca um UPDATE neste.
                if ($lancamentoId !== null && $decisao->pontosParaSaldo() > 0) {
                    $this->aplicarNoPlacar(
                        $decisao->pontosParaSaldo(),
                        $creditedUserId, $orgaoId, $municipioId,
                        $modulo, $competenciaEm, $geracao,
                    );
                }
            }

            return Transacao::query()->findOrFail($transacaoId);
        });
    }

    /**
     * Chave deterministica do lancamento a partir de (chave_canonica, familia,
     * natureza). E UNIQUE no schema e por isso e a segunda barreira contra
     * premio duplicado.
     *
     * A forma legivel e preferida para auditoria; quando a concatenacao passa
     * dos 220 caracteres da coluna, o excedente vira sha1 do texto integral -
     * continua deterministico e continua unico.
     */
    public function entryKey(string $chaveCanonica, string $familia, string $natureza): string
    {
        $bruta = $chaveCanonica . '|' . $familia . '|' . $natureza;

        if (strlen($bruta) <= self::TAMANHO_ENTRY_KEY) {
            return $bruta;
        }

        $digest = sha1($bruta);

        return substr($bruta, 0, self::TAMANHO_ENTRY_KEY - strlen($digest) - 1) . '|' . $digest;
    }

    /**
     * Resolve (criando se preciso) os tres periodos da competencia.
     *
     * O MESMO lancamento entra nos tres: mes, ano e acumulado. Nao sao tres
     * premios - sao tres recortes de leitura do mesmo fato.
     *
     * @return array<int, int> ids de ranking.periodos
     */
    public function resolverPeriodos(DateTimeImmutable $competencia): array
    {
        $conexao = DB::connection(self::CONEXAO);
        $ids = [];

        foreach (TipoPeriodo::cases() as $tipo) {
            $chave = $tipo->chave($competencia);
            [$inicio, $fim] = $tipo->limites($competencia);

            $conexao->statement(
                'INSERT INTO ranking.periodos (tipo, chave, inicia_em, termina_em)
                 VALUES (?, ?, ?::timestamptz, ?::timestamptz)
                 ON CONFLICT (chave) DO NOTHING',
                [$tipo->value, $chave, $this->instante($inicio), $this->instante($fim)],
            );

            $linha = $conexao->selectOne('SELECT id FROM ranking.periodos WHERE chave = ?', [$chave]);

            if ($linha === null) {
                throw new RuntimeException("Periodo '{$chave}' nao pode ser resolvido.");
            }

            $ids[] = (int) $linha->id;
        }

        return $ids;
    }

    /**
     * Soma (ou subtrai, quando $pontos e negativo) a projecao de leitura nas
     * tres dimensoes, em todos os periodos da competencia, na linha do modulo e
     * na linha de total.
     *
     * UPSERT com `pontos = saldos.pontos + EXCLUDED.pontos`, jamais
     * read-modify-write: dois lancamentos concorrentes para a mesma entidade
     * leriam o mesmo saldo e um sobrescreveria o incremento do outro. Aqui a
     * soma acontece dentro do proprio UPDATE, sob o lock da linha.
     *
     * Municipio nulo (usuario de orgao estadual) simplesmente nao gera linha
     * municipal - nao gera linha com entidade zero nem com entidade nula.
     */
    public function aplicarNoPlacar(
        int $pontos,
        ?int $usuarioId,
        ?int $orgaoId,
        ?int $municipioId,
        string $modulo,
        DateTimeImmutable $competencia,
        ?int $geracao = null,
    ): void {
        if ($pontos === 0) {
            return;
        }

        $entidades = array_filter(
            [
                EscopoPlacar::Usuario->value => $usuarioId,
                EscopoPlacar::Orgao->value => $orgaoId,
                EscopoPlacar::Municipio->value => $municipioId,
            ],
            static fn (?int $id): bool => $id !== null,
        );

        if ($entidades === []) {
            return;
        }

        $geracao ??= $this->geracaoPadrao;
        $periodos = $this->resolverPeriodos($competencia);

        // Linha do modulo + linha de total. Se o chamador ja pediu o rotulo de
        // total, grava uma linha so: duas linhas identicas no mesmo INSERT
        // fariam o PostgreSQL recusar o DO UPDATE ("cannot affect row a second
        // time"), e contariam o ponto duas vezes se passassem.
        $modulos = $modulo === self::MODULO_TOTAL
            ? [self::MODULO_TOTAL]
            : [self::MODULO_TOTAL, $modulo];

        $faixaInicial = FaixaRanking::deSaldo($pontos)->value;
        $linhas = [];
        $valores = [];

        foreach ($periodos as $periodoId) {
            foreach ($entidades as $escopo => $entidadeId) {
                foreach ($modulos as $rotulo) {
                    $linhas[] = '(?, ?, ?, ?, ?, ?, ?, now())';
                    array_push($valores, $geracao, $periodoId, $escopo, $entidadeId, $rotulo, $pontos, $faixaInicial);
                }
            }
        }

        $total = 'saldos.pontos + EXCLUDED.pontos';

        DB::connection(self::CONEXAO)->statement(
            'INSERT INTO ranking.saldos
                (geracao, periodo_id, escopo, entidade_id, modulo, pontos, faixa, atualizado_em)
             VALUES ' . implode(', ', $linhas) . '
             ON CONFLICT (geracao, periodo_id, escopo, entidade_id, modulo) DO UPDATE SET
                pontos = ' . $total . ',
                faixa = ' . $this->expressaoFaixa($total) . ',
                atualizado_em = now()',
            $valores,
        );
    }

    /**
     * Grava um lancamento ja resolvido pelo chamador (o estorno usa este
     * caminho, porque copia usuario, orgao, municipio e competencia do credito
     * original). Existe para que o INSERT no livro fique num lugar so.
     *
     * @return int|null id inserido, ou null quando o entry_key ja existia
     */
    public function gravarLancamento(
        int $transacaoId,
        string $entryKey,
        ?int $creditedUserId,
        ?int $orgaoId,
        ?int $municipioId,
        string $modulo,
        ?int $regraId,
        ?int $regraVersao,
        int $pontosBase,
        int $pontosBonus,
        DateTimeImmutable $competenciaEm,
        ?int $estornoDeId = null,
    ): ?int {
        return $this->inserirLancamento(
            $transacaoId, $entryKey, $creditedUserId, $orgaoId, $municipioId, $modulo,
            $regraId, $regraVersao, $pontosBase, $pontosBonus, $pontosBase + $pontosBonus,
            $competenciaEm, $estornoDeId,
        );
    }

    /**
     * @param array<string, mixed> $contexto
     * @return int|null id inserido, ou null quando uma das barreiras impediu
     */
    private function inserirTransacao(
        string $eventId,
        string $eventName,
        string $chaveCanonica,
        string $familia,
        ?int $regraId,
        ScoreDecisionData $decisao,
        ?int $actorUserId,
        ?int $creditedUserId,
        ?int $validadorUserId,
        ?int $orgaoId,
        ?int $municipioId,
        DateTimeImmutable $ocorridoEm,
        DateTimeImmutable $competenciaEm,
        array $contexto,
    ): ?int {
        $linhas = DB::connection(self::CONEXAO)->select(
            'INSERT INTO ranking.transacoes
                (event_id, event_name, chave_canonica, familia, regra_id, decisao, motivo,
                 actor_user_id, credited_user_id, validador_user_id, orgao_id, municipio_id,
                 ocorrido_em, competencia_em, contexto)
             VALUES (?::uuid, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?::timestamptz, ?::timestamptz, ?::jsonb)
             ON CONFLICT DO NOTHING
             RETURNING id',
            [
                $eventId,
                $eventName,
                $chaveCanonica,
                $familia,
                $regraId,
                $decisao->decisao->value,
                substr(trim($decisao->motivo), 0, 80),
                $actorUserId,
                $creditedUserId,
                $validadorUserId,
                $orgaoId,
                $municipioId,
                $this->instante($ocorridoEm),
                $this->instante($competenciaEm),
                json_encode($contexto, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            ],
        );

        return $linhas === [] ? null : (int) $linhas[0]->id;
    }

    /** @return int|null id inserido, ou null quando o entry_key ja existia */
    private function inserirLancamento(
        int $transacaoId,
        string $entryKey,
        ?int $creditedUserId,
        ?int $orgaoId,
        ?int $municipioId,
        string $modulo,
        ?int $regraId,
        ?int $regraVersao,
        int $pontosBase,
        int $pontosBonus,
        int $pontos,
        DateTimeImmutable $competenciaEm,
        ?int $estornoDeId = null,
    ): ?int {
        $linhas = DB::connection(self::CONEXAO)->select(
            'INSERT INTO ranking.lancamentos
                (transacao_id, entry_key, credited_user_id, orgao_id, municipio_id, modulo,
                 regra_id, regra_versao, pontos_base, pontos_bonus, pontos, competencia_em, estorno_de_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?::timestamptz, ?)
             ON CONFLICT (entry_key) DO NOTHING
             RETURNING id',
            [
                $transacaoId, $entryKey, $creditedUserId, $orgaoId, $municipioId, $modulo,
                $regraId, $regraVersao, $pontosBase, $pontosBonus, $pontos,
                $this->instante($competenciaEm), $estornoDeId,
            ],
        );

        return $linhas === [] ? null : (int) $linhas[0]->id;
    }

    private function transacaoExistente(string $eventId, string $chaveCanonica, string $familia): Transacao
    {
        $linha = DB::connection(self::CONEXAO)->selectOne(
            'SELECT id FROM ranking.transacoes
             WHERE event_id = ?::uuid OR (chave_canonica = ? AND familia = ?)
             ORDER BY id
             LIMIT 1',
            [$eventId, $chaveCanonica, $familia],
        );

        if ($linha === null) {
            throw new RuntimeException(
                'Conflito de unicidade sem linha correspondente em ranking.transacoes: '
                . "event_id={$eventId} chave={$chaveCanonica} familia={$familia}."
            );
        }

        return Transacao::query()->findOrFail((int) $linha->id);
    }

    /**
     * CASE SQL derivado de FaixaRanking. Os limiares vivem no enum; repeti-los
     * em SQL literal faria a faixa do placar divergir da faixa do dominio na
     * primeira vez que um limiar mudasse.
     */
    private function expressaoFaixa(string $expressaoTotal): string
    {
        $faixas = FaixaRanking::cases();
        usort($faixas, static fn (FaixaRanking $a, FaixaRanking $b): int => $b->pontosMinimos() <=> $a->pontosMinimos());

        $sql = 'CASE';

        foreach ($faixas as $faixa) {
            if ($faixa->pontosMinimos() <= 0) {
                continue;
            }

            $sql .= " WHEN {$expressaoTotal} >= {$faixa->pontosMinimos()} THEN '{$faixa->value}'";
        }

        return $sql . " ELSE '" . FaixaRanking::deSaldo(0)->value . "' END";
    }

    private function instante(?DateTimeInterface $momento): ?string
    {
        if ($momento === null) {
            return null;
        }

        return DateTimeImmutable::createFromInterface($momento)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:sP');
    }

    /**
     * Falha cedo, FORA da transacao de banco. Violacao de CHECK dentro do
     * DB::transaction() aborta a transacao no PostgreSQL e devolve ao chamador
     * um erro de driver em vez do defeito real do adaptador.
     */
    private function validarEntrada(
        string $eventId,
        string $eventName,
        string $chaveCanonica,
        string $familia,
        string $modulo,
        ScoreDecisionData $decisao,
        ?int $regraId,
    ): void {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $eventId) !== 1) {
            throw new InvalidArgumentException("event_id deve ser um UUID: '{$eventId}'.");
        }

        $limites = [
            'event_name' => [$eventName, 150],
            'chave_canonica' => [$chaveCanonica, 200],
            'familia' => [$familia, 60],
            'modulo' => [$modulo, 40],
        ];

        foreach ($limites as $campo => [$valor, $limite]) {
            if (trim($valor) === '') {
                throw new InvalidArgumentException("O campo {$campo} e obrigatorio.");
            }

            if (strlen($valor) > $limite) {
                throw new InvalidArgumentException("O campo {$campo} excede {$limite} caracteres.");
            }
        }

        // 'all' e o rotulo reservado da linha de total em ranking.saldos. Um
        // lancamento com esse modulo tornaria o total indistinguivel da parcela.
        if ($modulo === self::MODULO_TOTAL) {
            throw new InvalidArgumentException(
                "'" . self::MODULO_TOTAL . "' e reservado para a linha de total do placar."
            );
        }

        $semRegra = in_array($decisao->decisao, [DecisaoPontuacao::Zero, DecisaoPontuacao::EmApuracao], true);

        if ($regraId === null && ! $semRegra) {
            throw new InvalidArgumentException(
                "A decisao '{$decisao->decisao->value}' exige regra_id: so zero e em_apuracao pontuam sem catalogo."
            );
        }
    }
}
