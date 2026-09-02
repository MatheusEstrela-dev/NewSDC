<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\ReservaInvalidaException;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Services\ChaveQrService;
use Illuminate\Http\JsonResponse;

/**
 * Emite uma etiqueta NOVA para o chaveiro, matando a anterior.
 *
 * POST e nao GET: o ato muda estado e nao pode ser repetido por prefetch,
 * recarregamento ou botao voltar -- cada disparo queima a etiqueta vigente e
 * obriga a reimprimir o adesivo.
 *
 * A guarda de "viatura na rua" mora no ChaveQrService, nao aqui: vale para
 * qualquer caminho que rotacione, inclusive console.
 */
class ViaturaQrCodeRotacionarController extends Controller
{
    public function __construct(
        private readonly ChaveQrService $chaveService
    ) {
    }

    public function __invoke(Viatura $viatura): JsonResponse
    {
        try {
            $this->chaveService->rotacionarToken($viatura);
        } catch (ReservaInvalidaException $e) {
            // 422 e nao 403: nao falta permissao, o estado da viatura e que nao
            // permite o ato agora. A mensagem e o proprio texto da tela.
            return response()->json(['message' => $e->getMessage()], 422);
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
