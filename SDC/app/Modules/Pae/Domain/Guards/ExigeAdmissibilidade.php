<?php

declare(strict_types=1);

namespace App\Modules\Pae\Domain\Guards;

use App\Modules\Pae\Domain\Contracts\GuardaTransicaoPae;
use App\Modules\Pae\Domain\ContextoTransicao;
use App\Modules\Pae\Domain\Exceptions\TransicaoProibidaException;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeProtocolo;

final class ExigeAdmissibilidade implements GuardaTransicaoPae
{
    public function check(PaeProtocolo $protocolo, PaeProtocoloStatus $novo, ContextoTransicao $contexto): void
    {
        if ($novo === PaeProtocoloStatus::REPROVADO_SUMARIAMENTE) {
            if (! $contexto->ehDecisaoAdmissibilidade()
                || $protocolo->decisoesAdmissibilidade()->first()?->tipo !== 'reprovado_sumariamente') {
                throw new TransicaoProibidaException('A reprovação sumária exige decisão de admissibilidade registrada.');
            }

            return;
        }

        if (! in_array($novo, [PaeProtocoloStatus::CRIACAO_SDC, PaeProtocoloStatus::CCPAE], true)) {
            return;
        }

        $decisao = $protocolo->decisoesAdmissibilidade()->first();
        if ($protocolo->admissibilidade_legada_sem_triagem
            && $protocolo->admissibilidade_triagem_versao === 0
            && $decisao === null) {
            return;
        }

        if ($decisao?->tipo !== 'admitido') {
            throw new TransicaoProibidaException('Registre a admissão antes de avançar o PAE.');
        }

        if ($protocolo->admissibilidade_triagem_versao !== $decisao->triagem_versao) {
            throw new TransicaoProibidaException('A triagem foi alterada após a admissão; registre nova decisão.');
        }
    }
}
