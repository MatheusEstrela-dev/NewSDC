<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/** Mensagem a gravar na caixa de entrada, montada a partir da requisicao aceita. */
final class NovaEntrada
{
    /** Nunca gravados, mesmo se configurados por engano. */
    private const CABECALHOS_PROIBIDOS = ['authorization', 'cookie', 'proxy-authorization', 'set-cookie'];

    /**
     * @param  array<string, mixed>  $payload  dados validados (vao cifrados)
     * @param  array<string, mixed>  $parametrosRota  parametros crus da rota
     * @param  array<string, string>  $cabecalhos
     */
    public function __construct(
        public readonly string $fonte,
        public readonly string $operacao,
        public readonly array $payload,
        public readonly string $hashPayload,
        public readonly array $parametrosRota,
        public readonly string $metodo,
        public readonly string $caminho,
        public readonly string $recursoChave,
        public readonly ?string $recursoId,
        public readonly ?int $tokenId,
        public readonly ?int $userId,
        public readonly ?string $ip,
        public readonly ?string $chaveIdempotente,
        public readonly array $cabecalhos,
        public readonly string $traceId,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validados
     * @param  array<string, mixed>  $parametrosRota
     */
    public static function daRequisicao(
        Request $request,
        string $fonte,
        string $operacao,
        array $validados,
        array $parametrosRota,
        string $recursoChave,
    ): self {
        $token = $request->user()?->currentAccessToken();
        $chave = trim((string) $request->header('Idempotency-Key', ''));
        $ultimo = $parametrosRota === [] ? null : end($parametrosRota);

        return new self(
            fonte: $fonte,
            operacao: $operacao,
            payload: $validados,
            // O hash cobre o corpo enviado (nao so o validado) e o alvo: a
            // mesma chave reaproveitada em outro recurso tambem conflita.
            hashPayload: JsonCanonico::sha256([
                'operacao' => $operacao,
                'rota'     => $parametrosRota,
                'corpo'    => $request->all(),
            ]),
            parametrosRota: $parametrosRota,
            metodo: strtoupper($request->getMethod()),
            caminho: $request->path(),
            recursoChave: $recursoChave,
            recursoId: is_scalar($ultimo) ? (string) $ultimo : null,
            tokenId: $token instanceof PersonalAccessToken ? (int) $token->getKey() : null,
            userId: $request->user() !== null ? (int) $request->user()->getAuthIdentifier() : null,
            ip: $request->ip(),
            chaveIdempotente: $chave !== '' ? $chave : null,
            cabecalhos: self::cabecalhosPermitidos($request),
            traceId: (string) Str::uuid(),
        );
    }

    /** @return array<string, string> */
    private static function cabecalhosPermitidos(Request $request): array
    {
        $saida = [];
        foreach ((array) config('integracao.cabecalhos_permitidos', []) as $nome) {
            $nome = (string) $nome;
            if (in_array(strtolower($nome), self::CABECALHOS_PROIBIDOS, true) || ! $request->headers->has($nome)) {
                continue;
            }
            $saida[$nome] = mb_substr((string) $request->header($nome), 0, 500);
        }

        return $saida;
    }
}
