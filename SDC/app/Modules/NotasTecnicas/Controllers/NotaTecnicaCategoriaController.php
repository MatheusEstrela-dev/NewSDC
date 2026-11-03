<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\NotasTecnicas\Models\NotaTecnicaCategoria;
use App\Modules\NotasTecnicas\Requests\SalvarCategoriaRequest;
use App\Modules\NotasTecnicas\Services\NotaTecnicaCategoriaService;
use Illuminate\Http\RedirectResponse;

/** Pastas do workspace. Responde com back(): quem chama e o modal do workspace. */
final class NotaTecnicaCategoriaController extends Controller
{
    public function __construct(private readonly NotaTecnicaCategoriaService $categorias) {}

    public function store(SalvarCategoriaRequest $request): RedirectResponse
    {
        $categoria = $this->categorias->criar(
            $request->validated('nome'),
            $request->validated('parent_id') === null ? null : (int) $request->validated('parent_id'),
        );

        return back()->with('success', "Pasta \"{$categoria->nome}\" criada.");
    }

    public function update(SalvarCategoriaRequest $request, NotaTecnicaCategoria $categoria): RedirectResponse
    {
        $this->categorias->renomear($categoria, $request->validated('nome'));

        return back()->with('success', 'Pasta renomeada.');
    }

    public function destroy(NotaTecnicaCategoria $categoria): RedirectResponse
    {
        $this->categorias->excluir($categoria);

        return back()->with('success', "Pasta \"{$categoria->nome}\" excluída.");
    }
}
