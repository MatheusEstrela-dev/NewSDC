<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Inventario\Models\Equipamento;
use App\Modules\Inventario\Models\Estacao;
use App\Modules\Inventario\Models\Movimentacao;
use App\Modules\Inventario\Services\MovimentacaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MovimentacaoController extends Controller
{
    public function __construct(private readonly MovimentacaoService $service) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:ativo,devolvido'],
        ]);
        $query = Movimentacao::query()->with(['equipamento:id,nome,patrimonio', 'registradoPor:id,name']);
        if (! empty($filters['search'])) {
            $query->whereHas('equipamento', static fn ($query) => $query
                ->where('nome', 'ilike', '%'.$filters['search'].'%')
                ->orWhere('patrimonio', 'ilike', '%'.$filters['search'].'%'));
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return Inertia::render('Inventario/MovimentacoesIndex', [
            'movimentacoes' => $query->latest('data_saida')->paginate(15)->withQueryString(),
            'filters' => $filters,
            'equipamentos' => Equipamento::query()->whereNotIn('situacao', ['manutencao', 'baixado'])
                ->orderBy('nome')->get(['id', 'nome', 'patrimonio', 'quantidade']),
            'usuarios' => User::query()->orderBy('name')->get(['id', 'name']),
            'estacoes' => Estacao::query()->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'equipamento_id' => ['required', 'integer', 'exists:inventario_ti_equipamentos,id'],
            'tipo' => ['required', 'in:emprestimo,remanejamento'],
            'quantidade' => ['required', 'integer', 'min:1'],
            'usuario_destino_id' => ['nullable', 'integer', 'exists:users,id'],
            'estacao_destino_id' => ['nullable', 'integer', 'exists:inventario_ti_estacoes,id'],
            'data_prevista_devolucao' => ['nullable', 'date', 'after_or_equal:today'],
            'observacao' => ['nullable', 'string', 'max:5000'],
        ]);
        if (empty($data['usuario_destino_id']) && empty($data['estacao_destino_id'])) {
            throw ValidationException::withMessages(['usuario_destino_id' => 'Informe um usuário ou estação de destino.']);
        }
        $this->service->registrar($data, (int) $request->user()->id);
        return redirect()->back()->with('success', 'Movimentação registrada.');
    }

    public function devolver(int $movimentacao): RedirectResponse
    {
        $this->service->devolver($movimentacao);
        return redirect()->back()->with('success', 'Devolução registrada.');
    }
}
