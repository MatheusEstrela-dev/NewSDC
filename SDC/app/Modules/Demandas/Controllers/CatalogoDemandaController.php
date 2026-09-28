<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Demandas\Models\DemandaAssunto;
use App\Modules\Demandas\Models\DemandaCategoria;
use App\Modules\Demandas\Requests\SalvarAssuntoRequest;
use App\Modules\Demandas\Requests\SalvarCategoriaRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CatalogoDemandaController extends Controller
{
    public function index(): Response
    {
        $categorias = DemandaCategoria::query()->orderBy('nome')->get(['id', 'nome', 'descricao', 'parent_id', 'ativo']);
        $assuntosModelos = DemandaAssunto::query()->orderBy('nome')->get(['id', 'nome', 'categoria_id', 'ativo', 'campos_dinamicos', 'form_automacao']);
        $categoriasPorId = $categorias->keyBy('id');

        $assuntos = $assuntosModelos->map(function (DemandaAssunto $assunto) use ($categoriasPorId) {
            $dados = $assunto->toArray();
            $dados['categoria_nome'] = $categoriasPorId->get($assunto->categoria_id)?->nome;

            return $dados;
        });

        return Inertia::render('Demandas/Catalogo', [
            'categorias' => $categorias,
            'assuntos' => $assuntos,
            'estatisticas' => [
                'categorias_ativas' => $categorias->where('ativo', true)->count(),
                'assuntos_ativos' => $assuntosModelos->where('ativo', true)->count(),
                'assuntos_com_automacao' => $assuntosModelos
                    ->filter(fn (DemandaAssunto $assunto) => filled($assunto->form_automacao['acao'] ?? null))
                    ->count(),
            ],
        ]);
    }

    public function storeCategoria(SalvarCategoriaRequest $request): RedirectResponse
    {
        $data = $request->validated();
        DemandaCategoria::create($data);
        return redirect()->back()->with('success', 'Categoria cadastrada.');
    }

    public function updateCategoria(SalvarCategoriaRequest $request, DemandaCategoria $categoria): RedirectResponse
    {
        $data = $request->validated();
        $categoria->update($data);
        return redirect()->back()->with('success', 'Categoria atualizada.');
    }

    public function storeAssunto(SalvarAssuntoRequest $request): RedirectResponse
    {
        $data = $request->validated();
        DemandaAssunto::create($data);
        return redirect()->back()->with('success', 'Assunto cadastrado.');
    }

    public function updateAssunto(SalvarAssuntoRequest $request, DemandaAssunto $assunto): RedirectResponse
    {
        $data = $request->validated();
        $assunto->update($data);
        return redirect()->back()->with('success', 'Assunto atualizado.');
    }
}
