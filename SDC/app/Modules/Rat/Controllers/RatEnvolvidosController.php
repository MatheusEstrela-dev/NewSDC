<?php

declare(strict_types=1);

namespace App\Http\Controllers\Compdec;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rat\RatEnvolvidosRequest;
use App\Http\Requests\Rat\RatEnvolvidosUpdateRequest;
use App\Models\Rat\RatOcorrencia;
use App\Models\Rat\Relatos\RatRelatoEnvolvidos;
use App\Services\Rat\RatHistoricoService;
use App\Services\Rat\RatRelatoService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD de envolvidos (vítimas, agentes, testemunhas) em uma ocorrência RAT.
 *
 * Responsabilidade única: gerencia RatRelatoEnvolvidos de uma ocorrência.
 * Padrão Ouro: Controlador → FormRequest → Serviço → Model → BD.
 *
 * Métodos públicos: listar · salvar · atualizar · remover
 */
class RatEnvolvidosController extends Controller
{
    public function __construct(
        private readonly RatRelatoService    $relatoService,
        private readonly RatHistoricoService $historicoService,
    ) {}

    /**
     * Lista todos os envolvidos de uma ocorrência.
     */
    public function index(RatOcorrencia $ocorrencia): Response
    {
        $envolvidos = $ocorrencia->relatosMorph()
            ->get()
            ->map(fn($relato) => $relato->conteudo)
            ->filter(fn($conteudo) => $conteudo instanceof RatRelatoEnvolvidos)
            ->values();

        return Inertia::render('Compdec/Rat/Envolvidos/Listar', [
            'ocorrencia' => $ocorrencia,
            'envolvidos' => $envolvidos,
        ]);
    }

    /**
     * Salva um novo envolvido e vincula à ocorrência via polimorfismo.
     */
    public function store(RatEnvolvidosRequest $request, RatOcorrencia $ocorrencia): RedirectResponse
    {
        $envolvido = RatRelatoEnvolvidos::create(array_merge(
            $request->validated(),
            ['created_by' => auth()->id()]
        ));

        $this->relatoService->attachRelato(
            $ocorrencia,
            RatRelatoEnvolvidos::class,
            $envolvido->id
        );

        $this->historicoService->log(
            $ocorrencia,
            'envolvido.adicionado',
            ['envolvido_id' => $envolvido->id, 'nome' => $envolvido->p_nome_completo]
        );

        return redirect()
            ->route('compdec.rat.ocorrencias.envolvidos.index', $ocorrencia->id)
            ->with('sucesso', "Envolvido {$envolvido->p_nome_completo} adicionado com sucesso!");
    }

    /**
     * Atualiza os dados de um envolvido já registrado.
     */
    public function update(
        RatEnvolvidosUpdateRequest $request,
        RatOcorrencia              $ocorrencia,
        RatRelatoEnvolvidos        $envolvido
    ): RedirectResponse {
        $envolvido->update($request->validated());

        $this->historicoService->log(
            $ocorrencia,
            'envolvido.atualizado',
            ['envolvido_id' => $envolvido->id]
        );

        return redirect()
            ->route('compdec.rat.ocorrencias.envolvidos.index', $ocorrencia->id)
            ->with('sucesso', 'Envolvido atualizado com sucesso!');
    }

    /**
     * Remove (exclusão suave) um envolvido da ocorrência.
     * Remove também a entrada na tabela pivô polimórfica.
     */
    public function destroy(RatOcorrencia $ocorrencia, RatRelatoEnvolvidos $envolvido): RedirectResponse
    {
        // Remove o pivô via morphOne antes de excluir
        $pivo = $envolvido->ocorrenciaRelato;
        if ($pivo) {
            $this->relatoService->detachRelato($pivo->id);
        }

        $envolvido->delete();

        $this->historicoService->log(
            $ocorrencia,
            'envolvido.removido',
            ['envolvido_id' => $envolvido->id]
        );

        return redirect()
            ->route('compdec.rat.ocorrencias.envolvidos.index', $ocorrencia->id)
            ->with('sucesso', 'Envolvido removido com sucesso!');
    }
}
