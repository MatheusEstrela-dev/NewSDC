<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Pae\Services\PaeNotificacaoService;
use Illuminate\Console\Command;

class VerificarNotificacoesPae extends Command
{
    protected $signature = 'pae:verificar-notificacoes';

    protected $description = 'Processa vencimentos dos ciclos de notificacao PAE (30 dias): emite a proxima notificacao ou suspende o protocolo apos a terceira.';

    public function handle(PaeNotificacaoService $service): int
    {
        $processadas = $service->processarVencimentos();

        $this->info("Notificacoes PAE processadas: {$processadas}.");

        return self::SUCCESS;
    }
}
