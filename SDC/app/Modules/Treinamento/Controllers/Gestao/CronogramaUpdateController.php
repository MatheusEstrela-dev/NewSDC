<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Gestao;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Controllers\Gestao\Concerns\EscopoDoCurso;
use App\Modules\Treinamento\Models\CursoCronograma;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\Gestao\CronogramaItemUpdateRequest;
use App\Modules\Treinamento\Services\Gestao\GestaoCursoComandos;

class CronogramaUpdateController extends Controller
{
    use EscopoDoCurso;

    public function __construct(private readonly GestaoCursoComandos $comandos)
    {
    }

    public function __invoke(CronogramaItemUpdateRequest $request, Treinamento $treinamento, CursoCronograma $item)
    {
        $this->garantirItemDoCurso($treinamento, $item);

        try {
            $this->comandos->atualizarItemDeCronograma($item, $request->validated(), $request->user());
        } catch (\DomainException $e) {
            return back()->withErrors(['cronograma' => $e->getMessage()]);
        }

        return back()->with('success', 'Item do cronograma atualizado.');
    }
}
