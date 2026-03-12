<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\DTO\RatBoDTO;
use App\Modules\Rat\DTO\RatFilterDTO;
use App\Modules\Rat\Requests\ListRatRequest;
use App\Modules\Rat\Requests\StoreRatRequest;
use App\Modules\Rat\Requests\UpdateRatRequest;
use App\Modules\Rat\Resources\RatListResource;
use App\Modules\Rat\Resources\RatResource;
use App\Modules\Rat\Services\RatQueryService;
use App\Modules\Rat\Services\RatStatisticsService;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller CRUD principal do módulo RAT.
 *
 * FLUXO DE DADOS:
 *   Request (HTTP) -> Controller -> Service -> Model -> Banco
 *   Banco -> Model -> Resource -> Controller -> Response (Inertia)
 *
 * RESPONSABILIDADES:
 * - Receber requests HTTP
 * - Validar entrada via FormRequests
 * - Converter para DTOs
 * - Delegar para Services
 * - Retornar respostas Inertia
 */
class RatController extends Controller
{
    public function __construct(
        private readonly RatWriteService     $writeService,
        private readonly RatQueryService     $queryService,
        private readonly RatStatisticsService $statsService,
    ) {}

    /**
     * Lista ocorrências com filtros e estatísticas.
     *
     * FLUXO: Request -> RatFilterDTO -> RatQueryService -> Inertia (Rat/Index)
     */
    public function index(ListRatRequest $request): Response
    {
        $filters    = $request->toFilterDTO();
        $ocorrencias = $this->queryService->list($filters);
        $statistics = $this->statsService->getStatistics();

        return Inertia::render('Compdec/Rat/Index', [
            'ocorrencias' => RatListResource::collection($ocorrencias),
            'statistics'  => $statistics->toArray(),
            'filters'     => $filters->toArray(),
        ]);
    }

    /**
     * Formulário de criação de nova ocorrência.
     *
     * FLUXO: Inertia (Rat/Create)
     */
    public function create(): Response
    {
<<<<<<< HEAD
        return Inertia::render('Compdec/Rat/Create');
=======
        return Inertia::render('Rat');
>>>>>>> origin/dev
    }

    /**
     * Cria uma nova ocorrência RAT.
     *
     * FLUXO: StoreRatRequest -> RatBoDTO -> RatWriteService -> Redirect
     */
    public function store(StoreRatRequest $request): RedirectResponse
    {
        $dto        = RatBoDTO::fromArray($request->validated());
        $ocorrencia = $this->writeService->createWithData($dto);

        return redirect()
            ->route('compdec.rat.show', $ocorrencia->id)
            ->with('success', 'Ocorrência RAT criada com sucesso!');
    }

    /**
     * Visualização detalhada de uma ocorrência.
     *
     * FLUXO: ID -> RatQueryService -> Inertia (Rat/Show)
     */
    public function show(int $id): Response
    {
        $ocorrencia = $this->queryService->findById($id);

        if (!$ocorrencia) {
            abort(404, 'Ocorrência não encontrada');
        }

<<<<<<< HEAD
        return Inertia::render('Compdec/Rat/Show', [
            'ocorrencia' => new RatResource($ocorrencia),
=======
        return redirect()->route('rat.edit', $rat->id)
            ->with('success', 'RAT criado com sucesso!');
    }

    /**
     * Página de visualização somente leitura de um RAT.
     */
    public function show(string $id): Response
    {
        $rat = $this->service->findById($id);
        abort_if(is_null($rat), 404, 'RAT não encontrado.');

        return Inertia::render('Rat', [
            'rat'        => new RatResource($rat),
            'lastUpdate' => $rat->updated_at?->toIso8601String(),
>>>>>>> origin/dev
        ]);
    }

    /**
     * Formulário de edição.
     *
     * FLUXO: ID -> RatQueryService -> Inertia (Rat/Edit)
     */
    public function edit(int $id): Response
    {
        $ocorrencia = $this->queryService->findById($id);

<<<<<<< HEAD
        if (!$ocorrencia) {
            abort(404, 'Ocorrência não encontrada');
        }

        return Inertia::render('Compdec/Rat/Edit', [
            'ocorrencia' => new RatResource($ocorrencia),
=======
        return Inertia::render('Rat', [
            'rat'        => new RatResource($rat),
            'lastUpdate' => $rat->updated_at?->toIso8601String(),
>>>>>>> origin/dev
        ]);
    }

    /**
     * Atualiza dados de uma ocorrência.
     *
     * FLUXO: UpdateRatRequest -> RatBoDTO -> RatWriteService -> Redirect
     */
    public function update(UpdateRatRequest $request, int $id): RedirectResponse
    {
        $dto = RatBoDTO::fromArray($request->validated());
        $this->writeService->update($id, $dto);

        return redirect()
            ->route('compdec.rat.show', $id)
            ->with('success', 'Ocorrência atualizada com sucesso!');
    }

    /**
     * Remove ocorrência (soft delete).
     *
     * FLUXO: ID -> RatWriteService.delete() -> Redirect
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->writeService->delete($id);

        return redirect()
            ->route('compdec.rat.index')
            ->with('success', 'Ocorrência removida com sucesso!');
    }
}
