<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Gestao\Concerns;

use App\Modules\Treinamento\Models\CursoCronograma;
use App\Modules\Treinamento\Models\Treinamento;

/**
 * IDOR: a rota manda {treinamento} e {item} como bindings
 * independentes - nada impede quem tem `treinamento.cursos.configurar` (slug
 * geral, nao por curso) de trocar o id do item na URL por um de outro
 * curso. Mesmo check de App\Modules\Treinamento\Services\ConteudoCursoService::garantirDoCurso()
 * (modulo EAD), aqui do lado do gestor.
 */
trait EscopoDoCurso
{
    private function garantirItemDoCurso(Treinamento $treinamento, CursoCronograma $item): void
    {
        abort_unless((int) $item->treinamento_id === (int) $treinamento->id, 404);
    }
}
