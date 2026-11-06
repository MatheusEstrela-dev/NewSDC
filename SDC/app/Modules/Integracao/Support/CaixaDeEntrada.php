<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Support;

use App\Modules\Integracao\Exceptions\ChaveIdempotenteEmConflitoException;
use App\Support\Database\ConexaoPowerBi;
use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;
use Throwable;

/**
 * Unica classe que toca o schema entrada.* (sdc_powerbi). Mover a caixa para
 * outro banco e trocar a conexao aqui.
 *
 * LGPD: o payload vai cifrado (Crypt, APP_KEY) e vira NULL quando a mensagem
 * termina (aplicada, rejeitada ou morta); so entrada.mortas guarda o cifrado
 * para reprocessamento manual.
 *
 * Ordem por recurso: uma mensagem so e reivindicada se nao houver outra mais
 * antiga, nao terminal, com a mesma recurso_chave.
 */
final class CaixaDeEntrada
{
    private const NAO_TERMINAIS = "('recebida', 'processando', 'falhou')";

    private const SEM_ANTERIOR_PENDENTE = <<<'SQL'
        (m.recurso_chave IS NULL OR NOT EXISTS (
            SELECT 1 FROM entrada.mensagens a
            WHERE a.recurso_chave = m.recurso_chave
              AND a.status IN ('recebida', 'processando', 'falhou')
              AND (a.recebido_em, a.id) < (m.recebido_em, m.id)
        ))
        SQL;

    private const PRONTA = <<<'SQL'
        (
            (m.status IN ('recebida', 'falhou') AND coalesce(m.proxima_tentativa_em, '-infinity') <= now())
            OR (m.status = 'processando' AND m.trava_ate < now())
        )
        SQL;

    /**
     * Grava a mensagem. Com Idempotency-Key: mesma chave e mesmo hash devolvem
     * a mensagem original (repetida); hash diferente lanca conflito (409).
     *
     * @throws ChaveIdempotenteEmConflitoException
     */
    public function receber(NovaEntrada $entrada): ResultadoDoRecebimento
    {
        $cifrado = Crypt::encryptString(JsonCanonico::codificar($entrada->payload));

        // Segunda volta so se a chave sumir (expirou/podada) entre o conflito e a leitura.
        for ($volta = 0; $volta < 2; $volta++) {
            $resultado = $this->tentarReceber($entrada, $cifrado) ?? $this->repeticao($entrada);
            if ($resultado !== null) {
                return $resultado;
            }
        }

        throw new RuntimeException('Idempotency-Key instavel: tente novamente.');
    }

    public function reivindicar(string $id): ?MensagemReivindicada
    {
        $linha = $this->db()->selectOne(
            'UPDATE entrada.mensagens m
                SET status = \'processando\', trava_ate = now() + make_interval(secs => ?)
              WHERE m.id = ?::uuid AND '.self::PRONTA.' AND '.self::SEM_ANTERIOR_PENDENTE.'
          RETURNING m.id::text AS id, m.operacao, m.payload, m.rota, m.user_id, m.token_id,
                    m.tentativas, m.recurso_chave, m.trace_id',
            [(int) config('integracao.lease_segundos', 150), $id],
            false
        );

        if ($linha === null) {
            return null;
        }

        try {
            $payload = $linha->payload === null ? [] : self::json(Crypt::decryptString((string) $linha->payload));
        } catch (Throwable $e) {
            // APP_KEY trocada sem APP_PREVIOUS_KEYS: retentar nao resolve; vai para mortas com o cifrado.
            $this->db()->update('UPDATE entrada.mensagens SET erro = ? WHERE id = ?::uuid', ['Payload ilegivel: '.$e::class, $id]);
            $this->moverParaMortas($id);

            return null;
        }

        $rota = self::json($linha->rota);

        return new MensagemReivindicada(
            id: (string) $linha->id,
            operacao: (string) $linha->operacao,
            payload: $payload,
            parametrosRota: (array) ($rota['parametros'] ?? []),
            metodo: (string) ($rota['metodo'] ?? 'POST'),
            caminho: (string) ($rota['caminho'] ?? ''),
            userId: $linha->user_id !== null ? (int) $linha->user_id : null,
            tokenId: $linha->token_id !== null ? (int) $linha->token_id : null,
            tentativas: (int) $linha->tentativas,
            recursoChave: $linha->recurso_chave !== null ? (string) $linha->recurso_chave : null,
            traceId: $linha->trace_id !== null ? (string) $linha->trace_id : null,
        );
    }

    /** @param array<string, mixed> $resultado */
    public function marcarAplicada(string $id, array $resultado): void
    {
        $this->db()->update(
            'UPDATE entrada.mensagens
                SET status = \'aplicada\', resultado = ?::jsonb, aplicado_em = now(), payload = NULL,
                    trava_ate = NULL, proxima_tentativa_em = NULL, erro = NULL, erro_detalhe = NULL
              WHERE id = ?::uuid',
            [JsonCanonico::codificar((object) $resultado), $id]
        );
    }

    /**
     * Erro de negocio/validacao: nao adianta tentar de novo.
     *
     * @param  array<string, mixed>  $detalhe
     */
    public function marcarRejeitada(string $id, string $erro, array $detalhe = []): void
    {
        $this->db()->update(
            'UPDATE entrada.mensagens
                SET status = \'rejeitada\', erro = ?, erro_detalhe = ?::jsonb, payload = NULL,
                    trava_ate = NULL, proxima_tentativa_em = NULL
              WHERE id = ?::uuid',
            [mb_substr($erro, 0, 2000), JsonCanonico::codificar((object) $detalhe), $id]
        );
    }

    /**
     * Falha transitoria: soma a tentativa e agenda a proxima pelo backoff.
     *
     * @param  array<string, mixed>  $detalhe
     * @return int tentativas apos a falha
     */
    public function marcarFalha(string $id, string $erro, array $detalhe = []): int
    {
        $backoff = array_values(array_map('intval', (array) config('integracao.backoff', [60])));

        $linha = $this->db()->selectOne(
            'UPDATE entrada.mensagens
                SET status = \'falhou\', tentativas = tentativas + 1, trava_ate = NULL,
                    erro = ?, erro_detalhe = ?::jsonb,
                    proxima_tentativa_em = now() + make_interval(secs => (?::int[])[least(tentativas + 1, ?)])
              WHERE id = ?::uuid
          RETURNING tentativas',
            [
                mb_substr($erro, 0, 2000),
                JsonCanonico::codificar((object) $detalhe),
                '{'.implode(',', $backoff === [] ? [60] : $backoff).'}',
                max(1, count($backoff)),
                $id,
            ],
            false
        );

        return (int) ($linha->tentativas ?? 0);
    }

    /** Dead-letter: copia (com o payload cifrado) para entrada.mortas e encerra a mensagem. */
    public function moverParaMortas(string $id): void
    {
        $this->db()->transaction(function (Connection $db) use ($id): void {
            $db->insert(
                'INSERT INTO entrada.mortas (id, recebido_em, fonte, operacao, recurso_id, recurso_chave, token_id,
                        user_id, ip, chave_idempotente, hash_payload, payload, rota, cabecalhos, status, tentativas,
                        erro, erro_detalhe, trace_id)
                 SELECT id, recebido_em, fonte, operacao, recurso_id, recurso_chave, token_id,
                        user_id, ip, chave_idempotente, hash_payload, payload, rota, cabecalhos, \'morta\', tentativas,
                        erro, erro_detalhe, trace_id
                   FROM entrada.mensagens WHERE id = ?::uuid AND payload IS NOT NULL
                 ON CONFLICT (id, recebido_em) DO NOTHING',
                [$id]
            );
            $db->update(
                'UPDATE entrada.mensagens
                    SET status = \'morta\', payload = NULL, trava_ate = NULL, proxima_tentativa_em = NULL
                  WHERE id = ?::uuid',
                [$id]
            );
        });
    }

    /**
     * Visao do cliente: so o mesmo token ou o mesmo usuario enxerga.
     *
     * @return array<string, mixed>|null
     */
    public function status(string $id, ?int $tokenId, ?int $userId): ?array
    {
        $linha = $this->db()->selectOne(
            'SELECT id::text AS id, operacao, status, recebido_em, aplicado_em, resultado, erro, erro_detalhe, tentativas
               FROM entrada.mensagens
              WHERE id = ?::uuid AND (token_id = ? OR user_id = ?)',
            [$id, $tokenId, $userId],
            false
        );

        if ($linha === null) {
            return null;
        }

        return [
            'id'           => (string) $linha->id,
            'operacao'     => (string) $linha->operacao,
            'status'       => (string) $linha->status,
            'recebido_em'  => self::iso($linha->recebido_em),
            'aplicado_em'  => self::iso($linha->aplicado_em),
            'resultado'    => $linha->resultado !== null ? self::json($linha->resultado) : null,
            'erro'         => $linha->erro !== null ? (string) $linha->erro : null,
            'erro_detalhe' => $linha->erro_detalhe !== null ? self::json($linha->erro_detalhe) : null,
            'tentativas'   => (int) $linha->tentativas,
        ];
    }

    /**
     * Mensagens prontas e sem job: recebidas apos a carencia, falhas vencidas
     * e travas expiradas; so a cabeca de cada recurso_chave.
     *
     * @return list<string>
     */
    public function pendentesParaDespachar(int $limite): array
    {
        $carencia = (int) config('integracao.carencia_reenfileirar_segundos', 30);

        return array_map(
            static fn (object $l): string => (string) $l->id,
            $this->db()->select(
                'SELECT m.id::text AS id FROM entrada.mensagens m
                  WHERE '.self::PRONTA.'
                    AND (m.status <> \'recebida\' OR m.recebido_em <= now() - make_interval(secs => ?))
                    AND '.self::SEM_ANTERIOR_PENDENTE.'
                  ORDER BY m.recebido_em, m.id
                  LIMIT ?',
                [$carencia, $limite],
                false
            )
        );
    }

    /** Proxima nao terminal da fila de um recurso (null se a fila esvaziou). */
    public function proximaDoRecurso(string $recursoChave): ?string
    {
        $linha = $this->db()->selectOne(
            'SELECT id::text AS id FROM entrada.mensagens
              WHERE recurso_chave = ? AND status IN '.self::NAO_TERMINAIS.'
              ORDER BY recebido_em, id LIMIT 1',
            [$recursoChave],
            false
        );

        return $linha?->id !== null ? (string) $linha->id : null;
    }

    /**
     * Retencao: chaves idempotentes expiradas, particoes mensais anteriores a
     * retencao, linhas antigas da particao default e particoes futuras.
     *
     * @return array{chaves: int, particoes: list<string>, default: int}
     */
    public function podar(int $retencaoMeses, int $mesesAFrente): array
    {
        $db = $this->db();
        // Particoes nascem em limites UTC (entrada.criar_particao_mensal).
        $corte = CarbonImmutable::now('UTC')->startOfMonth()->subMonthsNoOverflow(max(1, $retencaoMeses));

        $chaves = $db->delete('DELETE FROM entrada.chaves_idempotentes WHERE expira_em <= now()');

        $removidas = [];
        $particoes = $db->select(
            "SELECT c.relname AS nome
               FROM pg_inherits i
               JOIN pg_class c ON c.oid = i.inhrelid
               JOIN pg_class p ON p.oid = i.inhparent
               JOIN pg_namespace n ON n.oid = p.relnamespace
              WHERE n.nspname = 'entrada' AND p.relname = 'mensagens' AND c.relname ~ '^mensagens_[0-9]{4}_[0-9]{2}$'"
        );
        foreach ($particoes as $particao) {
            $nome = (string) $particao->nome;
            if (substr($nome, -7) < $corte->format('Y_m')) {
                $db->statement(sprintf('DROP TABLE IF EXISTS entrada.%s', $db->getQueryGrammar()->wrap($nome)));
                $removidas[] = $nome;
            }
        }

        $default = $db->delete(
            'DELETE FROM entrada.mensagens_default WHERE recebido_em < ?::timestamptz',
            [$corte->toIso8601String()]
        );

        $db->statement('SELECT entrada.garantir_particoes(?)', [max(0, $mesesAFrente)]);

        return ['chaves' => $chaves, 'particoes' => $removidas, 'default' => $default];
    }

    /** Insere mensagem e reserva a chave; null quando a chave ja pertence a outra mensagem viva. */
    private function tentarReceber(NovaEntrada $entrada, string $cifrado): ?ResultadoDoRecebimento
    {
        $db = $this->db();
        $db->beginTransaction();

        try {
            $mensagem = $db->selectOne(
                'INSERT INTO entrada.mensagens (fonte, operacao, recurso_id, recurso_chave, token_id, user_id, ip,
                        chave_idempotente, hash_payload, payload, rota, cabecalhos, trace_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?::inet, ?, decode(?, \'hex\'), ?, ?::jsonb, ?::jsonb, ?)
                 RETURNING id::text AS id, recebido_em, trace_id',
                [
                    $entrada->fonte,
                    $entrada->operacao,
                    $entrada->recursoId,
                    $entrada->recursoChave,
                    $entrada->tokenId,
                    $entrada->userId,
                    $entrada->ip,
                    $entrada->chaveIdempotente,
                    $entrada->hashPayload,
                    $cifrado,
                    JsonCanonico::codificar([
                        'metodo'     => $entrada->metodo,
                        'caminho'    => $entrada->caminho,
                        'parametros' => (object) $entrada->parametrosRota,
                    ]),
                    JsonCanonico::codificar((object) $entrada->cabecalhos),
                    $entrada->traceId,
                ],
                false
            );

            if ($entrada->chaveIdempotente !== null && ! $this->reservarChave($db, $entrada, $mensagem)) {
                $db->rollBack();

                return null;
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();

            throw $e;
        }

        return new ResultadoDoRecebimento((string) $mensagem->id, (string) $mensagem->trace_id, false);
    }

    /** Chave expirada e ainda nao podada e reaproveitada (DO UPDATE ... WHERE expirou). */
    private function reservarChave(Connection $db, NovaEntrada $entrada, object $mensagem): bool
    {
        $reservada = $db->selectOne(
            'INSERT INTO entrada.chaves_idempotentes AS c (fonte, chave, mensagem_id, recebido_em, hash_payload, expira_em)
             VALUES (?, ?, ?::uuid, ?::timestamptz, decode(?, \'hex\'), now() + make_interval(hours => ?))
             ON CONFLICT (fonte, chave) DO UPDATE
                SET mensagem_id = EXCLUDED.mensagem_id, recebido_em = EXCLUDED.recebido_em,
                    hash_payload = EXCLUDED.hash_payload, expira_em = EXCLUDED.expira_em
              WHERE c.expira_em <= now()
             RETURNING mensagem_id',
            [
                $entrada->fonte,
                $this->escopoDaChave($entrada),
                $mensagem->id,
                $mensagem->recebido_em,
                $entrada->hashPayload,
                (int) config('integracao.chave_idempotente_ttl_horas', 24),
            ],
            false
        );

        return $reservada !== null;
    }

    /** @throws ChaveIdempotenteEmConflitoException */
    private function repeticao(NovaEntrada $entrada): ?ResultadoDoRecebimento
    {
        if ($entrada->chaveIdempotente === null) {
            return null;
        }

        $original = $this->db()->selectOne(
            'SELECT c.mensagem_id::text AS id, encode(c.hash_payload, \'hex\') AS hash, m.trace_id
               FROM entrada.chaves_idempotentes c
               LEFT JOIN entrada.mensagens m ON m.id = c.mensagem_id AND m.recebido_em = c.recebido_em
              WHERE c.fonte = ? AND c.chave = ? AND c.expira_em > now()',
            [$entrada->fonte, $this->escopoDaChave($entrada)],
            false
        );

        if ($original === null) {
            return null;
        }

        if (! hash_equals((string) $original->hash, $entrada->hashPayload)) {
            throw new ChaveIdempotenteEmConflitoException((string) $original->id);
        }

        return new ResultadoDoRecebimento((string) $original->id, (string) ($original->trace_id ?? ''), true);
    }

    /** A chave vale por cliente: dois tokens com a mesma chave nao colidem. */
    private function escopoDaChave(NovaEntrada $entrada): string
    {
        $dono = $entrada->tokenId !== null ? 't'.$entrada->tokenId : 'u'.(int) $entrada->userId;

        return $dono.':'.$entrada->chaveIdempotente;
    }

    private function db(): Connection
    {
        // Sem cache: sob pool por coroutine a Connection vale so para quem a obteve.
        return ConexaoPowerBi::escrita();
    }

    /** @return array<string, mixed> */
    private static function json(mixed $valor): array
    {
        if (is_array($valor)) {
            return $valor;
        }

        $decodificado = json_decode((string) $valor, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decodificado) ? $decodificado : [];
    }

    private static function iso(mixed $valor): ?string
    {
        return $valor === null ? null : CarbonImmutable::parse((string) $valor)->toIso8601String();
    }
}
