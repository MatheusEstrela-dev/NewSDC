<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\Estacao;
use App\Modules\Inventario\Requests\EstacaoRequest;
use App\Modules\Shared\Support\PadraoBusca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EstacaoController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:livre,ocupada'],
        ]);

        $query = Estacao::query()->with('usuario:id,name')->withCount('equipamentos');
        $padrao = PadraoBusca::contem($filters['search'] ?? null);
        if ($padrao !== null) {
            $query->where(static fn ($query) => $query
                ->where('nome', 'ilike', $padrao)
                ->orWhere('ponto_rede', 'ilike', $padrao));
        }
        if (($filters['status'] ?? null) === 'livre') {
            $query->whereNull('user_id');
        } elseif (($filters['status'] ?? null) === 'ocupada') {
            $query->whereNotNull('user_id');
        }

        return Inertia::render('Inventario/EstacoesIndex', [
            'estacoes' => $query->orderBy('nome')->paginate(15)->withQueryString(),
            // Sem a lista de usuarios: o campo do formulario busca sob demanda e o
            // ocupante atual (id e nome) ja vem em `usuario` de cada estacao.
            'filters' => $filters,
        ]);
    }

    public function store(EstacaoRequest $request): RedirectResponse
    {
        Estacao::create($request->validated());
        return redirect()->route('inventario.estacoes.index')->with('success', 'Estação cadastrada.');
    }

    public function update(EstacaoRequest $request, Estacao $estacao): RedirectResponse
    {
        $estacao->update($request->validated());
        return redirect()->back()->with('success', 'Estação atualizada.');
    }

    public function destroy(Estacao $estacao): RedirectResponse
    {
        abort_if($estacao->equipamentos()->exists(), 409, 'A estação possui equipamentos vinculados.');
        $estacao->delete();
        return redirect()->back()->with('success', 'Estação removida.');
    }
}
