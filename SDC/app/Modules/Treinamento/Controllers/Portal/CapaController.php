<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\CapaDoCursoService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Entrega a capa do curso ao cidadao. Curso fora do escopo dele (nao publicado
 * ou de outro publico) responde 404, igual a curso inexistente.
 */
class CapaController extends Controller
{
    public function __construct(private readonly CapaDoCursoService $capas) {}

    public function __invoke(Treinamento $treinamento): StreamedResponse
    {
        $cidadao = Auth::guard('cidadao')->user();

        abort_unless(
            $cidadao !== null
                && $treinamento->estaPublicado()
                && $treinamento->publico_alvo?->aceita($cidadao),
            404,
        );

        return $this->capas->responder($treinamento);
    }
}
