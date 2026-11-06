<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Aplicadores\Rat;

use App\Modules\Integracao\Aplicadores\AplicadorBase;
use App\Modules\Integracao\Exceptions\EntradaJaAplicadaException;
use App\Modules\Integracao\Support\MensagemDeEntrada;
use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Http\Request;

/** Espelha PATCH v1/rat/ocorrencias/{id}/finalizar (Api\V1\Rat\OcorrenciaController@finalize). */
final class FinalizarOcorrenciaAplicador extends AplicadorBase
{
    public function __construct(private readonly RatWriteService $service)
    {
    }

    /** Endpoint sem corpo. */
    public function regras(Request $request): array
    {
        return [];
    }

    public function recursoChave(array $payload, array $parametrosRota): string
    {
        return 'rat:' . $this->parametroDeRota($parametrosRota, 'id');
    }

    public function aplicar(MensagemDeEntrada $mensagem): array
    {
        $id = $this->parametroDeRota($mensagem->parametrosRota, 'id');
        $ocorrencia = RatOcorrencia::query()->findOrFail($id);

        if ((int) $ocorrencia->status === 1) {
            throw new EntradaJaAplicadaException('RAT ja esta finalizado.', ResultadoDaOcorrencia::de($ocorrencia));
        }

        return ResultadoDaOcorrencia::de($this->service->finalize($id));
    }
}
