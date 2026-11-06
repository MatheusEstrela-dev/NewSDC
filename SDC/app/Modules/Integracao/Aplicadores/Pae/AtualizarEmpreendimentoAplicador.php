<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Aplicadores\Pae;

use App\Modules\Integracao\Aplicadores\AplicadorBase;
use App\Modules\Integracao\Support\MensagemDeEntrada;
use App\Modules\Pae\Requests\UpdateEmpreendimentoRequest;
use App\Modules\Pae\Services\EmpreendimentoApiService;
use Illuminate\Http\Request;

/** Espelha PUT|PATCH v1/pae/empreendimentos/{id} (EmpreendimentoController@update). */
final class AtualizarEmpreendimentoAplicador extends AplicadorBase
{
    public function __construct(private readonly EmpreendimentoApiService $service)
    {
    }

    public function regras(Request $request): array
    {
        return (new UpdateEmpreendimentoRequest())->rules();
    }

    public function recursoChave(array $payload, array $parametrosRota): string
    {
        return 'pae:empreendimento:' . $this->parametroDeRota($parametrosRota, 'id');
    }

    public function aplicar(MensagemDeEntrada $mensagem): array
    {
        $empreendimento = $this->service->findById((int) $this->parametroDeRota($mensagem->parametrosRota, 'id'));
        $empreendimento = $this->service->update($empreendimento, $mensagem->payload);

        return ['id' => (int) $empreendimento->getKey()];
    }
}
