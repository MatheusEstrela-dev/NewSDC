<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\DTOs\TransicaoPedidoDTO;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Requests\TramitarPedidoRequest;
use App\Modules\AjudaHumanitaria\Services\TramitacaoService;
use Illuminate\Http\RedirectResponse;

/**
 * Tramitacao do pedido.
 *
 * Recusa de transicao nao e erro de sistema: e a regra funcionando. Por isso
 * volta como flash de aviso, com o motivo que a guarda produziu, e nao como
 * excecao.
 */
class TramitacaoController extends Controller
{
    public function __construct(
        private readonly TramitacaoService $tramitacao,
    ) {}

    public function store(TramitarPedidoRequest $request, int $pedidoId): RedirectResponse
    {
        $pedido = PedidoAh::findOrFail($pedidoId);

        $this->authorize('tramitar', $pedido);

        [$atualizado, $erro] = $this->tramitacao->tramitar(
            $pedidoId,
            TransicaoPedidoDTO::fromRequest($request->validated()),
            $request->user()?->id,
        );

        if ($erro !== null) {
            return back()->with('error', $erro);
        }

        return back()->with(
            'success',
            "Pedido {$atualizado->identificador} agora está em \"{$atualizado->status->label()}\".",
        );
    }
}
