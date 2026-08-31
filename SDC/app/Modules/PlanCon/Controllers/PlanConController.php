<?php

declare(strict_types=1);

namespace App\Modules\PlanCon\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compdec\Models\CompdecPlanoContingencia;
use App\Modules\PlanCon\Requests\UploadPlanoRequest;
use App\Modules\PlanCon\Services\PlanoContingenciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Illuminate\Http\Response as HttpResponse;

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
            // Usuario municipal envia direto (orgao vem do vinculo). Conta
            // estadual escolhe o municipio, entao recebe a lista.
            'podeEnviar' => true,
            'municipiosParaEnvio' => $this->planoService->municipiosParaEnvio($request->user()),
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

    /**
     * Envio do plano pelo proprio municipio. O orgao vem do usuario logado --
     * o request nao escolhe municipio, entao ninguem envia plano em nome de
     * outra cidade.
     */
    public function store(UploadPlanoRequest $request): RedirectResponse
    {
        try {
            $resultado = $this->planoService->enviarPlanosDoUsuario(
                $request->user(),
                $request->file('files', []),
                $request->input('versao'),
                $request->input('observacoes'),
                $request->integer('municipio_id') ?: null,
            );
        } catch (RuntimeException $e) {
            return redirect()->route('plancon.index')->with('error', $e->getMessage());
        }

        Cache::forget('dashboard.stats.full');

        $mensagem = sprintf('%d plano(s) enviado(s).', $resultado['enviados']);

        if ($resultado['erros']) {
            $mensagem .= ' ' . implode(' ', array_values($resultado['erros']));
        }

        return redirect()
            ->route('plancon.index')
            ->with($resultado['erros'] ? 'warning' : 'success', $mensagem)
            ->with('upload_erros', $resultado['erros']);
    }

    public function download(CompdecPlanoContingencia $planoContingencia): HttpResponse
    {
        return $this->planoService->downloadPlano($planoContingencia);
    }
}
