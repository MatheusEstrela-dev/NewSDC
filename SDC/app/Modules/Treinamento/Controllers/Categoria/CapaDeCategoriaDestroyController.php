<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Categoria;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\CategoriaOferta;
use App\Modules\Treinamento\Services\CapaDeCategoriaService;
use Illuminate\Http\RedirectResponse;

/** Remove a foto de fundo da pasta de categoria; ela volta ao gradiente com o icone. */
class CapaDeCategoriaDestroyController extends Controller
{
    public function __construct(private readonly CapaDeCategoriaService $capas) {}

    public function __invoke(CategoriaOferta $categoria): RedirectResponse
    {
        $this->capas->remover($categoria);

        return back()->with('success', "Capa de {$categoria->getLabel()} removida.");
    }
}
