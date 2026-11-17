<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Gestao;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\CategoriaTreinamento;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Resources\TreinamentoListResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lista da "Área do Gestor" (Sidebar, issue #86): landing analoga ao "Meus
 * cursos" do professor, mas para quem tem treinamento.gestor.acessar - todo
 * curso, nao so os EAD designados.
 *
 * Reaproveita o mesmo TreinamentoListResource do catalogo (treinamentos.index):
 * ja traz status_exibicao sem query extra (ver bloco 1) e evita manter dois
 * formatos de linha para a mesma entidade.
 */
class IndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'tipo', 'categoria']);

        $query = Treinamento::query();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('titulo', 'like', "%{$filters['search']}%")
                    ->orWhere('descricao', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (!empty($filters['categoria'])) {
            $query->where('categoria', $filters['categoria']);
        }

        $treinamentos = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return Inertia::render('Treinamento/Gestao/Index', [
            'treinamentos' => TreinamentoListResource::collection($treinamentos),
            'filters' => $filters,
            'filterOptions' => [
                'status' => StatusTreinamento::toSelectArray(),
                'tipos' => TipoTreinamento::toSelectArray(),
                'categorias' => CategoriaTreinamento::toSelectArray(),
            ],
        ]);
    }
}
