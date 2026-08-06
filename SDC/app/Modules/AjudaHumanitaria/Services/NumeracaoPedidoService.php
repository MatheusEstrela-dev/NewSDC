<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Domain\Repositories\PedidoAhRepositoryInterface;

/**
 * RN-01: numeracao sequencial por ano.
 *
 * O legado tem dois pares numero mais ano duplicados, sinal de que a regra
 * existia sem ser aplicada. Aqui a unicidade e garantida pela constraint do
 * banco; este servico apenas calcula o proximo valor, e quem cria o pedido
 * trata a violacao com nova tentativa.
 */
final class NumeracaoPedidoService
{
    public function __construct(
        private readonly PedidoAhRepositoryInterface $pedidos,
    ) {}

    public function proximoNumero(int $ano): int
    {
        return $this->pedidos->proximoNumeroDoAno($ano);
    }

    public function anoCorrente(): int
    {
        return (int) date('Y');
    }
}
