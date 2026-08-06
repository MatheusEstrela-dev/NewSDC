<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Infrastructure\Persistence;

use App\Modules\AjudaHumanitaria\Domain\Repositories\PrestacaoContaRepositoryInterface;
use App\Modules\AjudaHumanitaria\Enums\StatusPrestacaoConta;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use App\Modules\AjudaHumanitaria\Models\PedidoAhItem;
use App\Modules\AjudaHumanitaria\Models\PrestacaoConta;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaEntrega;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaItem;
use Carbon\CarbonImmutable;

final class EloquentPrestacaoContaRepository implements PrestacaoContaRepositoryInterface
{
    /** RN-16. */
    public function abrirParaPedido(int $pedidoId, CarbonImmutable $dataLimite): int
    {
        $prestacao = PrestacaoConta::create([
            'pedido_ah_id' => $pedidoId,
            'status'       => StatusPrestacaoConta::Pendente,
            'data_limite'  => $dataLimite->toDateString(),
        ]);

        return (int) $prestacao->id;
    }

    /**
     * RN-15: copia para a prestacao o que foi efetivamente liberado.
     *
     * Equivale a iniciaPrestContas do legado, que percorria os itens tipo L e
     * inseria um registro de prestacao para cada. No Laravel isso nunca chegou a
     * existir: AjudaPrestConta::lancarPrestContaItens era chamada e nunca
     * definida, o que quebrava a transicao para Atendido.
     */
    public function copiarItensLiberados(int $pedidoId, int $prestacaoContaId): int
    {
        $itens = PedidoAhItem::query()
            ->where('pedido_ah_id', $pedidoId)
            ->where('tipo', TipoItemPedido::Liberado->value)
            ->get();

        foreach ($itens as $item) {
            PrestacaoContaItem::create([
                'prestacao_conta_id'     => $prestacaoContaId,
                'material_ah_id'         => $item->material_ah_id,
                'codigo_material'        => $item->codigo,
                'nome_material'          => $item->descricao_item,
                'qtd'                    => $item->qtd,
                'total_familia_atendida' => $item->qtd_familia_atendida,
            ]);
        }

        return $itens->count();
    }

    /** RN-18. */
    public function quantidadeDoItem(int $prestacaoContaItemId): int
    {
        return (int) PrestacaoContaItem::query()
            ->whereKey($prestacaoContaItemId)
            ->value('qtd');
    }

    /** RN-18. */
    public function quantidadeJaEntregue(int $prestacaoContaItemId): int
    {
        return (int) PrestacaoContaEntrega::query()
            ->where('prestacao_conta_item_id', $prestacaoContaItemId)
            ->sum('qtd');
    }

    /** RN-19. */
    public function homologar(int $prestacaoContaId, int $usuarioId): void
    {
        PrestacaoConta::query()
            ->whereKey($prestacaoContaId)
            ->update([
                'status'         => StatusPrestacaoConta::Homologada->value,
                'homologado_por' => $usuarioId,
                'homologado_em'  => now(),
            ]);
    }
}
