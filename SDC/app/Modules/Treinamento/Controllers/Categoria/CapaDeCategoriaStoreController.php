<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Categoria;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\CategoriaOferta;
use App\Modules\Treinamento\Requests\CapaStoreRequest;
use App\Modules\Treinamento\Services\CapaDeCategoriaService;
use Illuminate\Http\RedirectResponse;

/** Troca a foto de fundo da pasta de categoria (quem administra cursos: can na rota). */
class CapaDeCategoriaStoreController extends Controller
{
    public function __construct(private readonly CapaDeCategoriaService $capas) {}

    public function __invoke(CapaStoreRequest $request, CategoriaOferta $categoria): RedirectResponse
    {
        $this->capas->trocar($categoria, $request->file('capa'));

        return back()->with('success', "Capa de {$categoria->getLabel()} atualizada.");
    }
}
