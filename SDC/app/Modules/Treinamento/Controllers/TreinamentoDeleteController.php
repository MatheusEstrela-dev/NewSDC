<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Services\TreinamentoService;

class TreinamentoDeleteController extends Controller
{
    public function __construct(
        private readonly TreinamentoService $treinamentoService
    ) {
    }

    public function __invoke(int $id)
    {
        $this->treinamentoService->delete($id);

        return redirect()->route('treinamentos.index')
            ->with('success', 'Treinamento removido com sucesso!');
    }
}
