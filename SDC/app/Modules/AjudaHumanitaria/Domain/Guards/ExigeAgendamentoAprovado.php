<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Guards;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\GuardaTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;

/**
 * RN-21: o pedido so passa a Atendido com agendamento de retirada aprovado.
 */
final class ExigeAgendamentoAprovado implements GuardaTransicao
{
    public function verificar(ContextoTransicao $contexto): ResultadoGuarda
    {
        if (! $contexto->ehTransicao(StatusPedidoAh::AguardandoRetirada, StatusPedidoAh::Atendido)) {
            return ResultadoGuarda::permitir();
        }

        if ($contexto->agendamentoAprovado) {
            return ResultadoGuarda::permitir();
        }

        return ResultadoGuarda::bloquear(
            'É necessário um agendamento de retirada aprovado para marcar o pedido como atendido.'
        );
    }
}
