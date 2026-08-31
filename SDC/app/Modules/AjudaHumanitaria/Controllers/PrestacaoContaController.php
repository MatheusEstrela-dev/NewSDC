<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\DTOs\EntregaBeneficiarioDTO;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Models\PrestacaoConta;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaEntrega;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaItem;
use App\Modules\AjudaHumanitaria\Requests\StoreEntregaRequest;
use App\Modules\AjudaHumanitaria\Services\PrestacaoContasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Prestacao de contas do pedido (RN-17, RN-18, RN-19).
 *
 * A prestacao nao e criada aqui: nasce como efeito da entrada do pedido em
 * Atendido, disparada pelo TramitacaoService (RN-15).
 */
class PrestacaoContaController extends Controller
{
    public function __construct(
        private readonly PrestacaoContasService $prestacoes,
    ) {}

    /** RN-17 e RN-18. */
    public function storeEntrega(StoreEntregaRequest $request, int $pedidoId): RedirectResponse
    {
        $pedido = PedidoAh::findOrFail($pedidoId);

        $this->authorize('lancarEntrega', $pedido);

        $this->itemDoPedido($pedido, (int) $request->validated('prestacao_conta_item_id'));

        [$entrega, $erro] = $this->prestacoes->lancarEntrega(
            EntregaBeneficiarioDTO::fromRequest($request->validated()),
        );

        if ($erro !== null) {
            return back()->with('error', $erro);
        }

        return back()->with('success', "Entrega para {$entrega->nome_beneficiario} registrada.");
    }

    public function destroyEntrega(Request $request, int $pedidoId, int $entregaId): RedirectResponse
    {
        $pedido = PedidoAh::findOrFail($pedidoId);

        $this->authorize('lancarEntrega', $pedido);

        $entrega = PrestacaoContaEntrega::with('item')->findOrFail($entregaId);

        $this->itemDoPedido($pedido, $entrega->prestacao_conta_item_id);

        $this->prestacoes->removerEntrega($entregaId);

        return back()->with('success', 'Entrega removida.');
    }

    /** RN-19: homologar a prestacao e finalizar o processo sao o mesmo ato. */
    public function homologar(Request $request, int $pedidoId): RedirectResponse
    {
        $pedido = PedidoAh::findOrFail($pedidoId);

        $this->authorize('homologar', $pedido);

        $prestacao = PrestacaoConta::where('pedido_ah_id', $pedidoId)->firstOrFail();

        [$ok, $erro] = $this->prestacoes->homologar($prestacao->id, $request->user()?->id);

        if (! $ok) {
            return back()->with('error', $erro);
        }

        return back()->with('success', 'Prestação de contas homologada e processo finalizado.');
    }

    /**
     * Garante que o item da prestacao pertence ao pedido informado.
     *
     * Sem isso, um id de item de outro pedido passaria pela policy do pedido
     * atual e permitiria lancar entrega no processo de outro municipio.
     */
    private function itemDoPedido(PedidoAh $pedido, int $itemId): PrestacaoContaItem
    {
        return PrestacaoContaItem::query()
            ->whereHas('prestacaoConta', fn ($q) => $q->where('pedido_ah_id', $pedido->id))
            ->findOrFail($itemId);
    }
}
