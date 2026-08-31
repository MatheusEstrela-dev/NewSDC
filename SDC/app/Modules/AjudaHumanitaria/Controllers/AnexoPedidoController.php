<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Requests\StoreAnexoPedidoRequest;
use App\Modules\AjudaHumanitaria\Services\AnexoPedidoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Anexos do pedido (RN-22).
 *
 * O download passa pelo controller em vez de expor a URL do disco: o disco
 * do modulo nao e publico, e assim o arquivo herda o escopo por municipio da
 * policy em vez de ficar acessivel por link direto.
 */
class AnexoPedidoController extends Controller
{
    public function __construct(
        private readonly AnexoPedidoService $anexos,
    ) {}

    public function store(StoreAnexoPedidoRequest $request, int $pedidoId): RedirectResponse
    {
        $pedido = PedidoAh::findOrFail($pedidoId);

        $this->authorize('anexos', $pedido);

        [$media, $erro] = $this->anexos->anexar($pedidoId, $request->file('arquivo'));

        if ($erro !== null) {
            return back()->with('error', $erro);
        }

        return back()->with('success', "Documento \"{$media->name}\" anexado.");
    }

    public function destroy(Request $request, int $pedidoId, int $mediaId): RedirectResponse
    {
        $pedido = PedidoAh::findOrFail($pedidoId);

        $this->authorize('anexos', $pedido);

        $this->mediaDoPedido($pedido, $mediaId);

        $this->anexos->remover($mediaId);

        return back()->with('success', 'Documento removido.');
    }

    public function download(Request $request, int $pedidoId, int $mediaId): StreamedResponse
    {
        $pedido = PedidoAh::findOrFail($pedidoId);

        $this->authorize('view', $pedido);

        $media = $this->mediaDoPedido($pedido, $mediaId);

        return response()->streamDownload(
            static function () use ($media): void {
                echo $media->toResponse(request())->getContent();
            },
            $media->name,
            ['Content-Type' => $media->mime_type],
        );
    }

    /**
     * Garante que a midia pertence ao pedido informado.
     *
     * Sem isso, um id de midia de outro pedido passaria pela policy do pedido
     * atual e vazaria documento entre municipios.
     */
    private function mediaDoPedido(PedidoAh $pedido, int $mediaId): Media
    {
        return Media::query()
            ->where('model_type', $pedido->getMorphClass())
            ->where('model_id', $pedido->id)
            ->where('collection_name', PedidoAh::MEDIA_ANEXOS)
            ->findOrFail($mediaId);
    }
}
