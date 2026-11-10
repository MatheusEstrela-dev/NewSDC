<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\ProfessorService;

class ProfessorDestroyController extends Controller
{
    public function __construct(
        private readonly ProfessorService $professorService
    ) {
    }

    public function __invoke(Treinamento $treinamento, int $userId)
    {
        $this->professorService->remover($treinamento, $userId);

        return back()->with('success', 'Professor removido.');
    }
}
