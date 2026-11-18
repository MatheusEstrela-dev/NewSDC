<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Gestao;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Controllers\Gestao\Concerns\EscopoDoCurso;
use App\Modules\Treinamento\Models\CursoCronograma;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\Gestao\GestaoCursoComandos;
use Illuminate\Http\Request;

class CronogramaDestroyController extends Controller
{
    use EscopoDoCurso;

    public function __construct(private readonly GestaoCursoComandos $comandos)
    {
    }

    public function __invoke(Request $request, Treinamento $treinamento, CursoCronograma $item)
    {
        $this->garantirItemDoCurso($treinamento, $item);

        $this->comandos->removerItemDeCronograma($item, $request->user());

        return back()->with('success', 'Item removido do cronograma.');
    }
}
