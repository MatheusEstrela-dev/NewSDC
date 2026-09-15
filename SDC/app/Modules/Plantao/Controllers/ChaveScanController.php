<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\DTOs\ReservaListDTO;
use App\Modules\Plantao\Exceptions\ReservaInvalidaException;
use App\Modules\Plantao\Requests\ScanChaveRequest;
use App\Modules\Plantao\Services\ChaveQrService;
use Illuminate\Http\JsonResponse;

/**
 * Leitura da etiqueta do chaveiro. Responde em JSON porque quem chama e a
 * camera, nao um formulario: a tela precisa reagir ao scan sem recarregar.
 *
 * NAO ESCREVE NADA. Devolve qual e o proximo ato daquela chave e os dados para
 * o formulario; a gravacao acontece no submit do check-in ou do check-out.
 */
class ChaveScanController extends Controller
{
    public function __construct(
        private readonly ChaveQrService $chaveService
    ) {
    }

    public function __invoke(ScanChaveRequest $request): JsonResponse
    {
        try {
            $resultado = $this->chaveService->resolver(
                $request->validated('qr_token'),
                $request->user()
            );
        } catch (ReservaInvalidaException $e) {
            // 422 e nao 403: nao e falta de permissao, e o estado da chave que
            // nao permite o ato agora. A mensagem e o proprio texto da tela.
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $viatura = $resultado['viatura'];
        $movimentacao = $resultado['movimentacao'];

        return response()->json([
            'acao' => $resultado['acao'],
            'viatura' => [
                'id' => $viatura->id,
                'prefixo' => $viatura->prefixo,
                'placa' => $viatura->placa,
                'modelo' => $viatura->modelo,
                'hodometro_atual' => $viatura->hodometro_atual,
                'combustivel_valor' => $viatura->nivel_combustivel?->value,
            ],
            'reserva' => $resultado['reserva'] === null
                ? null
                : ReservaListDTO::fromModel($resultado['reserva']->loadMissing('viatura')),
            'movimentacao' => $movimentacao === null ? null : [
                'id' => $movimentacao->id,
                'saida_em' => $movimentacao->saida_em?->toIso8601String(),
                'saida_hodometro' => $movimentacao->saida_hodometro,
                'destino' => $movimentacao->destino,
            ],
        ]);
    }
}
