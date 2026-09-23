<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Contracts\EquipamentoRepository;
use App\Modules\Inventario\Enums\SituacaoEquipamento;
use App\Modules\Inventario\Models\CategoriaInventario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InventarioController extends Controller
{
    public function __construct(private readonly EquipamentoRepository $equipamentos) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'categoria' => ['nullable', 'integer', 'exists:inventario_ti_categorias,id'],
            'situacao' => ['nullable', Rule::enum(SituacaoEquipamento::class)],
        ]);

        $paginator = $this->equipamentos->paginate($filters);
        $paginator->through(static fn ($equipamento) => [
            'id' => $equipamento->id,
            'nome' => $equipamento->nome,
            'patrimonio' => $equipamento->patrimonio,
            'numero_serie' => $equipamento->numero_serie,
            'ramal' => $equipamento->ramal,
            'categoria_id' => $equipamento->categoria_id,
            'unidade' => $equipamento->unidade,
            'emprestavel' => $equipamento->emprestavel,
            'quantidade' => $equipamento->quantidade,
            'observacao' => $equipamento->observacao,
            'categoria' => $equipamento->categoria?->nome,
            'situacao' => $equipamento->situacao->value,
            'responsavel' => $equipamento->usuario?->name ?? $equipamento->estacao?->usuario?->name,
            'diretoria' => $equipamento->diretoria,
            'ultima_movimentacao' => $equipamento->movimentacoes_max_created_at,
        ]);

        return Inertia::render('Inventario/InventarioIndex', [
            'equipamentos' => $paginator,
            'statistics' => $this->equipamentos->statistics(),
            'filters' => $filters,
            'filterOptions' => [
                'categorias' => CategoriaInventario::query()->where('ativo', true)->orderBy('nome')
                    ->get(['id', 'nome'])->map(static fn ($categoria) => [
                        'value' => $categoria->id,
                        'label' => $categoria->nome,
                    ]),
                'situacoes' => SituacaoEquipamento::options(),
            ],
        ]);
    }
}
