<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Http\Middleware;

use App\Modules\Pmda\Models\PmdaComunidade;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Pmda\Models\PmdaRepresentante;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Barra escrita de CONTEUDO em plano que nao aceita mais edicao.
 *
 * Fica em middleware, e nao numa checagem por controller, porque a regra e sempre a
 * mesma para as 20 rotas de conteudo do modulo: o que varia e por onde o plano
 * aparece na URL, nao o que se decide sobre ele. Espalhada, cada rota nova nascia
 * sem guarda e ninguem percebia -- foi assim que um PMDA arquivado continuou
 * aceitando salvar em todas as abas.
 *
 * NAO cobre as rotas de ciclo de vida (enviar/aprovar/arquivar/pedir-alteracao),
 * que existem justamente para mexer no plano fora da janela de edicao, nem copiar
 * e excluir, que tem regra propria.
 */
class GarantirPmdaEditavel
{
    public function handle(Request $request, Closure $next): Response
    {
        $plano = $this->planoDaRota($request);

        if ($plano !== null && ! $plano->status->permiteEdicao()) {
            $mensagem = sprintf(
                'Este PMDA está %s e não aceita mais edição. Para alterar, duplique o plano e envie um novo.',
                mb_strtolower($plano->status->getLabel())
            );

            if ($request->expectsJson()) {
                abort(Response::HTTP_FORBIDDEN, $mensagem);
            }

            return back()->withErrors(['plano' => $mensagem]);
        }

        return $next($request);
    }

    /**
     * O plano vem direto na rota na maioria dos casos; comunidade e representante sao
     * filhos que carregam o plano por um salto (o representante, por dois). Null quando a rota nao amarra nenhum plano: sem alvo, nada a barrar.
     */
    private function planoDaRota(Request $request): ?PmdaPlano
    {
        $plano = $request->route('plano');

        if ($plano instanceof PmdaPlano) {
            return $plano;
        }

        $comunidade = $request->route('comunidade');
        if ($comunidade instanceof PmdaComunidade) {
            return $comunidade->plano;
        }

        $representante = $request->route('representante');
        if ($representante instanceof PmdaRepresentante) {
            return $representante->comunidade?->plano;
        }

        return null;
    }
}
