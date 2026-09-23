<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Contracts;

use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Enums\StatusDemanda;

interface GuardaTransicaoStatus
{
    /**
     * Verifica se a demanda atende aos critérios necessários para a transição.
     * Retorna true se permitido, ou lança exception detalhando o bloqueio.
     *
     * @throws \DomainException
     */
    public function check(Demanda $demanda, StatusDemanda $novoStatus): bool;
}

