<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Aplicadores\Rat;

use App\Modules\Integracao\Aplicadores\AplicadorBase;
use App\Modules\Integracao\Support\MensagemDeEntrada;
use App\Modules\Rat\DTOs\RatRelatosDTO;
use App\Services\Rat\RatNovoService;
use Illuminate\Http\Request;

/**
 * Espelha POST v1/rat-novo (Api\RatNovoController@store).
 *
 * numero_bos enviado e ignorado (o servidor gera o protocolo) e status=1
 * finaliza na criacao, como no endpoint sincrono: as duas regras vivem no
 * RatRelatosDTO (REGRAS_CRIACAO_RAT_NOVO e fromRatNovo).
 *
 * Chave: criacao nao tem alvo, entao a chave e `rat:criacao:<hash>` do payload
 * validado. Serve so para ser estavel por mensagem; a serializacao real da
 * sequencia do protocolo e o advisory lock do RatProtocoloService.
 *
 * prazo_edicao (48h) conta a partir da APLICACAO: o atraso de fila e minimo e
 * aceito. A idempotencia vem do exactly-once por mensagem somado a
 * Idempotency-Key obrigatoria (exigida pelo nucleo).
 */
final class CriarRatNovoAplicador extends AplicadorBase
{
    use GeraChaveDeCriacao;

    public function __construct(private readonly RatNovoService $service)
    {
    }

    public function regras(Request $request): array
    {
        return RatRelatosDTO::REGRAS_CRIACAO_RAT_NOVO;
    }

    public function recursoChave(array $payload, array $parametrosRota): string
    {
        return $this->chaveDeCriacao($payload);
    }

    public function aplicar(MensagemDeEntrada $mensagem): array
    {
        return ResultadoDaOcorrencia::de(
            $this->service->createRat(RatRelatosDTO::fromRatNovo($mensagem->payload))
        );
    }
}
