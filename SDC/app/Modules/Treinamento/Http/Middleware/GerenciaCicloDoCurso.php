<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Http\Middleware;

use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\ProfessorService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pausar, retomar, desativar e reativar um curso EAD: o professor designado
 * ou quem edita cursos (admin), pela regra unica de ProfessorService::gerencia.
 */
class GerenciaCicloDoCurso
{
    public function __construct(private readonly ProfessorService $professores) {}

    public function handle(Request $request, Closure $next): Response
    {
        $treinamento = $request->route('treinamento');

        if (! $treinamento instanceof Treinamento && $treinamento !== null) {
            $treinamento = Treinamento::query()->find($treinamento);
        }

        abort_unless($treinamento instanceof Treinamento && $treinamento->isEad(), 404);

        $usuario = $request->user();

        abort_unless(
            $usuario !== null && $this->professores->gerencia($usuario, $treinamento),
            403
        );

        return $next($request);
    }
}
