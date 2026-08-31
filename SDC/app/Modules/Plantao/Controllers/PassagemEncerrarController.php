<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\PassagemInvalidaException;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Requests\EncerrarPassagemRequest;
use App\Modules\Plantao\Services\PassagemServicoService;
use Illuminate\Http\RedirectResponse;

class PassagemEncerrarController extends Controller
{
    public function __construct(
        private readonly PassagemServicoService $passagemService
    ) {
    }

    public function __invoke(EncerrarPassagemRequest $request, Plantao $plantao): RedirectResponse
    {
        $usuario = $request->user();

        // Decisao do usuario: so o dono do turno encerra por padrao. Quem tem
        // `encerrar_alheio` (supervisao/administracao) e a excecao prevista na
        // secao 4.3 do spec para o handshake nao travar quando quem saiu nunca
        // encerra. Falta de autorizacao, nao erro de formulario -> 403.
        if ((int) $plantao->plantonista_id !== (int) $usuario->id) {
            abort_unless(
                $usuario->can('plantao.passagem.encerrar_alheio'),
                403,
                'Voce so pode encerrar o proprio turno.'
            );
        }

        $dados = $request->validated();

        try {
            $this->passagemService->encerrar(
                $plantao->id,
                $dados['snapshots'],
                $dados['ocorrencias_destaque'] ?? null,
                (int) $request->user()->id
            );
        } catch (PassagemInvalidaException $e) {
            return back()->withErrors(['plantao' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Plantao encerrado. Aguardando aceite de quem assume.');
    }
}
