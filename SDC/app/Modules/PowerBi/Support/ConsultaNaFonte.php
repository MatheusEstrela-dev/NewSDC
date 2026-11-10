<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Support;

use App\Support\Database\ConexaoPowerBi;
use Illuminate\Database\Query\Builder;

/**
 * Os services de export montam consultas com DB::table (conexao default).
 * O materializador precisa ler do membro de BI: redireciona o builder.
 */
final class ConsultaNaFonte
{
    public static function redirecionar(Builder $consulta): Builder
    {
        $fonte = ConexaoPowerBi::fonte();
        $consulta->connection = $fonte;
        $consulta->grammar = $fonte->getQueryGrammar();
        $consulta->processor = $fonte->getPostProcessor();

        return $consulta;
    }
}
