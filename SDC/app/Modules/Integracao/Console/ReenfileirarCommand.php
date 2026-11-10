<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Console;

use App\Modules\Integracao\Jobs\AplicarEntradaJob;
use App\Modules\Integracao\Support\CaixaDeEntrada;
use App\Support\Database\ConexaoEntrada;
use Illuminate\Console\Command;

final class ReenfileirarCommand extends Command
{
    protected $signature = 'integracao:reenfileirar {--limite= : Maximo de mensagens por execucao}';

    protected $description = 'Redespacha mensagens da caixa de entrada sem job (recebidas paradas, falhas vencidas, travas expiradas)';

    public function handle(CaixaDeEntrada $caixa): int
    {
        if (! ConexaoEntrada::ativa()) {
            $this->error('sdc_entrada desligado (DB_ENTRADA_CONNECTION).');

            return self::FAILURE;
        }

        $limite = (int) ($this->option('limite') ?: config('integracao.lote_reenfileirar', 500));
        $ids = $caixa->pendentesParaDespachar(max(1, $limite));

        foreach ($ids as $id) {
            AplicarEntradaJob::despachar($id);
        }

        $this->info(sprintf('Mensagens redespachadas: %d', count($ids)));

        return self::SUCCESS;
    }
}
