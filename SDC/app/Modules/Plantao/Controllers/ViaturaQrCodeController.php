<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Services\ChaveQrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Etiqueta do chaveiro. Responde nos dois formatos pelo Accept, seguindo
 * Cisterna\Controllers\QrCodeController:
 *
 *   JSON  -> SVG inline para o modal (escala sem borrar, uma requisicao a menos)
 *   PNG   -> o mesmo endereco com ?formato=png, usado pelo "Baixar PNG"
 *
 * O token e emitido na primeira chamada e reaproveitado dali em diante:
 * reimprimir a etiqueta nao pode invalidar as que ja estao coladas. Trocar o
 * token e ato explicito, por ?rotacionar=1 -- o caminho do chaveiro extraviado,
 * em que o adesivo antigo precisa parar de funcionar.
 */
class ViaturaQrCodeController extends Controller
{
    public function __construct(
        private readonly ChaveQrService $chaveService
    ) {
    }

    public function __invoke(Request $request, Viatura $viatura): Response|JsonResponse
    {
        // SOMENTE LEITURA. A troca de etiqueta saiu daqui para o
        // ViaturaQrCodeRotacionarController, em POST: rotacionar por
        // `?rotacionar=1` num GET fazia um metodo seguro mudar estado, e
        // bastava um prefetch do navegador, um recarregamento ou o botao voltar
        // para queimar a etiqueta de novo sem ninguem pedir.
        if ($request->query('formato') === 'png') {
            return response($this->chaveService->png($viatura), 200, [
                'Content-Type' => 'image/png',
                // `inline`: abrir a URL da etiqueta no navegador MOSTRA o QR, em
                // vez de baixar um arquivo. E o caminho mais curto para por o
                // codigo numa tela e apontar o celular. O botao "Baixar PNG" do
                // modal forca o download pelo atributo `download` do link.
                'Content-Disposition' => 'inline; filename="chave-'.$viatura->placa.'.png"',
                // Sem cache: o token pode ter sido rotacionado, e uma etiqueta
                // servida do cache seria impressa ja invalida.
                'Cache-Control' => 'no-store, private',
            ]);
        }

        return response()->json([
            'svg' => $this->chaveService->svg($viatura),
            'prefixo' => $viatura->prefixo,
            'placa' => $viatura->placa,
            'modelo' => $viatura->modelo,
            'download' => route('plantao.viaturas.qrcode', [$viatura->id, 'formato' => 'png']),
        ])->header('Cache-Control', 'no-store, private');
    }
}
