<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Application\Services\RatService;
use App\Modules\Rat\Http\Requests\ListRatRequest;
use App\Modules\Rat\Http\Resources\RatListResource;
use App\Modules\Rat\Http\Resources\RatResource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller web do módulo RAT — rotas de navegação e ações de escrita.
 *
 * SOLID - SRP: só responde às ações web (index, show, destroy, finalize).
 * SOLID - DIP: depende do RatService (camada de aplicação), não de infraestrutura.
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
     * Cria um novo RAT em branco e redireciona para a página de edição.
     */
    public function create(): RedirectResponse
    {
        $rat = $this->service->createNew();

        return redirect()->route('rat.show', $rat->id);
    }

    /**
     * Cria um novo RAT via POST (formulário) e redireciona para a página de edição.
     */
    public function store(): RedirectResponse
    {
        $rat = $this->service->createNew();

        return redirect()->route('rat.show', $rat->id);
    }

    /**
     * Página de detalhe/edição de um RAT.
     */
    public function show(string $id): Response
    {
        $rat = $this->service->findById($id);
        abort_if(is_null($rat), 404, 'RAT não encontrado.');

        return Inertia::render('Rat', ['rat' => new RatResource($rat)]);
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

