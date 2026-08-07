<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Guards;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\GuardaTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;

/**
 * Nao se envia pedido vazio para analise.
 *
 * Trava ausente no legado, onde era possivel tramitar um pedido sem nenhum
 * material solicitado.
 */
final class ExigeItemNoPedido implements GuardaTransicao
{
    public function verificar(ContextoTransicao $contexto): ResultadoGuarda
    {
        if (! $contexto->ehTransicao(StatusPedidoAh::EdicaoCompdec, StatusPedidoAh::AnaliseDlog)) {
            return ResultadoGuarda::permitir();
        }

        if ($contexto->temItemPedido) {
            return ResultadoGuarda::permitir();
        }

        return ResultadoGuarda::bloquear(
            'Inclua ao menos um material antes de enviar o pedido para análise.'
        );
    }
}
