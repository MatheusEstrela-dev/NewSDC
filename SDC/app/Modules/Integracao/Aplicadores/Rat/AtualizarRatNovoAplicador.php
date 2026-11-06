<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Aplicadores\Rat;

use App\Modules\Integracao\Aplicadores\AplicadorBase;
use App\Modules\Integracao\Support\MensagemDeEntrada;
use App\Services\Rat\RatNovoService;
use Illuminate\Http\Request;

/** Espelha PUT v1/rat-novo/{id} (Api\RatNovoController@update). */
final class AtualizarRatNovoAplicador extends AplicadorBase
{
    public function __construct(private readonly RatNovoService $service)
    {
    }

    /**
     * Copia do validate inline de Api\RatNovoController@update: la nao ha
     * FormRequest nem metodo reaproveitavel. Manter as duas em sincronia ate
     * a regra sair do controller.
     */
    public function regras(Request $request): array
    {
        return [
            'numero_bos' => 'nullable|string|max:50',
            'status'     => 'integer|in:0,1',
            'historico'  => 'nullable|string',
        ];
    }

    public function recursoChave(array $payload, array $parametrosRota): string
    {
        return 'rat:' . $this->parametroDeRota($parametrosRota, 'id');
    }

    public function aplicar(MensagemDeEntrada $mensagem): array
    {
        return ResultadoDaOcorrencia::de($this->service->updateRat(
            $this->parametroDeRota($mensagem->parametrosRota, 'id'),
            $mensagem->payload,
        ));
    }
}
