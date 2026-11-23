<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Observers;

use App\Modules\Treinamento\Support\AvisoDeCursos;
use Illuminate\Database\Eloquent\Model;

/**
 * Avaliacao do curso, certificado, catalogo de cursos e cronograma entram nas
 * contas do painel de indicadores, mas nao mudam o que as telas de curso
 * mostram: avisam so o painel. Inscricao e turma ficam em CursoTempoRealObserver.
 */
class IndicadoresTempoRealObserver
{
    public function saved(Model $model): void
    {
        // save() sem mudanca nao altera numero nenhum: nao vale um aviso.
        if ($model->wasRecentlyCreated || $model->wasChanged()) {
            AvisoDeCursos::avisarIndicadores();
        }
    }

    public function deleted(Model $model): void
    {
        AvisoDeCursos::avisarIndicadores();
    }
}
