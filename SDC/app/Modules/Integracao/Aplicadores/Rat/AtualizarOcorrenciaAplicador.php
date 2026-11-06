<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Aplicadores\Rat;

use App\Modules\Integracao\Aplicadores\AplicadorBase;
use App\Modules\Integracao\Support\MensagemDeEntrada;
use App\Modules\Rat\DTOs\RatRelatosDTO;
use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Services\RatWriteService;
use DomainException;
use Illuminate\Http\Request;

/**
 * Espelha PUT v1/rat/ocorrencias/{id} (Api\V1\Rat\OcorrenciaController@update).
 *
 * Idempotencia: itens sem id em recursos/envolvidos sao criados a cada
 * aplicacao (mescla por id, substituirListas: false). Nao ha dedupe de itens
 * aqui: a garantia de aplicar uma vez so vem do exactly-once por mensagem
 * (integracao_aplicacoes) somado a Idempotency-Key obrigatoria na 2a onda.
 */
final class AtualizarOcorrenciaAplicador extends AplicadorBase
{
    public function __construct(private readonly RatWriteService $service)
    {
    }

    /** Mesmas regras do controller sincrono (constante unica no DTO). */
    public function regras(Request $request): array
    {
        return RatRelatosDTO::REGRAS_ATUALIZACAO;
    }

    public function recursoChave(array $payload, array $parametrosRota): string
    {
        return 'rat:' . $this->parametroDeRota($parametrosRota, 'id');
    }

    public function aplicar(MensagemDeEntrada $mensagem): array
    {
        $id = $this->parametroDeRota($mensagem->parametrosRota, 'id');
        $ocorrencia = RatOcorrencia::query()->findOrFail($id);

        $bloqueio = $this->service->motivoBloqueioEdicaoApi($ocorrencia);

        if ($bloqueio !== null) {
            throw new DomainException($bloqueio);
        }

        $dados = array_intersect_key($mensagem->payload, RatRelatosDTO::REGRAS_ATUALIZACAO);

        $this->service->atualizar($id, RatRelatosDTO::fromArray($dados), substituirListas: false);

        return ResultadoDaOcorrencia::de(RatOcorrencia::query()->findOrFail($id));
    }
}
