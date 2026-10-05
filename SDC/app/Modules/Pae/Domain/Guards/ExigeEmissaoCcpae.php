<?php

declare(strict_types=1);

namespace App\Modules\Pae\Domain\Guards;

use App\Modules\Pae\Domain\Contracts\GuardaTransicaoPae;
use App\Modules\Pae\Domain\ContextoTransicao;
use App\Modules\Pae\Domain\Exceptions\TransicaoProibidaException;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeProtocolo;

/**
 * Status CCPAE so com certificado registrado (pae_ccpae): codigo, emissao e
 * vigencia. Sem isso ccpae/ccpae_venc ficavam vazios e o vencimento de 3 anos
 * (Arts. 4 e 5) nunca era calculado.
 */
final class ExigeEmissaoCcpae implements GuardaTransicaoPae
{
    public function check(PaeProtocolo $protocolo, PaeProtocoloStatus $novo, ContextoTransicao $contexto): void
    {
        if ($novo === PaeProtocoloStatus::CCPAE && ! $contexto->ehEmissaoCcpae()) {
            throw TransicaoProibidaException::semEmissaoCcpae();
        }
    }
}
