<?php

declare(strict_types=1);

namespace App\Core\Outbox;

use Illuminate\Database\Query\Builder;
use InvalidArgumentException;

/** Politica compartilhada pelo despachante e pelos comandos operacionais. */
final class OutboxRetryPolicy
{
    private readonly int $limite;

    /** @var list<int> */
    private readonly array $esperas;

    public function __construct()
    {
        $limite = (int) config('outbox.max_tentativas', 8);
        $esperas = config('outbox.backoff_segundos', [30, 120, 600, 1800, 3600]);
        if ($limite < 1 || $limite > 32767 || ! is_array($esperas) || $esperas === []) {
            throw new InvalidArgumentException('Politica de retentativas do outbox invalida.');
        }
        foreach ($esperas as $espera) {
            if (! is_int($espera) || $espera < 1) {
                throw new InvalidArgumentException('As esperas do outbox devem ser inteiros positivos em segundos.');
            }
        }
        $this->limite = $limite;
        $this->esperas = array_values($esperas);
    }

    public function limite(): int
    {
        return $this->limite;
    }

    public function elegiveis(Builder $query): Builder
    {
        return $query->whereNull('dispatched_at')
            ->where('dispatch_attempts', '<', $this->limite)
            ->where(function (Builder $query): void {
                $query->where('dispatch_attempts', 0)->orWhereNull('updated_at');
                foreach ($this->esperas as $indice => $espera) {
                    $operador = $indice === array_key_last($this->esperas) ? '>=' : '=';
                    $query->orWhere(function (Builder $query) use ($indice, $espera, $operador): void {
                        $query->where('dispatch_attempts', $operador, $indice + 1)
                            ->where('updated_at', '<=', now()->subSeconds($espera));
                    });
                }
            });
    }

    public function esgotados(Builder $query): Builder
    {
        return $query->whereNull('dispatched_at')->where('dispatch_attempts', '>=', $this->limite);
    }
}
