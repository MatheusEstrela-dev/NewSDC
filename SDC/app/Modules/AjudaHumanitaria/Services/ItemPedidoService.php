<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Domain\Repositories\MaterialAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\DTOs\ItemPedidoDTO;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAhItem;
use Illuminate\Database\Eloquent\Collection;

/**
 * Itens do pedido (RN-07, RN-08, RN-09).
 *
 * O discriminador de tipo substitui a terceira tabela que o legado mantinha
 * para preservar o pedido original. Para que as duas representacoes nunca se
 * sobreponham, este servico as separa no tempo:
 *
 * - o item Solicitado so pode ser criado ou removido enquanto o pedido esta em
 *   edicao com o municipio
 * - o item Liberado so pode ser criado depois que o pedido saiu da edicao
 *
 * Assim o que o municipio pediu fica congelado no instante do envio, e o corte
 * de quantidade do CEDEC nunca o sobrescreve.
 */
final class ItemPedidoService
{
    public function __construct(
        private readonly MaterialAhRepositoryInterface $materiais,
    ) {}

    /**
     * RN-07.
     *
     * @return array<int, array{id: int, nome: string, unidade_medida: string}>
     */
    public function materiaisDisponiveis(): array
    {
        return $this->materiais->disponiveisParaPedido();
    }

    /**
     * @return array{0: ?PedidoAhItem, 1: ?string}
     */
    public function adicionar(int $pedidoId, ItemPedidoDTO $dto): array
    {
        if ($dto->qtd <= 0) {
            return [null, 'A quantidade deve ser maior que zero.'];
        }

        $pedido = PedidoAh::findOrFail($pedidoId);
        $emEdicao = $pedido->status === StatusPedidoAh::EdicaoCompdec;

        if ($dto->tipo === TipoItemPedido::Pedido && ! $emEdicao) {
            return [null, 'O pedido já foi enviado para análise; os materiais solicitados não podem mais ser alterados.'];
        }

        if ($dto->tipo === TipoItemPedido::Liberado && $emEdicao) {
            return [null, 'As quantidades liberadas só podem ser definidas depois que o pedido entrar em análise.'];
        }

        $item = PedidoAhItem::create($dto->toArray() + ['pedido_ah_id' => $pedidoId]);

        return [$item, null];
    }

    /**
     * @return array{0: bool, 1: ?string}
     */
    public function remover(int $itemId): array
    {
        $item = PedidoAhItem::with('pedido')->findOrFail($itemId);
        $emEdicao = $item->pedido->status === StatusPedidoAh::EdicaoCompdec;

        if ($item->tipo === TipoItemPedido::Pedido && ! $emEdicao) {
            return [false, 'O pedido já foi enviado para análise; os materiais solicitados não podem mais ser alterados.'];
        }

        return [(bool) $item->delete(), null];
    }

    /**
     * @return Collection<int, PedidoAhItem>
     */
    public function itensDoPedido(int $pedidoId, ?TipoItemPedido $tipo = null): Collection
    {
        return PedidoAhItem::query()
            ->where('pedido_ah_id', $pedidoId)
            ->when($tipo !== null, fn ($q) => $q->where('tipo', $tipo->value))
            ->orderBy('descricao_item')
            ->get();
    }
}
