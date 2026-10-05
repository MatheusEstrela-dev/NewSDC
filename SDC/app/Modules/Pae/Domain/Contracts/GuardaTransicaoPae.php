<?php

declare(strict_types=1);

namespace App\Modules\Pae\Domain\Contracts;

use App\Modules\Pae\Domain\ContextoTransicao;
use App\Modules\Pae\Domain\Exceptions\TransicaoProibidaException;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeProtocolo;

/**
 * Regra que pode barrar uma transicao de status do protocolo PAE.
 * Registrada pela tag 'pae.guardas_transicao' no PaeServiceProvider.
 */
interface GuardaTransicaoPae
{
    /**
     * @throws TransicaoProibidaException
     */
    public function check(PaeProtocolo $protocolo, PaeProtocoloStatus $novo, ContextoTransicao $contexto): void;
}
