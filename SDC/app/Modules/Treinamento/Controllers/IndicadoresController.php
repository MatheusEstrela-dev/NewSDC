<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\DTOs\IndicadoresFiltroDTO;
use App\Modules\Treinamento\Services\IndicadoresConsulta;
use App\Modules\Treinamento\Services\IndicadoresService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Painel de indicadores das Capacitacoes. Cada bloco e uma closure: o Inertia
 * so avalia o que a tela pede (visita parcial), e o filtro vale para todos.
 */
class IndicadoresController extends Controller
{
    /** Mes do detalhe mensal ('YYYY-MM'). */
    private const FORMATO_MES = '/^\d{4}-(0[1-9]|1[0-2])$/';

    public function __construct(private readonly IndicadoresService $indicadores) {}

    public function __invoke(Request $request): Response
    {
        $filtro = IndicadoresFiltroDTO::fromArray($request->query());

        // Uma consulta por requisicao: os closures abaixo dividem os mesmos dados.
        $consulta = $this->indicadores->para($filtro);

        return Inertia::render('Treinamento/Indicadores/Index', [
            'filtros' => fn () => $filtro->toArray(),
            'opcoes' => fn () => $consulta->opcoes(),
            // Todos os municipios de MG, sem filtro: a tela nao pede de novo ao filtrar.
            'territorio' => fn () => $consulta->territorio(),
            'kpis' => fn () => $consulta->kpis(),
            'mapa' => fn () => $consulta->mapa(),
            'semMunicipio' => fn () => $consulta->semMunicipio(),
            'porRedec' => fn () => $consulta->porRedec(),
            'porMes' => fn () => $consulta->porMes(),
            'porAno' => fn () => $consulta->porAno(),
            'porSexo' => fn () => $consulta->porSexo(),
            'porFaixaEtaria' => fn () => $consulta->porFaixaEtaria(),
            'porCurso' => fn () => $consulta->porCurso(),
            'rankingMunicipios' => fn () => $consulta->rankingMunicipios(),
            'pessoasPorCurso' => fn () => $consulta->pessoasPorCurso(),
            'pessoasPorMunicipio' => fn () => $consulta->pessoasPorMunicipio(),
            'satisfacao' => fn () => $consulta->satisfacao(),
            'tabela' => fn () => JsonResource::collection($consulta->tabela()->withQueryString()),
            // So vem quando a tela pede (clique num mes do grafico), com ?mes=YYYY-MM.
            'detalheMes' => Inertia::lazy(fn () => $this->detalheMes($request, $consulta)),
            // So vem quando a tela pede (Gerar relatorio): a tabela inteira, sem paginar.
            'relatorio' => Inertia::lazy(fn () => $consulta->relatorio()),
        ]);
    }

    /** @return array<string, mixed>|null */
    private function detalheMes(Request $request, IndicadoresConsulta $consulta): ?array
    {
        $mes = $request->input('mes');

        return is_string($mes) && preg_match(self::FORMATO_MES, $mes) ? $consulta->detalheMes($mes) : null;
    }
}
