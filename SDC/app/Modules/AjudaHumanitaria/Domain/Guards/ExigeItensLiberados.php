<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Guards;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\GuardaTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;

/**
 * Nao se aprova pedido sem definir o que sera liberado.
 *
 * Trava ausente no legado. Sem itens liberados, a prestacao de contas nasceria
 * vazia na entrada em Atendido (RN-15).
 */
final class ExigeItensLiberados implements GuardaTransicao
{
    public function verificar(ContextoTransicao $contexto): ResultadoGuarda
    {
        if (! $contexto->ehTransicao(StatusPedidoAh::AnaliseDiretorDlog, StatusPedidoAh::Aprovado)) {
            return ResultadoGuarda::permitir();
        }

        if ($contexto->temItemLiberado) {
            return ResultadoGuarda::permitir();
        }

        return ResultadoGuarda::bloquear(
            'Defina as quantidades liberadas antes de aprovar o pedido.'
        );
    }
}
