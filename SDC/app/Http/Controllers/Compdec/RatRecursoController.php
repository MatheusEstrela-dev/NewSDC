<?php

declare(strict_types=1);

namespace App\Http\Controllers\Compdec;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rat\RatRecursosRequest;
use App\Http\Requests\Rat\RatRecursosUpdateRequest;
use App\Models\Rat\RatOcorrencia;
use App\Models\Rat\Relatos\RatRelatoRecurso;
use App\Services\Rat\RatHistoricoService;
use App\Services\Rat\RatRecursoService;
use App\Services\Rat\RatRelatoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD de recursos empregados em uma ocorrência RAT.
 *
 * Responsabilidade única: gerencia RatRelatoRecurso e suas guarnições.
 * Padrão Ouro: Controlador → FormRequest → Serviço → Model → BD.
 *
 * Métodos públicos: listar · salvar · atualizar · remover
 */
class RatRecursoController extends Controller
{
    public function __construct(
        private readonly RatRecursoService   $recursoService,
        private readonly RatRelatoService    $relatoService,
        private readonly RatHistoricoService $historicoService,
    ) {}

    /**
     * Lista todos os recursos de uma ocorrência com suas guarnições.
     * Otimizado: query direta com eager loading (evita N+1).
     */
    public function index(RatOcorrencia $ocorrencia): Response
    {
        $recursos = RatRelatoRecurso::whereHas('ocorrenciaRelato', fn($q) =>
                $q->where('ocorrencia_id', $ocorrencia->id)
            )
            ->with('recursosEmpregados.componentesGuarnicao')
            ->get();

        return Inertia::render('Compdec/Rat/Recursos/Listar', [
            'ocorrencia' => $ocorrencia,
            'recursos'   => $recursos,
        ]);
    }

    /**
     * Salva um novo recurso com guarnição e vincula à ocorrência.
     * Usa transação para garantir atomicidade do recurso + empregados + guarnição.
     */
    public function store(RatRecursosRequest $request, RatOcorrencia $ocorrencia): RedirectResponse
    {
        $recurso = DB::transaction(function () use ($request, $ocorrencia) {
            $recurso = $this->recursoService->createRecurso(array_merge(
                $request->safe()->except('guarnicao'),
                ['created_by' => auth()->id()]
            ));

            // Vincula à ocorrência via tabela pivô polimórfica
            $this->relatoService->attachRelato(
                $ocorrencia,
                RatRelatoRecurso::class,
                $recurso->id
            );

            // Salva os componentes da guarnição aninhados
            foreach ($request->input('guarnicao', []) as $membro) {
                $empregado = $this->recursoService->addEmpregado($recurso->id, [
                    'recurso_tipo'  => $request->input('recurso_tipo'),
                    'viatura_placa' => $request->input('viatura_placa'),
                ]);

                $this->recursoService->addComponenteGuarnicao($empregado->id, $membro);
            }

            return $recurso;
        });

        $this->historicoService->log(
            $ocorrencia,
            'recurso.adicionado',
            ['recurso_id' => $recurso->id, 'tipo' => $recurso->recurso_tipo]
        );

        return redirect()
            ->route('compdec.rat.ocorrencias.recursos.index', $ocorrencia->id)
            ->with('sucesso', 'Recurso adicionado com sucesso!');
    }

    /**
     * Atualiza um recurso já registrado.
     */
    public function update(
        RatRecursosUpdateRequest $request,
        RatOcorrencia            $ocorrencia,
        RatRelatoRecurso         $recurso
    ): RedirectResponse {
        $recurso->update(array_merge(
            $request->safe()->except('guarnicao'),
            ['updated_by' => auth()->id()]
        ));

        $this->historicoService->log(
            $ocorrencia,
            'recurso.atualizado',
            ['recurso_id' => $recurso->id]
        );

        return redirect()
            ->route('compdec.rat.ocorrencias.recursos.index', $ocorrencia->id)
            ->with('sucesso', 'Recurso atualizado com sucesso!');
    }

    /**
     * Remove (exclusão suave) um recurso e seus empregados/guarnições em cascata.
     */
    public function destroy(RatOcorrencia $ocorrencia, RatRelatoRecurso $recurso): RedirectResponse
    {
        // Remove o pivô via morphOne antes de excluir
        $pivo = $recurso->ocorrenciaRelato;
        if ($pivo) {
            $this->relatoService->detachRelato($pivo->id);
        }

        // Cascata via booted() do model
        $recurso->delete();

        $this->historicoService->log(
            $ocorrencia,
            'recurso.removido',
            ['recurso_id' => $recurso->id]
        );

        return redirect()
            ->route('compdec.rat.ocorrencias.recursos.index', $ocorrencia->id)
            ->with('sucesso', 'Recurso removido com sucesso!');
    }
}
