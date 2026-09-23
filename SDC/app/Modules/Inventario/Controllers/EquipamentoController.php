<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Contracts\EquipamentoRepository;
use App\Modules\Inventario\Enums\SituacaoEquipamento;
use App\Modules\Inventario\Models\Equipamento;
use App\Modules\Inventario\Requests\EquipamentoRequest;
use Illuminate\Http\RedirectResponse;

class EquipamentoController extends Controller
{
    public function __construct(private readonly EquipamentoRepository $equipamentos) {}

    public function store(EquipamentoRequest $request): RedirectResponse
    {
        $equipamento = new Equipamento($request->validated());
        $equipamento->situacao = SituacaoEquipamento::DISPONIVEL;
        $this->equipamentos->save($equipamento);

        return redirect()->route('inventario.index')->with('success', 'Equipamento cadastrado.');
    }

    public function update(EquipamentoRequest $request, int $equipamento): RedirectResponse
    {
        $model = $this->equipamentos->find($equipamento) ?? abort(404);
        abort_if(
            $model->movimentacoes()->where('status', 'ativo')->exists()
                && (int) $request->validated('quantidade') !== $model->quantidade,
            409,
            'Não é possível alterar a quantidade durante uma movimentação ativa.'
        );
        $model->fill($request->validated());
        $this->equipamentos->save($model);

        return redirect()->back()->with('success', 'Equipamento atualizado.');
    }

    public function destroy(int $equipamento): RedirectResponse
    {
        $model = $this->equipamentos->find($equipamento) ?? abort(404);
        abort_if($model->movimentacoes()->where('status', 'ativo')->exists(), 409, 'Há movimentação ativa para este equipamento.');
        $model->delete();

        return redirect()->back()->with('success', 'Equipamento removido.');
    }
}
