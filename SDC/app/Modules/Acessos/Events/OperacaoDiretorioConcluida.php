<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A operacao no AD chegou a um estado final (confirmado ou falhou). Leva so o
 * id: quem escuta rele a operacao. Disparado no worker, depois do commit.
 */
final class OperacaoDiretorioConcluida
{
    use Dispatchable;

    public function __construct(public readonly string $operacaoId) {}
}
