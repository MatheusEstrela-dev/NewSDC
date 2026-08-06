<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Specifications;

use Carbon\CarbonImmutable;

/**
 * RN-16: prazo da prestacao de contas e a data de aprovacao somada ao numero
 * de dias configurado no modulo.
 */
final class PrazoPrestacaoContas
{
    public function calcular(CarbonImmutable $dataAprovacao, int $prazoDias): CarbonImmutable
    {
        return $dataAprovacao->startOfDay()->addDays($prazoDias);
    }

    public function estaVencido(CarbonImmutable $dataLimite, CarbonImmutable $hoje): bool
    {
        return $hoje->startOfDay()->greaterThan($dataLimite->startOfDay());
    }
}
