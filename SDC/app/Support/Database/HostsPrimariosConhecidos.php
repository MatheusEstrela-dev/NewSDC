<?php

declare(strict_types=1);

namespace App\Support\Database;

use Closure;

/**
 * Hosts de leitura que se revelaram primarios, lembrados por poucos segundos
 * no worker. Evita que cada coroutine gaste um slot de leitura e um round trip
 * para redescobrir o lider. O prazo curto limita o custo de um failover: o
 * pior caso e pular, por alguns segundos, um host que ja voltou a ser replica.
 */
final class HostsPrimariosConhecidos
{
    /** @var array<string, float> */
    private array $validade = [];

    private readonly Closure $relogio;

    public function __construct(private readonly float $ttl = 5.0, ?Closure $relogio = null)
    {
        $this->relogio = $relogio ?? static fn (): float => microtime(true);
    }

    public function marcar(string $host): void
    {
        $this->validade[$host] = ($this->relogio)() + $this->ttl;
    }

    public function conhece(string $host): bool
    {
        $limite = $this->validade[$host] ?? null;
        if ($limite === null) {
            return false;
        }
        if (($this->relogio)() >= $limite) {
            unset($this->validade[$host]);

            return false;
        }

        return true;
    }
}
