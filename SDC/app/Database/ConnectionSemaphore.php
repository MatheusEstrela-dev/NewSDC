<?php

declare(strict_types=1);

namespace App\Database;

use Illuminate\Redis\Connections\Connection;
use Swoole\Coroutine;

/**
 * Semaforo Redis-backed que limita o numero de requests concorrentes
 * batendo no DB. Funciona em qualquer ambiente: dev, staging, prod,
 * com ou sem PgBouncer externo.
 *
 * Uso:
 *   $owner = (string) Str::uuid();
 *   if (!$semaphore->acquire($owner)) {
 *       return response()->json([...], 503);
 *   }
 *   try {
 *       // processa request
 *   } finally {
 *       $semaphore->release($owner);
 *   }
 *
 * MODELO: um sorted set de posses, com o score sendo o instante (ms) em que
 * cada posse caduca. Nao existe contador separado. A versao anterior mantinha
 * um INCR/DECR em `db:slots:active` ao lado do set de owners, e so o set tinha
 * a expiracao renovada a cada acquire: sob carga continua o contador caducava
 * aos 60s enquanto as posses seguiam vivas, e cada release subsequente
 * decrementava um contador recem-criado ate leva-lo a NEGATIVO. Dai em diante
 * `active()` devolvia um numero negativo, o Backpressure calculava utilizacao
 * negativa e NUNCA descartava nada -- a protecao morria em silencio exatamente
 * sob a carga para a qual existe. Com um unico sorted set nao ha o que
 * dessincronizar: a posse e o dado, a contagem e derivada dela, e a posse de um
 * processo que morreu some sozinha quando o score vence.
 */
class ConnectionSemaphore
{
    private const KEY_OWNERS = 'db:slots:owners';
    private const TTL_SECONDS = 60;

    /** @var (callable():?Connection)|null Resolve a conexao no momento do uso. */
    private $redisResolver;

    public function __construct(
        ?callable $redisResolver,
        private int $limit,
        private int $waitMs = 50,
        private int $maxWaitMs = 2000,
    ) {
        $this->redisResolver = $redisResolver;
    }

    /**
     * Resolve a conexao Redis por-chamada (NAO captura no construtor). Sob
     * Swoole+pool, cada coroutine recebe a sua conexao; capturar uma unica vez
     * num singleton compartilharia o socket entre coroutines concorrentes.
     *
     * O tipo e a Connection do Laravel, nao o \Redis do phpredis: tipar a
     * extensao aqui obrigava o REDIS_CLIENT a ser phpredis, que e uma extensao
     * em C e portanto NAO cede execucao sob os hooks de corrotina do Swoole --
     * era esta classe que prendia hook_flags em 0 na configuracao inteira. A
     * Connection aceita phpredis e predis com a mesma assinatura de eval().
     */
    private function redis(): ?Connection
    {
        if ($this->redisResolver === null) {
            return null;
        }

        try {
            return ($this->redisResolver)();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Admissao atomica: limpa posses vencidas, confere o limite e registra a
     * propria posse -- tudo num round-trip so. Devolve 1 se admitiu, 0 se cheio.
     *
     * O relogio vem do TIME do proprio Redis, e nao do PHP: com varias replicas
     * do app, relogios diferentes escreveriam scores incomparaveis entre si e a
     * expiracao das posses ficaria a merce do drift entre containers.
     */
    private const LUA_ACQUIRE = <<<'LUA'
        local agora = redis.call('TIME')
        local agoraMs = (tonumber(agora[1]) * 1000) + math.floor(tonumber(agora[2]) / 1000)
        local ttlMs = tonumber(ARGV[1])
        local limite = tonumber(ARGV[2])

        redis.call('ZREMRANGEBYSCORE', KEYS[1], '-inf', agoraMs)

        if redis.call('ZCARD', KEYS[1]) >= limite then
            return 0
        end

        redis.call('ZADD', KEYS[1], agoraMs + ttlMs, ARGV[3])
        redis.call('PEXPIRE', KEYS[1], ttlMs * 2)

        return 1
    LUA;

    /**
     * Contagem viva: conta apenas as posses cujo score ainda nao venceu. E uma
     * leitura pura (ZCOUNT, sem remocao) porque roda no Backpressure a cada
     * request -- nao vale escrever no caminho quente so para podar.
     */
    private const LUA_ACTIVE = <<<'LUA'
        local agora = redis.call('TIME')
        local agoraMs = (tonumber(agora[1]) * 1000) + math.floor(tonumber(agora[2]) / 1000)

        return redis.call('ZCOUNT', KEYS[1], '(' .. agoraMs, '+inf')
    LUA;

    /**
     * Libera a PROPRIA posse e apenas ela. Vai por eval, e nao por um ZREM
     * direto, para que todo acesso a chave passe pelo mesmo caminho: o phpredis
     * nao aplica o prefixo do Laravel nos KEYS de um script, e misturar eval com
     * comando direto faria acquire e release mirarem chaves diferentes.
     */
    private const LUA_RELEASE = <<<'LUA'
        return redis.call('ZREM', KEYS[1], ARGV[1])
    LUA;

    public function acquire(string $owner): bool
    {
        // Sem Redis: no-op — concede sempre (backpressure desativado).
        $redis = $this->redis();
        if ($redis === null) {
            return true;
        }

        $prazo = $this->maxWaitMsEfetivo();
        $start = microtime(true);

        do {
            $admitido = (int) $redis->eval(
                self::LUA_ACQUIRE,
                1,
                self::KEY_OWNERS,
                (string) (self::TTL_SECONDS * 1000),
                (string) $this->limit,
                $owner,
            );

            if ($admitido === 1) {
                return true;
            }

            if ($prazo <= 0) {
                return false;
            }

            usleep($this->waitMs * 1000);
        } while ((microtime(true) - $start) * 1000 < $prazo);

        return false;
    }

    public function release(string $owner): void
    {
        $redis = $this->redis();
        if ($redis === null) {
            return;
        }

        $redis->eval(self::LUA_RELEASE, 1, self::KEY_OWNERS, $owner);
    }

    public function active(): int
    {
        $redis = $this->redis();
        if ($redis === null) {
            return 0;
        }

        return (int) $redis->eval(self::LUA_ACTIVE, 1, self::KEY_OWNERS);
    }

    public function limit(): int
    {
        return $this->limit;
    }

    /**
     * Quanto tempo faz sentido esperar por uma vaga.
     *
     * Dentro de uma coroutine com os hooks DESLIGADOS o usleep nao cede
     * execucao: ele congela o worker inteiro do Swoole, e a vaga que estamos
     * esperando so pode ser liberada por OUTRO worker. Como a capacidade em voo
     * e igual ao numero de workers nessa configuracao, esperar aqui gasta
     * justamente o recurso que esta em falta -- a fila de espera consome a
     * mesma coisa que a fila serve. Nesse caso a resposta correta e recusar na
     * hora (503 com Retry-After) e devolver o worker ao pool.
     *
     * Com hooks ligados o usleep vira sleep de corrotina e a espera custa
     * memoria, nao um worker: ai o prazo configurado vale.
     */
    private function maxWaitMsEfetivo(): int
    {
        if ($this->maxWaitMs <= 0) {
            return 0;
        }

        $emCoroutine = extension_loaded('swoole')
            && class_exists(Coroutine::class)
            && Coroutine::getCid() >= 0;

        if ($emCoroutine && (int) config('octane.swoole.options.hook_flags', 0) === 0) {
            return 0;
        }

        return $this->maxWaitMs;
    }
}
