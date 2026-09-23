<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Guards;

use App\Modules\Demandas\Domain\Contracts\GuardaTransicaoStatus;
use App\Modules\Demandas\Models\Task as Demanda;
use App\Modules\Demandas\Enums\StatusDemanda;

final readonly class ExigeAtribuicaoParaProgresso implements GuardaTransicaoStatus
{
    public function check(Demanda $demanda, StatusDemanda $novoStatus): bool
    {
        if ($novoStatus === StatusDemanda::EM_PROGRESSO && $demanda->atribuido_para_id === null) {
            throw new \DomainException('A demanda precisa estar atribuída a alguém antes de entrar em progresso.');
        }

        return true;
    }
}
