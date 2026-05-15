<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Outbox\OutboxDispatcher;
use Illuminate\Console\Command;

/**
 * Worker que consome o outbox em loop.
 *
 * Uso:
 *   php artisan outbox:dispatch              (loop, sai com Ctrl+C)
 *   php artisan outbox:dispatch --once       (1 batch e sai)
 *   php artisan outbox:dispatch --batch=200  (tamanho do batch)
 *
 * Em producao, supervisado pelo Horizon/systemd.
 */
class OutboxDispatch extends Command
{
    protected $signature = 'outbox:dispatch
        {--batch=100 : Eventos por iteracao}
        {--sleep=1 : Segundos entre iteracoes ociosas}
        {--once : Executa um batch e encerra}';

    protected $description = 'Despacha Domain Events pendentes do outbox para listeners idempotentes.';

    public function handle(OutboxDispatcher $dispatcher): int
    {
        $batch = max(1, (int) $this->option('batch'));
        $sleep = max(0, (int) $this->option('sleep'));
        $once = (bool) $this->option('once');

        $this->info("outbox:dispatch iniciado (batch={$batch}, once=".($once ? 'sim' : 'nao').')');

        do {
            $n = $dispatcher->dispatchPending($batch);
            if ($n > 0) {
                $this->info("Despachados: {$n} eventos");
            } elseif (! $once) {
                sleep($sleep);
            }
            if ($once) break;
        } while (! $this->shouldQuit());

        return self::SUCCESS;
    }

    private function shouldQuit(): bool
    {
        // Util para testes/CTRL+C
        return false;
    }
}
