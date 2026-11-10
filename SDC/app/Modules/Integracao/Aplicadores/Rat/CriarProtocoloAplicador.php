<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Aplicadores\Rat;

use App\Modules\Integracao\Aplicadores\AplicadorBase;
use App\Modules\Integracao\Support\MensagemDeEntrada;
use App\Modules\Rat\DTOs\RatRelatosDTO;
use App\Modules\Rat\Http\Requests\ReceiveRatBIRequest;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Http\Request;

/**
 * Espelha POST v1/rat/protocolos (Api\V1\Rat\ProtocoloController@receive).
 *
 * Chama RatWriteService::createWithData, o mesmo caminho do controller: o
 * numero do BOS (advisory lock do RatProtocoloService), a conferencia de
 * finalize com interdicao (422, vira rejeicao com a mesma mensagem) e o
 * outbox (actor_user_id do usuario do token) vivem la.
 *
 * Chave: criacao nao tem alvo, entao a chave e `rat:criacao:<hash>` do payload
 * validado. Serve so para ser estavel por mensagem; a serializacao real da
 * sequencia do protocolo e o advisory lock do RatProtocoloService.
 *
 * prazo_edicao (48h) conta a partir da APLICACAO: o atraso de fila e minimo e
 * aceito. A idempotencia vem do exactly-once por mensagem somado a
 * Idempotency-Key obrigatoria (exigida pelo nucleo).
 */
final class CriarProtocoloAplicador extends AplicadorBase
{
    use GeraChaveDeCriacao;

    public function __construct(private readonly RatWriteService $service)
    {
    }

    /** Mesmas regras do FormRequest do controller sincrono. */
    public function regras(Request $request): array
    {
        return (new ReceiveRatBIRequest())->rules();
    }

    public function recursoChave(array $payload, array $parametrosRota): string
    {
        return $this->chaveDeCriacao($payload);
    }

    public function aplicar(MensagemDeEntrada $mensagem): array
    {
        return ResultadoDaOcorrencia::de(
            $this->service->createWithData(RatRelatosDTO::fromProtocolo($mensagem->payload))
        );
    }
}
