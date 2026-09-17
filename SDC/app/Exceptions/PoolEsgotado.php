<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Um pool de conexoes por-coroutine ficou sem recurso dentro do prazo.
 *
 * Isto NAO e erro de aplicacao, e teto de capacidade: sob hooks de corrotina, o
 * numero de requisicoes em voo por worker deixa de ser 1 e passa a ser limitado
 * pelo menor dos pools (Postgres e Redis). Quando o pool esgota, a resposta
 * correta e a mesma do resto do backpressure -- 503 com Retry-After, dizendo ao
 * cliente para voltar -- e nao 500, que significa "o servidor tem um defeito" e
 * contamina a taxa de erro usada para decidir se um deploy volta atras.
 *
 * A distincao importa no ensaio de capacidade: recusa intencional e atendimento
 * degradado com sucesso, enquanto 500 e falha inesperada. Contar uma como a
 * outra esconde saturacao ou inventa defeito onde nao ha.
 */
class PoolEsgotado extends RuntimeException
{
    public function __construct(public readonly string $pool, string $mensagem)
    {
        parent::__construct($mensagem);
    }

    public function render(Request $request): Response
    {
        return response()->json([
            'error' => 'Service Busy',
            'message' => 'Capacidade momentaneamente esgotada; tente novamente em instantes.',
            'pool' => $this->pool,
        ], Response::HTTP_SERVICE_UNAVAILABLE, ['Retry-After' => '1']);
    }
}
