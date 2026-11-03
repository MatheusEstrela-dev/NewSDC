<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\NotasTecnicas\Models\NotaTecnica;
use App\Modules\NotasTecnicas\Services\NotaTecnicaColecaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Liga/desliga a estrela de favorita da nota para o proprio usuario. */
final class NotaTecnicaFavoritoController extends Controller
{
    public function __construct(private readonly NotaTecnicaColecaoService $colecoes) {}

    public function alternar(Request $request, NotaTecnica $nota): RedirectResponse
    {
        // Rascunho invisivel ao leitor nao pode ser favoritado.
        $this->authorize('view', $nota);

        $favorita = $this->colecoes->alternarFavorito($nota, $request->user());

        return back()->with('success', $favorita
            ? 'Nota adicionada as favoritas.'
            : 'Nota removida das favoritas.');
    }
}
