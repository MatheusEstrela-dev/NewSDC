<?php

declare(strict_types=1);

namespace App\Modules\PlanCon\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PlanCon\Models\PlanoContingencia;
use App\Modules\PlanCon\Requests\UploadPlanoRequest;
use App\Modules\PlanCon\Services\PlanoContingenciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlanConController extends Controller
{
    public function __construct(
        private readonly PlanoContingenciaService $planoService
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'municipio_id']);
        $planos = $this->planoService->list($filters, 15);
        $statistics = $this->planoService->getStatistics();

        return Inertia::render('PlanCon/PlanConIndex', [
            'planos' => $planos,
            'statistics' => $statistics,
            'filters' => $filters,
        ]);
    }

    public function stats(): Response
    {
        $statistics = $this->planoService->getStatistics();

        return Inertia::render('PlanCon/Stats', [
            'statistics' => $statistics,
        ]);
    }

    public function municipiosComPlano(): Response
    {
        $municipios = $this->planoService->getMunicipiosComPlano();

        return Inertia::render('PlanCon/MunicipiosComPlano', [
            'municipios' => $municipios,
        ]);
    }

    public function municipiosSemPlano(): Response
    {
        $municipios = $this->planoService->getMunicipiosSemPlano();

        return Inertia::render('PlanCon/MunicipiosSemPlano', [
            'municipios' => $municipios,
        ]);
    }

    public function store(UploadPlanoRequest $request): RedirectResponse
    {
        $resultado = $this->planoService->uploadPlanos(
            $request->file('files', []),
            $request->integer('municipio_id') ?: null
        );

        $mensagem = sprintf(
            '%d plano(s) criado(s), %d atualizado(s).',
            $resultado['criados'],
            $resultado['atualizados']
        );

        return redirect()
            ->route('plancon.index')
            ->with($resultado['erros'] ? 'warning' : 'success', $mensagem)
            ->with('upload_erros', $resultado['erros']);
    }

    public function download(PlanoContingencia $planoContingencia): StreamedResponse
    {
        return $this->planoService->downloadPlano($planoContingencia);
    }
}
