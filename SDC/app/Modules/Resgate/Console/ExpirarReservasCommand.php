<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Console;

use App\Modules\Resgate\Services\PedidoResgate;
use Illuminate\Console\Command;

/**
 * Expira pedidos RESERVADOS cujo prazo venceu sem decisao da CEDEC,
 * devolvendo pontos e unidade. Cada expiracao vira evento de sistema na
 * trilha do pedido. Idempotente: pedido ja expirado nao e tocado de novo.
 */
final class ExpirarReservasCommand extends Command
{
    protected $signature = 'resgate:expirar-reservas';

    protected $description = 'Expira reservas de resgate vencidas e libera pontos e unidades';

    public function handle(PedidoResgate $pedidos): int
    {
        $total = $pedidos->expirarVencidos();
        $this->info("Reservas expiradas: {$total}.");

        return self::SUCCESS;
    }
}
