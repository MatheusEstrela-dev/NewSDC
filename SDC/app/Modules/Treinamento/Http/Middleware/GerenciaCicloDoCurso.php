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
 * ou quem edita cursos (admin). Diferente de LecionaCurso, o admin nao precisa
 * ser professor do curso nem ter o acesso a area do professor.
 */
class GerenciaCicloDoCurso
{
    public const PERMISSAO_ADMIN = 'treinamento.cursos.edit';

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
            $usuario !== null
                && ($usuario->can(self::PERMISSAO_ADMIN) || $this->professores->leciona((int) $usuario->getKey(), $treinamento)),
            403
        );

        return $next($request);
    }
}
