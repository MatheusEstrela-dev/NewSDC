<?php

declare(strict_types=1);

namespace App\Modules\PlanCon\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PlanCon\Models\PlanoContingencia;
use App\Modules\PlanCon\Requests\UploadPlanoRequest;
use App\Modules\PlanCon\Services\PlanoContingenciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
            'stats' => $statistics,
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

    public function municipiosComPlano(Request $request): Response
    {
        $filters = $request->only(['search']);
        $resultado = $this->planoService->listMunicipiosComPlano(15, $filters);

        return Inertia::render('PlanCon/MunicipiosComPlano', [
            'municipios' => $resultado['data'],
            'pagination' => $resultado['pagination'],
            'filters' => $filters,
        ]);
    }

    public function municipiosSemPlano(Request $request): Response
    {
        $filters = $request->only(['search']);
        $resultado = $this->planoService->listMunicipiosSemPlano(15, $filters);

        return Inertia::render('PlanCon/MunicipiosSemPlano', [
            'municipios' => $resultado['data'],
            'pagination' => $resultado['pagination'],
            'filters' => $filters,
        ]);
    }

    public function store(UploadPlanoRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->loadMissing('orgaoPrincipal');

        $orgao = $user->orgaoPrincipal
            ?? $user->orgaos()->wherePivot('is_principal', true)->first()
            ?? ($user->orgaos()->count() === 1 ? $user->orgaos()->first() : null);

        if (! $orgao?->municipio_id) {
            return redirect()
                ->route('plancon.index')
                ->with('error', 'Seu usuario nao possui um municipio vinculado ao orgao principal. Contate o administrador.');
        }

        $resultado = $this->planoService->uploadPlanos(
            $request->file('files', []),
            (int) $orgao->municipio_id
        );

        Cache::forget('dashboard.stats.full');

        $mensagem = sprintf(
            '%d plano(s) criado(s), %d atualizado(s).',
            $resultado['criados'],
            $resultado['atualizados']
        );

        if ($resultado['erros']) {
            $mensagem .= ' ' . implode(' ', array_values($resultado['erros']));
        }

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
