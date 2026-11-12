<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Models\User;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\CicloDoCursoService;

class CursoRetomarController extends CursoCicloController
{
    protected function executar(CicloDoCursoService $ciclo, Treinamento $treinamento, User $por): void
    {
        $ciclo->retomar($treinamento, $por);
    }

    protected function mensagem(): string
    {
        return 'Curso retomado.';
    }
}
