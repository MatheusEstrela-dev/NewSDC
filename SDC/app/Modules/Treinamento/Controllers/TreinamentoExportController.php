<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Services\TreinamentoService;
use Illuminate\Http\Request;

class TreinamentoExportController extends Controller
{
    public function __construct(
        private readonly TreinamentoService $treinamentoService
    ) {
    }

    public function __invoke(Request $request)
    {
        $filters = $request->only(['status', 'tipo']);
        $data = $this->treinamentoService->export($filters);

        return response()->json($data);
    }
}
