<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Demandas\Models\DemandaAssunto;
use App\Modules\Demandas\Models\DemandaCategoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CatalogoDemandaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Demandas/Catalogo', [
            'categorias' => DemandaCategoria::query()->orderBy('nome')->get(['id', 'nome', 'descricao', 'parent_id', 'ativo']),
            'assuntos' => DemandaAssunto::query()->orderBy('nome')->get(['id', 'nome', 'categoria_id', 'ativo']),
        ]);
    }

    public function storeCategoria(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer', Rule::exists('demanda_categorias', 'id')->whereNull('parent_id')],
        ]);
        DemandaCategoria::create($data);
        return redirect()->back()->with('success', 'Categoria cadastrada.');
    }

    public function updateCategoria(Request $request, DemandaCategoria $categoria): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['sometimes', 'required', 'string', 'max:100'],
            'descricao' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'parent_id' => ['sometimes', 'nullable', 'integer', Rule::exists('demanda_categorias', 'id')->whereNull('parent_id')->whereNot('id', $categoria->id)],
            'ativo' => ['sometimes', 'boolean'],
        ]);
        $categoria->update($data);
        return redirect()->back()->with('success', 'Categoria atualizada.');
    }

    public function storeAssunto(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:150', 'unique:demanda_assuntos,nome'],
            'categoria_id' => ['nullable', 'integer', 'exists:demanda_categorias,id'],
        ]);
        DemandaAssunto::create($data);
        return redirect()->back()->with('success', 'Assunto cadastrado.');
    }

    public function updateAssunto(Request $request, DemandaAssunto $assunto): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['sometimes', 'required', 'string', 'max:150', Rule::unique('demanda_assuntos', 'nome')->ignore($assunto->id)],
            'categoria_id' => ['sometimes', 'nullable', 'integer', 'exists:demanda_categorias,id'],
            'ativo' => ['sometimes', 'boolean'],
        ]);
        $assunto->update($data);
        return redirect()->back()->with('success', 'Assunto atualizado.');
    }
}
