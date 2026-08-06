<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Repositories;

use Carbon\CarbonImmutable;

/**
 * Persistencia da prestacao de contas.
 *
 * Implementacao Eloquent na fase 2, sob Infrastructure/Persistence.
 */
interface PrestacaoContaRepositoryInterface
{
    /** RN-16: cria o cabecalho da prestacao e devolve o id. */
    public function abrirParaPedido(int $pedidoId, CarbonImmutable $dataLimite): int;

    /**
     * RN-15: copia os itens tipo Liberado do pedido para a prestacao.
     * Devolve quantos itens foram copiados.
     */
    public function copiarItensLiberados(int $pedidoId, int $prestacaoContaId): int;

    /** RN-18: quantidade de material do item da prestacao. */
    public function quantidadeDoItem(int $prestacaoContaItemId): int;

    /** RN-18: soma das quantidades ja entregues a beneficiarios do item. */
    public function quantidadeJaEntregue(int $prestacaoContaItemId): int;

    /** RN-19: marca a prestacao como homologada. */
    public function homologar(int $prestacaoContaId, ?int $usuarioId): void;
}
