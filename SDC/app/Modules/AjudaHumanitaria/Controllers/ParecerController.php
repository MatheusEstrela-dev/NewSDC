<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\DTOs\ParecerDTO;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAhParecer;
use App\Modules\AjudaHumanitaria\Requests\StoreParecerRequest;
use App\Modules\AjudaHumanitaria\Services\ParecerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Parecer tecnico do pedido (RN-10).
 *
 * O parecer favoravel e o que habilita o avanco da analise DLOG para o
 * Diretor (RN-11), mas quem consulta esse fato na transicao e o
 * TramitacaoService, nao este controller.
 */
class ParecerController extends Controller
{
    public function __construct(
        private readonly ParecerService $pareceres,
    ) {}

    public function store(StoreParecerRequest $request, int $pedidoId): RedirectResponse
    {
        $pedido = PedidoAh::findOrFail($pedidoId);

        $this->authorize('parecer', $pedido);

        [$parecer, $erro] = $this->pareceres->emitir(
            $pedidoId,
            ParecerDTO::fromRequest($request->validated()),
            $request->user()?->id,
        );

        if ($erro !== null) {
            return back()->with('error', $erro);
        }

        return back()->with(
            'success',
            'Parecer ' . ($parecer->situacao->ehFavoravel() ? 'favorável' : 'contrário') . ' registrado.',
        );
    }

    public function destroy(Request $request, int $pedidoId, int $parecerId): RedirectResponse
    {
        $pedido = PedidoAh::findOrFail($pedidoId);

        $this->authorize('parecer', $pedido);

        PedidoAhParecer::where('pedido_ah_id', $pedidoId)->findOrFail($parecerId);

        $this->pareceres->remover($parecerId);

        return back()->with('success', 'Parecer removido.');
    }
}
