<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Repositories;

use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;

/**
 * Persistencia do pedido e dos fatos que alimentam o ContextoTransicao.
 *
 * Nao ha metodo de agendamento: a guarda que o consumiria foi removida ao se
 * verificar que aju_h_agendamento nao existe no banco legado e que os 417
 * pedidos que atingiram Atendido o fizeram sem agendamento algum.
 */
interface PedidoAhRepositoryInterface
{
    /** RN-01: proximo numero sequencial do ano informado. */
    public function proximoNumeroDoAno(int $ano): int;

    /** RN-03: existe pedido em status EdicaoCompdec para o municipio. */
    public function municipioTemPedidoEmEdicao(int $municipioId): bool;

    /** RN-08: quantos itens do tipo informado o pedido possui. */
    public function contarItensPorTipo(int $pedidoId, TipoItemPedido $tipo): int;

    /** RN-11: existe ao menos um parecer favoravel. */
    public function temParecerFavoravel(int $pedidoId): bool;

    public function atualizarStatus(int $pedidoId, StatusPedidoAh $novo): void;

    /** RN-14: grava o log da transicao. */
    public function registrarTramite(
        int $pedidoId,
        StatusPedidoAh $anterior,
        StatusPedidoAh $novo,
        ?string $observacao,
        ?int $usuarioId,
    ): void;
}
