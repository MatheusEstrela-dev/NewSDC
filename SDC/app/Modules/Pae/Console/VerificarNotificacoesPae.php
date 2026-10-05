<?php

declare(strict_types=1);

namespace App\Modules\Pae\Console;

use App\Modules\Pae\Services\PaeNotificacaoService;
use App\Modules\Pae\Services\PaePrazoService;
use Illuminate\Console\Command;

class VerificarNotificacoesPae extends Command
{
    protected $signature = 'pae:verificar-notificacoes';

    protected $description = 'Processa os vencimentos das notificacoes PAE (Art. 11): renova ate o 3o ciclo, sinaliza ciclos esgotados para decisao da CEDEC e recalcula o prazo de analise das diligencias abertas (Art. 9).';

    public function handle(PaeNotificacaoService $notificacoes, PaePrazoService $prazos): int
    {
        $processadas = $notificacoes->processarVencimentos();
        $recalculados = $prazos->recalcularDiligenciasAbertas();

        $this->info("Notificacoes PAE processadas: {$processadas}. Prazos recalculados: {$recalculados}.");

        return self::SUCCESS;
    }
}
