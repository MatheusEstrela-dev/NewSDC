<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\PassagemInvalidaException;
use App\Modules\Plantao\Requests\AbrirPassagemRequest;
use App\Modules\Plantao\Services\PassagemServicoService;
use Illuminate\Http\RedirectResponse;

/**
 * Entrada da maquina de estados da passagem de servico (spec, secao 4). Sem
 * esta rota o ritual inteiro (encerrar -> aceitar -> relatorio) era inalcancavel
 * em producao: `abrirTurno()` estava implementado e testado, mas so era chamado
 * por teste - o submit do "Abrir Plantao" nao ia a lugar nenhum.
 */
class PassagemAbrirController extends Controller
{
    public function __construct(
        private readonly PassagemServicoService $passagemService
    ) {
    }

    public function __invoke(AbrirPassagemRequest $request): RedirectResponse
    {
        $dados = $request->validated();

        try {
            $this->passagemService->abrirTurno([
                // Quem abre e sempre o autenticado, nunca um id vindo do cliente.
                'plantonista_id' => (int) $request->user()->id,
                'data' => $dados['data'],
                'periodo' => $dados['periodo'],
                'localizacao' => $dados['localizacao'] ?? null,
            ]);
        } catch (PassagemInvalidaException $e) {
            // Chave propria (nao 'plantao'): o erro de abertura e exibido no
            // banner da pagina de indice, enquanto 'plantao' pertence aos modais
            // de encerrar/aceitar, que preservam estado e mostram o proprio erro.
            return back()->withErrors(['abertura' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Plantao aberto.');
    }
}
