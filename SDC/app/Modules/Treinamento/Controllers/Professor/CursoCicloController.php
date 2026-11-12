<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\CicloDoCursoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Base das quatro acoes do ciclo do curso (pausar, retomar, desativar,
 * reativar): cada controller invocavel so diz qual acao chama e a mensagem.
 */
abstract class CursoCicloController extends Controller
{
    public function __construct(private readonly CicloDoCursoService $ciclo) {}

    abstract protected function executar(CicloDoCursoService $ciclo, Treinamento $treinamento, User $por): void;

    abstract protected function mensagem(): string;

    public function __invoke(Request $request, Treinamento $treinamento): RedirectResponse
    {
        try {
            $this->executar($this->ciclo, $treinamento, $request->user());
        } catch (\DomainException $e) {
            return back()->withErrors(['ciclo' => $e->getMessage()]);
        }

        return back()->with('success', $this->mensagem());
    }
}
