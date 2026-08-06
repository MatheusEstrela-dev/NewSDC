<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Guards;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\GuardaTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;

/**
 * RN-11: avanco da analise DLOG para o diretor exige ao menos um parecer
 * favoravel. Devolver o pedido para correcao nao exige parecer.
 */
final class ExigeParecerFavoravel implements GuardaTransicao
{
    public function verificar(ContextoTransicao $contexto): ResultadoGuarda
    {
        if (! $contexto->ehTransicao(StatusPedidoAh::AnaliseDlog, StatusPedidoAh::AnaliseDiretorDlog)) {
            return ResultadoGuarda::permitir();
        }

        if ($contexto->temParecerFavoravel) {
            return ResultadoGuarda::permitir();
        }

        return ResultadoGuarda::bloquear(
            'É necessário ao menos um parecer favorável para encaminhar ao Diretor DLOG.'
        );
    }
}
