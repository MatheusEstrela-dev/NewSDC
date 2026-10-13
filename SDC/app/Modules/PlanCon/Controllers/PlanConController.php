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
use App\Modules\Shared\Support\CsvSeguro;
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
     * CSV com a situacao do plano de contingencia de cada municipio de MG.
     * Ponto e virgula e BOM para o Excel em pt-BR abrir com acentos; o nome do
     * municipio passa pelo CsvSeguro como os demais exports.
     */
    public function export(): StreamedResponse
    {
        $linhas = $this->planoService->linhasExportacao();

        return response()->streamDownload(function () use ($linhas): void {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['municipio', 'codigo_ibge', 'tem_plano', 'versao', 'situacao', 'enviado_em'], ';', '"', '');

            foreach ($linhas as $l) {
                fputcsv($out, [
                    CsvSeguro::celula($l['municipio']),
                    $l['codigo_ibge'],
                    $l['tem_plano'] ? 'sim' : 'nao',
                    $l['versao'],
                    $l['situacao'],
                    $l['enviado_em'] ? substr((string) $l['enviado_em'], 0, 10) : '',
                ], ';', '"', '');
            }

            fclose($out);
        }, 'plancon-municipios-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
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
