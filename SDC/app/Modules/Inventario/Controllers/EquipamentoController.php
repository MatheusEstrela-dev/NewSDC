<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\DTOs\EquipamentoData;
use App\Modules\Inventario\Requests\EquipamentoRequest;
use App\Modules\Inventario\Services\EquipamentoService;
use Illuminate\Http\RedirectResponse;

class EquipamentoController extends Controller
{
    public function __construct(private readonly EquipamentoService $service) {}

    public function store(EquipamentoRequest $request): RedirectResponse
    {
        $this->service->criar(EquipamentoData::fromArray($request->validated()));

        return redirect()->route('inventario.index')->with('success', 'Equipamento cadastrado.');
    }

    public function update(EquipamentoRequest $request, int $equipamento): RedirectResponse
    {
        $this->service->atualizar($equipamento, EquipamentoData::fromArray($request->validated()));

        return redirect()->back()->with('success', 'Equipamento atualizado.');
    }

    public function destroy(int $equipamento): RedirectResponse
    {
        $this->service->remover($equipamento);

        return redirect()->back()->with('success', 'Equipamento removido.');
    }
}
