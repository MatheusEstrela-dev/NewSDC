<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Repositories;

/**
 * Leitura de saldo de material por deposito (RN-25).
 *
 * A implementacao da fase 2 le a base legada em modo somente leitura. Quando
 * o estoque virar nativo, basta trocar o bind: nada no dominio muda.
 *
 * disponivel() permite a interface degradar em vez de estourar quando a
 * conexao legada estiver fora do ar.
 */
interface SaldoMaterialRepositoryInterface
{
    /**
     * @return array<int, array{deposito: string, material: string, saldo: int}>
     */
    public function saldoPorDeposito(?string $codigoLegado = null): array;

    public function disponivel(): bool;
}
