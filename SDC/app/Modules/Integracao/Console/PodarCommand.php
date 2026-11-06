<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Console;

use App\Modules\Integracao\Support\CaixaDeEntrada;
use App\Support\Database\ConexaoPowerBi;
use Illuminate\Console\Command;

final class PodarCommand extends Command
{
    protected $signature = 'integracao:podar';

    protected $description = 'Retencao da caixa de entrada: chaves idempotentes expiradas, particoes antigas e particoes futuras';

    public function handle(CaixaDeEntrada $caixa): int
    {
        if (! ConexaoPowerBi::ativa()) {
            $this->error('sdc_powerbi desligado (DB_POWERBI_CONNECTION).');

            return self::FAILURE;
        }

        $r = $caixa->podar(
            (int) config('integracao.retencao_meses', 3),
            (int) config('integracao.particoes_a_frente', 2),
        );

        $this->info(sprintf(
            'Chaves expiradas: %d | particoes removidas: %s | linhas antigas na default: %d',
            $r['chaves'],
            $r['particoes'] === [] ? 'nenhuma' : implode(', ', $r['particoes']),
            $r['default']
        ));

        return self::SUCCESS;
    }
}
