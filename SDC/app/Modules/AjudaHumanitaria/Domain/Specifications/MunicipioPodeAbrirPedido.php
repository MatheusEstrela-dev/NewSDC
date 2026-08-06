<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Specifications;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda;

/**
 * RN-03: o municipio nao abre pedido novo enquanto tiver um em edicao.
 *
 * O legado tinha duas versoes dessa regra. A que funcionava contava pedidos
 * com status 0 (buscaStatus); a outra, que contava status menor que 4
 * (compdecVerificaPedido), retornava sempre verdadeiro por erro de
 * implementacao. Esta specification reproduz a que funcionava.
 */
final class MunicipioPodeAbrirPedido
{
    public function verificar(bool $temPedidoEmEdicao): ResultadoGuarda
    {
        if (! $temPedidoEmEdicao) {
            return ResultadoGuarda::permitir();
        }

        return ResultadoGuarda::bloquear(
            'O município já possui um pedido em edição. Conclua ou cancele o pedido atual antes de abrir outro.'
        );
    }
}
