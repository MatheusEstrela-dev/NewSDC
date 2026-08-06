<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Guards;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\GuardaTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;

/**
 * Nao se marca um pedido como atendido sem definir o que foi liberado.
 *
 * A guarda protege a RN-15: a entrada em Atendido abre a prestacao de contas
 * copiando os itens tipo Liberado. Sem eles a prestacao nasceria vazia e o
 * ciclo nao teria como fechar.
 *
 * Posicionada na entrada de Atendido, e nao na aprovacao, por evidencia do
 * legado: dos 892 pedidos que chegaram a Atendido ou Finalizado, nenhum estava
 * sem itens liberados, enquanto 10 pedidos parados em Aguardando Retirada
 * estavam. Guardar a aprovacao bloquearia casos reais; guardar o atendimento
 * nao bloqueia nenhum.
 */
final class ExigeItensLiberados implements GuardaTransicao
{
    public function verificar(ContextoTransicao $contexto): ResultadoGuarda
    {
        if ($contexto->statusAlvo !== StatusPedidoAh::Atendido) {
            return ResultadoGuarda::permitir();
        }

        if ($contexto->temItemLiberado) {
            return ResultadoGuarda::permitir();
        }

        return ResultadoGuarda::bloquear(
            'Defina as quantidades liberadas antes de marcar o pedido como atendido.'
        );
    }
}
