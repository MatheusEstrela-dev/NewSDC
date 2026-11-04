<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\DTOs\CronoCaminhaoDTO;
use App\Modules\Tdap\Models\CronoCaminhao;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Requests\StoreCronoCaminhaoRequest;
use App\Modules\Tdap\Requests\UpdateCronoCaminhaoRequest;
use App\Modules\Tdap\Services\CronoCaminhaoService;
use App\Modules\Tdap\Support\EscopoDeLeitura;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CronoCaminhaoController extends Controller
{
    public function __construct(
        private readonly CronoCaminhaoService $service,
    ) {}

    public function store(StoreCronoCaminhaoRequest $request): RedirectResponse
    {
        // O cronograma vem do corpo; o municipio dele decide o escopo.
        $municipioId = Cronograma::query()->whereKey((int) $request->validated('cronograma_id'))->value('municipio_id');
        EscopoDeLeitura::garantir($request->user(), $municipioId !== null ? (int) $municipioId : null);

        try {
            $cc = $this->service->alocar(CronoCaminhaoDTO::fromRequest($request->validated()));

            return redirect()
                ->route('tdap.cronogramas.show', $cc->cronograma_id)
                ->with('success', 'Caminhao alocado ao cronograma.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * cronograma_id/caminhao_id nao vem do payload -- eles sao a chave da
     * alocacao e o UpdateCronoCaminhaoRequest nem os valida
     * (`includeVinculo() === false`). Sao tomados do proprio model.
     *
     * O resto sai de `validated()`, e nao de `input()`: lendo o request cru os
     * limites do Request (min/max de agua_prevista e num_viagens) eram
     * validados e depois ignorados.
     */
    public function update(UpdateCronoCaminhaoRequest $request, CronoCaminhao $cronoCaminhao): RedirectResponse
    {
        $this->garantirEscopo($request, $cronoCaminhao);

        $dto = CronoCaminhaoDTO::fromRequest([
            ...$request->validated(),
            'cronograma_id' => $cronoCaminhao->cronograma_id,
            'caminhao_id'   => $cronoCaminhao->caminhao_id,
        ]);

        try {
            $this->service->atualizar($cronoCaminhao->id, $dto);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tdap.cronogramas.show', $cronoCaminhao->cronograma_id)
            ->with('success', 'Alocacao atualizada.');
    }

    public function destroy(CronoCaminhao $cronoCaminhao, Request $request): RedirectResponse
    {
        if (! $request->user()?->can('tdap.cronogramas.edit')) {
            abort(403);
        }
        $this->garantirEscopo($request, $cronoCaminhao);

        try {
            $cronogramaId = $cronoCaminhao->cronograma_id;
            $this->service->remover($cronoCaminhao->id);

            return redirect()
                ->route('tdap.cronogramas.show', $cronogramaId)
                ->with('success', 'Caminhao desalocado do cronograma.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /** 403 quando a alocacao e de cronograma de municipio fora do escopo. */
    private function garantirEscopo(Request $request, CronoCaminhao $cronoCaminhao): void
    {
        $municipioId = Cronograma::withTrashed()->whereKey($cronoCaminhao->cronograma_id)->value('municipio_id');
        EscopoDeLeitura::garantir($request->user(), $municipioId !== null ? (int) $municipioId : null);
    }
}
