<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Application\Services\RatService;
use App\Modules\Rat\Http\Requests\ListRatRequest;
use App\Modules\Rat\Http\Requests\UpdateRatRequest;
use App\Modules\Rat\Http\Resources\RatListResource;
use App\Modules\Rat\Http\Resources\RatResource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller web do módulo RAT — rotas de navegação e ações de escrita.
 *
 * Responsabilidade única: responde às ações web (index, show, destroy, finalize).
 * Inversão de Dependência: depende do RatService (camada de aplicação), não de infraestrutura.
 */
class RatController extends Controller
{
    public function __construct(
        private readonly RatService $service
    ) {}

    /**
     * Listagem paginada com filtros e estatísticas reais do banco.
     */
    public function index(ListRatRequest $request): Response
    {
        $filters = $request->toFilterDTO();
        $data    = $this->service->getIndexData($filters);

        return Inertia::render('RatIndex', [
            'rats'           => RatListResource::collection($data['rats']),
            'statistics'     => $data['statistics'],
            'municipalities' => $this->buildMunicipalityOptions($data['municipalities']),
            'cobradeTypes'   => [],
            'years'          => $this->buildYearOptions(),
            'filters'        => $request->validated(),
        ]);
    }

    /**
     * Página de Criação de um novo RAT (formulário em branco).
     */
    public function create(): Response
    {
        return Inertia::render('Rat');
    }

    /**
     * Cria um novo RAT via POST com dados do formulário e redireciona para a página de edição.
     */
    public function store(UpdateRatRequest $request): RedirectResponse
    {
        $data     = $request->safe()->except('finalize');
        $finalize = (bool) $request->input('finalize', false);

        $rat = $this->service->createWithData($data);

        if ($finalize) {
            $this->service->finalize($rat->id);
            return redirect()->route('rat.show', $rat->id)
                ->with('success', 'RAT criado e finalizado com sucesso!');
        }

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
        ]);
    }

    /**
     * Página de edição de um RAT.
     */
    public function edit(string $id): Response
    {
        $rat = $this->service->findById($id);
        abort_if(is_null($rat), 404, 'RAT não encontrado.');

        return Inertia::render('Rat', [
            'rat'        => new RatResource($rat),
            'lastUpdate' => $rat->updated_at?->toIso8601String(),
        ]);
    }

    /** Remove permanentemente o RAT e redireciona para a listagem. */
    public function destroy(string $id): RedirectResponse
    {
        $this->service->delete($id);

        return redirect()->route('rat.index')
            ->with('success', 'RAT removido com sucesso!');
    }

    // -------------------------------------------------------------------------
    // Helpers privados de apresentação (não contam no limite de 5 públicos)
    // -------------------------------------------------------------------------

    private function buildMunicipalityOptions(array $items): array
    {
        $options = [['value' => '', 'label' => 'Todos']];

        foreach ($items as $item) {
            $options[] = ['value' => $item, 'label' => $item];
        }

        return $options;
    }

    private function buildYearOptions(): array
    {
        $current = now()->year;
        $options = [['value' => '', 'label' => 'Todos']];

        for ($year = $current; $year >= $current - 5; $year--) {
            $options[] = ['value' => (string) $year, 'label' => (string) $year];
        }

        return $options;
    }
}

