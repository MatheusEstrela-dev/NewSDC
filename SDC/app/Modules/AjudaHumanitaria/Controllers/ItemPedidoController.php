<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\DTOs\ItemPedidoDTO;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAhItem;
use App\Modules\AjudaHumanitaria\Requests\StoreItemPedidoRequest;
use App\Modules\AjudaHumanitaria\Services\ItemPedidoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Materiais do pedido.
 *
 * O controller nao decide quando cada tipo de item pode entrar: isso e da
 * RN-09, aplicada pelo ItemPedidoService. Aqui so ha autorizacao de escopo e
 * traducao do resultado em resposta.
 */
class ItemPedidoController extends Controller
{
    public function __construct(
        private readonly ItemPedidoService $itens,
    ) {}

    public function store(StoreItemPedidoRequest $request, int $pedidoId): RedirectResponse
    {
        $pedido = PedidoAh::findOrFail($pedidoId);

        $this->authorize($request->ehItemLiberado() ? 'liberarItens' : 'update', $pedido);

        [$item, $erro] = $this->itens->adicionar(
            $pedidoId,
            ItemPedidoDTO::fromRequest($request->validated()),
        );

        if ($erro !== null) {
            return back()->with('error', $erro);
        }

        return back()->with('success', "Material \"{$item->descricao_item}\" incluído.");
    }

    public function destroy(Request $request, int $pedidoId, int $itemId): RedirectResponse
    {
        $pedido = PedidoAh::findOrFail($pedidoId);
        $item   = PedidoAhItem::where('pedido_ah_id', $pedidoId)->findOrFail($itemId);

        $this->authorize($item->tipo->value === 'L' ? 'liberarItens' : 'update', $pedido);

        [$removido, $erro] = $this->itens->remover($itemId);

        if (! $removido) {
            return back()->with('error', $erro ?? 'Não foi possível remover o material.');
        }

        return back()->with('success', 'Material removido.');
    }
}
