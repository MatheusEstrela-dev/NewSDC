<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\DesignarProfessorRequest;
use App\Modules\Treinamento\Services\ProfessorService;

class ProfessorStoreController extends Controller
{
    public function __construct(
        private readonly ProfessorService $professorService
    ) {
    }

    public function __invoke(DesignarProfessorRequest $request, Treinamento $treinamento)
    {
        try {
            $this->professorService->designar($treinamento, (int) $request->validated('user_id'));
        } catch (\DomainException $e) {
            return back()->withErrors(['user_id' => $e->getMessage()]);
        }

        return back()->with('success', 'Professor designado.');
    }
}
