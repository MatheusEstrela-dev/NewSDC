<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\Request;

/** Le per_page da query com piso 1 e teto fixo (evita paginas gigantes). */
trait LimitaPorPagina
{
    protected const PER_PAGE_MAXIMO = 100;

    protected function porPagina(Request $request, int $padrao = 15): int
    {
        return max(1, min($request->integer('per_page', $padrao), self::PER_PAGE_MAXIMO));
    }
}
