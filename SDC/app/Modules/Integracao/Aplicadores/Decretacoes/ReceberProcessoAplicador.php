<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Aplicadores\Decretacoes;

use App\Modules\Decretacoes\DTO\ProcessoRequestDTO;
use App\Modules\Decretacoes\Requests\ReceiveProcessoRequest;
use App\Modules\Decretacoes\Services\EntradaProcessoService;
use App\Modules\Integracao\Aplicadores\AplicadorBase;
use App\Modules\Integracao\Support\MensagemDeEntrada;
use Illuminate\Http\Request;

/**
 * Espelha POST v1/decretacoes/receive (DecretacoesApiController@receive).
 *
 * Mesmo caminho do controller: ProcessoRequestDTO::fromValidated +
 * EntradaProcessoService::createProcesso. O model do processo exige usuario
 * autenticado (o job faz Auth::setUser antes de aplicar()).
 *
 * Sem dedupe por chave natural: nada impoe unicidade de protocolo/SEI hoje.
 * A idempotencia vem do exactly-once por mensagem e da Idempotency-Key
 * obrigatoria no nucleo da caixa de entrada.
 */
final class ReceberProcessoAplicador extends AplicadorBase
{
    public function __construct(private readonly EntradaProcessoService $service)
    {
    }

    public function regras(Request $request): array
    {
        return (new ReceiveProcessoRequest())->rules();
    }

    public function recursoChave(array $payload, array $parametrosRota): string
    {
        $base = 'decretacoes:processo:' . (string) ($payload['municipio_id'] ?? '');

        foreach (['n_protocolo_fide', 'n_processo_sei'] as $campo) {
            $valor = $payload[$campo] ?? null;

            if (is_scalar($valor) && trim((string) $valor) !== '') {
                return $base . ':' . trim((string) $valor);
            }
        }

        return $base . ':' . $this->hashCurto($payload);
    }

    public function aplicar(MensagemDeEntrada $mensagem): array
    {
        // informacoes_decreto segue como string JSON, sem decodificar.
        $dto = ProcessoRequestDTO::fromValidated($mensagem->payload);
        $processo = $this->service->createProcesso($dto);

        // Resultado minimo: sem analista (texto livre, dado pessoal).
        return [
            'id' => (int) $processo->getKey(),
            'processo' => strtolower((string) $processo->processo),
            'status' => $processo->status instanceof \BackedEnum ? $processo->status->value : $processo->status,
            'municipio_id' => isset($mensagem->payload['municipio_id']) ? (int) $mensagem->payload['municipio_id'] : null,
        ];
    }

    /** @param array<string, mixed> $payload */
    private function hashCurto(array $payload): string
    {
        return substr(hash('sha256', (string) json_encode($this->canonico($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)), 0, 16);
    }

    /** Ordena chaves recursivamente para o json ser estavel. */
    private function canonico(mixed $valor): mixed
    {
        if (! is_array($valor)) {
            return $valor;
        }

        $valor = array_map(fn (mixed $v) => $this->canonico($v), $valor);

        if (! array_is_list($valor)) {
            ksort($valor);
        }

        return $valor;
    }
}
