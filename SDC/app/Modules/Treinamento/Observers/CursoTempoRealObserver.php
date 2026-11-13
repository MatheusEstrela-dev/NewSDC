<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Observers;

use App\Modules\Treinamento\Support\AvisoDeCursos;
use Illuminate\Database\Eloquent\Model;

/**
 * Curso ou inscricao gravados mudam o que as telas de curso mostram (selos,
 * contagem de alunos, situacao da inscricao). A mudanca de situacao do curso
 * nao passa por aqui: CicloDoCursoService atualiza por query e avisa direto.
 */
class CursoTempoRealObserver
{
    public function saved(Model $model): void
    {
        AvisoDeCursos::avisar();
    }

    public function deleted(Model $model): void
    {
        AvisoDeCursos::avisar();
    }
}
