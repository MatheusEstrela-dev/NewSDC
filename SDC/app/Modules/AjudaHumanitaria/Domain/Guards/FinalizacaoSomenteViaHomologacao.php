<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Guards;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\GuardaTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;

/**
 * RN-19: o processo so chega a Finalizado pela homologacao da prestacao de
 * contas, nunca por tramitacao manual.
 */
final class FinalizacaoSomenteViaHomologacao implements GuardaTransicao
{
    public function verificar(ContextoTransicao $contexto): ResultadoGuarda
    {
        if (! $contexto->ehTransicao(StatusPedidoAh::Atendido, StatusPedidoAh::Finalizado)) {
            return ResultadoGuarda::permitir();
        }

        if ($contexto->viaHomologacao) {
            return ResultadoGuarda::permitir();
        }

        return ResultadoGuarda::bloquear(
            'O processo é finalizado pela homologação da prestação de contas.'
        );
    }
}
