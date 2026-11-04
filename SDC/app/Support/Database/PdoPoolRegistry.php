<?php

declare(strict_types=1);

namespace App\Support\Database;

use Closure;
use Illuminate\Contracts\Config\Repository;
use InvalidArgumentException;

/**
 * Pools de PDO por conexao, um registro por worker (database.coroutine_pools).
 *
 * Cada conexao listada ganha um pool PROPRIO: o esgotamento do pool de log nao
 * consome capacidade do pool critico, e vice-versa. Os pools sao criados sob
 * demanda; so o aquecer() os instancia no boot do worker, e cada um com o seu
 * try/catch, para que uma conexao fora do ar nao impeca o aquecimento da outra.
 */
final class PdoPoolRegistry
{
    /** @var array<string, PdoPool> */
    private array $pools = [];

    /** @var array<string, array<string, PdoPool>> */
    private array $poolsDeLeitura = [];

    private readonly Closure $fabricaDeLeitura;

    /**
     * @param  array<string, array{conexao: array<string, mixed>, size: int, timeout: float, warm: bool, read_size: int}>  $definicoes
     * @param  Closure(string, array<string, mixed>, int, float): PdoPool  $fabrica  (label, config, tamanho, timeout)
     * @param  (Closure(string, array<string, mixed>, int, float): PdoPool)|null  $fabricaDeLeitura  mesma assinatura; default $fabrica
     */
    public function __construct(
        private readonly array $definicoes,
        private readonly Closure $fabrica,
        ?Closure $fabricaDeLeitura = null,
    ) {
        $this->fabricaDeLeitura = $fabricaDeLeitura ?? $fabrica;
    }

    /**
     * Le database.coroutine_pools. Nome sem conexao correspondente, ou com
     * driver diferente de pgsql, e ignorado: cai no DatabaseManager padrao.
     */
    public static function fromConfig(Repository $config): self
    {
        $definicoes = [];
        foreach ((array) $config->get('database.coroutine_pools', []) as $nome => $pool) {
            $conexao = $config->get("database.connections.{$nome}");
            if (! is_array($conexao) || ($conexao['driver'] ?? null) !== 'pgsql') {
                continue;
            }

            $pool = (array) $pool;
            $definicoes[(string) $nome] = [
                'conexao' => $conexao,
                'size' => (int) ($pool['size'] ?? 16),
                'timeout' => (float) ($pool['timeout'] ?? 3.0),
                'warm' => (bool) ($pool['warm'] ?? false),
                'read_size' => (int) ($pool['read_size'] ?? 0),
            ];
        }

        return new self(
            $definicoes,
            static fn (string $label, array $conexao, int $size, float $timeout): PdoPool => SwoolePdoPool::fromConfig($conexao, $size, $timeout, $label),
        );
    }

    public function gerencia(string $nome): bool
    {
        return isset($this->definicoes[$nome]);
    }

    /**
     * @return list<string>
     */
    public function nomes(): array
    {
        return array_keys($this->definicoes);
    }

    public function pool(string $nome): PdoPool
    {
        $definicao = $this->definicao($nome);

        return $this->pools[$nome] ??= ($this->fabrica)(
            $nome,
            $definicao['conexao'],
            $definicao['size'],
            $definicao['timeout'],
        );
    }

    /**
     * Pools das replicas, por host. Vazio quando a conexao nao tem
     * replica_routing. read_size abaixo de 1 vira 1, como antes da
     * generalizacao: sem pool a ConsistentPostgresConnection abriria um leitor
     * por coroutine, sem teto, e o guardrail do entrypoint nao conta isso.
     *
     * @return array<string, PdoPool>
     */
    public function poolsDeLeitura(string $nome): array
    {
        if (isset($this->poolsDeLeitura[$nome])) {
            return $this->poolsDeLeitura[$nome];
        }

        $definicao = $this->definicoes[$nome] ?? null;
        $conexao = $definicao['conexao'] ?? [];
        if ($definicao === null || ! ($conexao['replica_routing'] ?? false)) {
            return $this->poolsDeLeitura[$nome] = [];
        }

        $tamanho = max(1, (int) $definicao['read_size']);
        $leitura = array_replace($conexao, (array) ($conexao['read'] ?? []));
        $pools = [];
        foreach ((array) $leitura['host'] as $host) {
            $pools[$host] = ($this->fabricaDeLeitura)(
                "{$nome}:leitura",
                array_replace($leitura, ['host' => $host]),
                $tamanho,
                $definicao['timeout'],
            );
        }

        return $this->poolsDeLeitura[$nome] = $pools;
    }

    /**
     * Pre-cria as conexoes dos pools marcados com warm. Falha de um pool nao
     * impede os demais; as conexoes que faltarem sobem sob demanda.
     */
    public function aquecer(): void
    {
        foreach ($this->definicoes as $nome => $definicao) {
            if (! $definicao['warm']) {
                continue;
            }

            try {
                $this->pool($nome)->warm();
            } catch (\Throwable) {
                // Pool e otimizacao; o worker sobe mesmo sem ele aquecido.
            }
        }
    }

    /**
     * Estado de cada pool configurado. Nao instancia pool nenhum: o que ainda
     * nao foi usado aparece com instantiated=false.
     *
     * @return array<string, array<string, mixed>>
     */
    public function diagnostico(): array
    {
        $saida = [];
        foreach ($this->definicoes as $nome => $definicao) {
            $saida[$nome] = [
                'configured_size' => $definicao['size'],
                'configured_timeout_seconds' => $definicao['timeout'],
                'warm' => $definicao['warm'],
                'configured_read_size' => $definicao['read_size'],
                'instantiated' => isset($this->pools[$nome]),
                ...self::estado($this->pools[$nome] ?? null),
                'read_pools' => array_map(
                    static fn (PdoPool $pool): array => self::estado($pool),
                    $this->poolsDeLeitura[$nome] ?? [],
                ),
            ];
        }

        return $saida;
    }

    /**
     * @return array{capacity: ?int, created: ?int, available: ?int, timeout_seconds: ?float}
     */
    public static function estado(?PdoPool $pool): array
    {
        return [
            'capacity' => $pool?->capacity(),
            'created' => $pool?->created(),
            'available' => $pool?->available(),
            'timeout_seconds' => $pool?->timeout(),
        ];
    }

    /**
     * @return array{conexao: array<string, mixed>, size: int, timeout: float, warm: bool, read_size: int}
     */
    private function definicao(string $nome): array
    {
        return $this->definicoes[$nome]
            ?? throw new InvalidArgumentException("Conexao '{$nome}' nao tem pool em database.coroutine_pools.");
    }
}
