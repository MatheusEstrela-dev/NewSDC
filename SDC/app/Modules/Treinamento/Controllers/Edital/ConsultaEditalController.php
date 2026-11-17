<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Edital;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\DTOs\ConsultaEditalFiltroDTO;
use App\Modules\Treinamento\Services\ConsultaEditalService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tela da consulta para edital. Sem curso escolhido nada e consultado: as
 * listas voltam vazias e so as opcoes dos filtros sao carregadas.
 */
class ConsultaEditalController extends Controller
{
    public function __construct(private readonly ConsultaEditalService $consulta) {}

    public function __invoke(Request $request): Response
    {
        $filtro = ConsultaEditalFiltroDTO::fromArray($request->query());

        $resultado = $filtro->temCursos()
            ? $this->consulta->consultar($filtro)
            : [
                'pessoas' => new LengthAwarePaginator([], 0, ConsultaEditalService::POR_PAGINA),
                'municipios' => [],
                'total_pessoas' => 0,
                'total_municipios' => 0,
            ];

        return Inertia::render('Treinamento/ConsultaEdital/Index', [
            'filtros' => $filtro->toArray(),
            'opcoes' => fn () => $this->consulta->opcoes(),
            'pessoas' => JsonResource::collection($resultado['pessoas']->withQueryString()),
            'municipios' => $resultado['municipios'],
            'total_pessoas' => $resultado['total_pessoas'],
            'total_municipios' => $resultado['total_municipios'],
        ]);
    }
}
