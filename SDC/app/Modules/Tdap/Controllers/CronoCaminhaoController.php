<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\DTOs\CronoCaminhaoDTO;
use App\Modules\Tdap\Models\CronoCaminhao;
use App\Modules\Tdap\Requests\StoreCronoCaminhaoRequest;
use App\Modules\Tdap\Requests\UpdateCronoCaminhaoRequest;
use App\Modules\Tdap\Services\CronoCaminhaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CronoCaminhaoController extends Controller
{
    public function __construct(
        private readonly CronoCaminhaoService $service,
    ) {}

    public function store(StoreCronoCaminhaoRequest $request): RedirectResponse
    {
        try {
            $cc = $this->service->alocar(CronoCaminhaoDTO::fromRequest($request->validated()));

            return redirect()
                ->route('tdap.cronogramas.show', $cc->cronograma_id)
                ->with('success', 'Caminhao alocado ao cronograma.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function update(UpdateCronoCaminhaoRequest $request, CronoCaminhao $cronoCaminhao): RedirectResponse
    {
        $dto = new CronoCaminhaoDTO(
            cronograma_id: $cronoCaminhao->cronograma_id,
            caminhao_id:   $cronoCaminhao->caminhao_id,
            comunidade_id: $request->input('comunidade_id') !== '' && $request->input('comunidade_id') !== null ? (int) $request->input('comunidade_id') : null,
            agua_prevista: (float) $request->input('agua_prevista', 0),
            num_viagens:   (int) $request->input('num_viagens', 0),
            ordem:         (int) $request->input('ordem', 0),
        );

        $this->service->atualizar($cronoCaminhao->id, $dto);

        return redirect()
            ->route('tdap.cronogramas.show', $cronoCaminhao->cronograma_id)
            ->with('success', 'Alocacao atualizada.');
    }

    public function destroy(CronoCaminhao $cronoCaminhao, Request $request): RedirectResponse
    {
        if (! $request->user()?->can('tdap.cronogramas.edit')) {
            abort(403);
        }

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
}
