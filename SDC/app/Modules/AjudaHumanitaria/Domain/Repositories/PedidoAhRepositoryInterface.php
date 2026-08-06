<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Repositories;

use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;

/**
 * Persistencia do pedido e dos fatos que alimentam o ContextoTransicao.
 *
 * Implementacao Eloquent na fase 2, sob Infrastructure/Persistence.
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

    /** RN-21: existe agendamento de retirada aprovado. */
    public function temAgendamentoAprovado(int $pedidoId): bool;

    public function atualizarStatus(int $pedidoId, StatusPedidoAh $novo): void;

    /** RN-14: grava o log da transicao. */
    public function registrarTramite(
        int $pedidoId,
        StatusPedidoAh $anterior,
        StatusPedidoAh $novo,
        ?string $observacao,
        int $usuarioId,
    ): void;
}
