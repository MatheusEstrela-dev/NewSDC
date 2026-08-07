<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Specifications;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda;

/**
 * RN-18: a soma das quantidades entregues aos beneficiarios nao pode exceder
 * a quantidade de material daquele item da prestacao.
 *
 * Equivale a verificaRestanteBenef do legado, que calculava o restante como
 * QtdMaterialPrest menos percBenef.
 */
final class SaldoEntregaBeneficiarios
{
    public function saldo(int $qtdMaterial, int $qtdJaEntregue): int
    {
        return max(0, $qtdMaterial - $qtdJaEntregue);
    }

    public function verificar(int $qtdMaterial, int $qtdJaEntregue, int $qtdNova): ResultadoGuarda
    {
        if ($qtdNova <= 0) {
            return ResultadoGuarda::bloquear('A quantidade entregue deve ser maior que zero.');
        }

        $saldo = $this->saldo($qtdMaterial, $qtdJaEntregue);

        if ($qtdNova > $saldo) {
            return ResultadoGuarda::bloquear(sprintf(
                'Quantidade acima do saldo disponível. Restam %d de %d.',
                $saldo,
                $qtdMaterial,
            ));
        }

        return ResultadoGuarda::permitir();
    }
}
