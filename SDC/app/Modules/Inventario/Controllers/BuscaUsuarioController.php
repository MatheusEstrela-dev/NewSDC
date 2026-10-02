<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Queries\BuscaUsuarioQuery;
use App\Modules\Inventario\Requests\BuscaUsuarioRequest;
use Illuminate\Http\JsonResponse;

class BuscaUsuarioController extends Controller
{
    public function __construct(private readonly BuscaUsuarioQuery $busca) {}

    public function __invoke(BuscaUsuarioRequest $request): JsonResponse
    {
        return response()->json($this->busca->porNome($request->termo()));
    }
}
