<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Gestao;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\Gestao\CronogramaItemStoreRequest;
use App\Modules\Treinamento\Services\Gestao\GestaoCursoComandos;

class CronogramaStoreController extends Controller
{
    public function __construct(private readonly GestaoCursoComandos $comandos)
    {
    }

    public function __invoke(CronogramaItemStoreRequest $request, Treinamento $treinamento)
    {
        try {
            $this->comandos->adicionarItemDeCronograma($treinamento, $request->validated(), $request->user());
        } catch (\DomainException $e) {
            return back()->withErrors(['cronograma' => $e->getMessage()]);
        }

        return back()->with('success', 'Item adicionado ao cronograma.');
    }
}
