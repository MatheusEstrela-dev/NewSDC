<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Http\Middleware;

use App\Modules\Treinamento\Enums\StatusTreinamento;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Issue #86: encerrar o curso e ato do gestor.
 *
 * A rota de transicao de status recebe o destino no corpo, entao a permissao
 * fina nao cabe num `can:` de rota. Aqui so o destino CANCELADO exige
 * `treinamento.cursos.cancelar`; as demais transicoes (EM_ANDAMENTO,
 * CONCLUIDO) seguem com o `treinamento.cursos.edit` do grupo, que e a regra
 * de hoje -- sao o andamento normal da turma, nao o encerramento.
 *
 * Sem estado: nada e guardado em propriedade ou estatico, logo o middleware
 * pode ser resolvido uma vez por worker no Octane sem vazar entre requests.
 */
class TransicaoDeStatusAutorizada
{
    public const PERMISSAO_CANCELAR = 'treinamento.cursos.cancelar';

    public function handle(Request $request, Closure $next): Response
    {
        $destino = $request->input('status');

        if (is_string($destino) && strtoupper($destino) === StatusTreinamento::CANCELADO->value) {
            $usuario = $request->user();

            abort_unless(
                $usuario !== null && $usuario->can(self::PERMISSAO_CANCELAR),
                403
            );
        }

        return $next($request);
    }
}
