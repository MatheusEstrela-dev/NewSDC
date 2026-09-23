<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Console;

use Illuminate\Console\Command;
use App\Modules\Demandas\Services\SlaEngine;

class SlaVerificadorCommand extends Command
{
    protected $signature = 'demandas:verificar-slas';
    protected $description = 'Verifica e marca violações de prazo (SLA) nas demandas em andamento';

    public function handle(SlaEngine $engine): int
    {
        $this->info('Iniciando verificação de SLAs correntes...');
        
        $engine->verificarViolacoes();
        
        $this->info('Verificação concluída com sucesso.');
        
        return self::SUCCESS;
    }
}
