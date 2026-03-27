<?php

declare(strict_types=1);

namespace App\Http\Controllers\Compdec;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rat\RatVistoriaRequest;
use App\Models\Rat\RatOcorrencia;
use App\Models\Rat\Relatos\RatRelatoVistoria;
use App\Services\Rat\RatHistoricoService;
use App\Services\Rat\RatRelatoService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gerencia o laudo de vistoria técnica de uma ocorrência RAT.
 *
 * Responsabilidade única: CRUD de RatRelatoVistoria vinculado a uma ocorrência.
 * Padrão Ouro: Controlador → FormRequest → Serviço → Model → BD.
 *
 * Métodos públicos: exibir · salvar · atualizar
 */
class RatVistoriaController extends Controller
{
    public function __construct(
        private readonly RatRelatoService    $relatoService,
        private readonly RatHistoricoService $historicoService,
    ) {}

    /**
     * Exibe o laudo de vistoria de uma ocorrência.
     */
    public function show(RatOcorrencia $ocorrencia): Response
    {
        $vistoria = $ocorrencia->relatosMorph()
            ->get()
            ->map(fn($relato) => $relato->conteudo)
            ->filter(fn($conteudo) => $conteudo instanceof RatRelatoVistoria)
            ->first();

        return Inertia::render('Compdec/Rat/Vistoria/Exibir', [
            'ocorrencia' => $ocorrencia,
            'vistoria'   => $vistoria,
        ]);
    }

    /**
     * Salva um novo laudo de vistoria e vincula à ocorrência.
     */
    public function store(RatVistoriaRequest $request, RatOcorrencia $ocorrencia): RedirectResponse
    {
        $vistoria = RatRelatoVistoria::create(array_merge(
            $request->validated(),
            ['created_by' => auth()->id()]
        ));

        $this->relatoService->attachRelato(
            $ocorrencia,
            RatRelatoVistoria::class,
            $vistoria->id
        );

        $this->historicoService->log(
            $ocorrencia,
            'vistoria.registrada',
            ['vistoria_id' => $vistoria->id]
        );

        return redirect()
            ->route('compdec.rat.ocorrencias.vistoria.show', $ocorrencia->id)
            ->with('sucesso', 'Laudo de vistoria registrado com sucesso!');
    }

    /**
     * Atualiza o laudo de vistoria já registrado de uma ocorrência.
     */
    public function update(
        RatVistoriaRequest  $request,
        RatOcorrencia       $ocorrencia,
        RatRelatoVistoria   $vistoria
    ): RedirectResponse {
        $vistoria->update(array_merge(
            $request->validated(),
            ['updated_by' => auth()->id()]
        ));

        $this->historicoService->log(
            $ocorrencia,
            'vistoria.atualizada',
            ['vistoria_id' => $vistoria->id]
        );

        return redirect()
            ->route('compdec.rat.ocorrencias.vistoria.show', $ocorrencia->id)
            ->with('sucesso', 'Laudo de vistoria atualizado com sucesso!');
    }
}
