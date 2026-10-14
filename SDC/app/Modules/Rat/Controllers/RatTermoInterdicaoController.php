<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\DTOs\RatTermoInterdicaoDTO;
use App\Modules\Rat\Http\Requests\RatTermoInterdicaoRequest;
use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Services\RatTermoInterdicaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Termo de interdicao da vistoria do RAT (#57). */
class RatTermoInterdicaoController extends Controller
{
    public function __construct(private readonly RatTermoInterdicaoService $service) {}

    public function salvar(RatTermoInterdicaoRequest $request, string $id): JsonResponse
    {
        $ocorrencia = RatOcorrencia::findOrFail($id);
        $termo = $this->service->salvar($ocorrencia, RatTermoInterdicaoDTO::fromValidated($request->validated()), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Termo de interdição salvo.',
            'termo'   => $this->service->paraArray($termo),
        ]);
    }

    public function emitir(Request $request, string $id): JsonResponse
    {
        $ocorrencia = RatOcorrencia::findOrFail($id);
        $termo = $this->service->emitir($ocorrencia, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Termo de interdição emitido.',
            'termo'   => $this->service->paraArray($termo),
        ]);
    }
}
