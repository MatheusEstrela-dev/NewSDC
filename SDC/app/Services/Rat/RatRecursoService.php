<?php

declare(strict_types=1);

namespace App\Services\Rat;

use App\Models\Rat\Relatos\RatRelatoRecurso;
use App\Models\Rat\Recursos\RatRecursosEmpregado;
use App\Models\Rat\Recursos\RatRecursosComponentesGuarnicao;
use Illuminate\Support\Facades\DB;

/**
 * Gerenciamento de recursos em ocorrências RAT.
 *
 * Métodos públicos:
 *   createRecurso()         — cria um relato de recurso
 *   addEmpregado()          — adiciona recurso empregado ao relato
 *   addComponenteGuarnicao() — adiciona componente à guarnição
 *   removeEmpregado()       — remove recurso empregado e seus componentes
 */
class RatRecursoService
{
    /** Cria um relato de recurso (entrada no polimorfismo). */
    public function createRecurso(array $data): RatRelatoRecurso
    {
        return RatRelatoRecurso::create($data);
    }

    /** Adiciona um recurso empregado a um relato de recurso. */
    public function addEmpregado(int $relatoRecursoId, array $data): RatRecursosEmpregado
    {
        return RatRecursosEmpregado::create(array_merge($data, [
            'relato_recurso_id' => $relatoRecursoId,
            'created_by'        => auth()->id(),
        ]));
    }

    /** Adiciona um componente à guarnição de um recurso empregado. */
    public function addComponenteGuarnicao(int $recursoEmpregadoId, array $data): RatRecursosComponentesGuarnicao
    {
        return RatRecursosComponentesGuarnicao::create(array_merge($data, [
            'recurso_empregado_id' => $recursoEmpregadoId,
            'created_by'           => auth()->id(),
        ]));
    }

    /** Remove recurso empregado e seus componentes de guarnição em transação. */
    public function removeEmpregado(int $empregadoId): void
    {
        DB::transaction(function () use ($empregadoId) {
            RatRecursosComponentesGuarnicao::where('recurso_empregado_id', $empregadoId)->delete();
            RatRecursosEmpregado::where('id', $empregadoId)->delete();
        });
    }
}
