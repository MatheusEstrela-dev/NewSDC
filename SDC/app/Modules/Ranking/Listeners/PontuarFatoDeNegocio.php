<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Listeners;

use App\Core\Events\DomainEvent;
use App\Modules\Ranking\Contracts\ModuleAdapter;
use App\Modules\Ranking\Services\ProcessarFatoDoRanking;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

/**
 * Consumidor isolado. A idempotencia reside no livro da database ranking.
 * Nao grava processed_events na origem. Excecoes propagam para retry/failed_jobs;
 * falha de persistencia nunca pode ser reconhecida como consumo bem-sucedido.
 */
class PontuarFatoDeNegocio implements ShouldQueueAfterCommit
{
    // O Dispatcher inspeciona as propriedades sem executar o construtor.
    public int $timeout = 120;

    /** @param iterable<ModuleAdapter> $adaptadores */
    public function __construct(
        private readonly iterable $adaptadores,
        private readonly ProcessarFatoDoRanking $processador,
    ) {}

    public function tries(): int
    {
        return max(1, (int) config('ranking.fila.tentativas', 3));
    }

    public function viaConnection(): string
    {
        return 'redis-ranking';
    }

    public function viaQueue(): string
    {
        return (string) config('ranking.fila.nome', 'ranking');
    }

    public function backoff(): array
    {
        return config('ranking.fila.backoff_segundos', [10, 30, 60]);
    }

    public function handle(DomainEvent $event): void
    {
        foreach ($this->adaptadores as $adaptador) {
            if (! $adaptador->suporta($event)) {
                continue;
            }
            $fato = $adaptador->normalizar($event);
            if ($fato !== null) {
                $this->processador->processar($fato);
            }
            return;
        }
    }
}
