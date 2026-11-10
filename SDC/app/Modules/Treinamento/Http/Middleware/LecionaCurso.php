<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Http\Middleware;

use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\ProfessorService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LecionaCurso
{
    public function __construct(private readonly ProfessorService $professorService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $treinamento = $request->route('treinamento');

        // Binding implicito so ocorre com parametro tipado no controller; sem ele
        // (ou se este middleware rodar antes do binding) chega o id cru.
        if (!$treinamento instanceof Treinamento && $treinamento !== null) {
            $treinamento = Treinamento::query()->find($treinamento);
        }

        $userId = $request->user()?->getKey();

        // A area do professor e so do EAD: um curso de outro tipo nem existe aqui.
        abort_unless($treinamento instanceof Treinamento && $treinamento->isEad(), 404);

        abort_unless(
            $userId !== null
                && $this->professorService->leciona((int) $userId, $treinamento),
            403
        );

        return $next($request);
    }
}
