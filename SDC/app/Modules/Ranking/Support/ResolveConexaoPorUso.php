<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Resolve a conexao pelo NOME a cada uso.
 *
 * Com pool por coroutine, a Connection so vale para a coroutine que a obteve:
 * guardar o objeto em propriedade de singleton o compartilharia entre
 * requests simultaneos. O singleton guarda so o nome; quem usa chama
 * conexao() em cada operacao. A classe define `$nomeConexao`.
 */
trait ResolveConexaoPorUso
{
    private function conexao(): Connection
    {
        return DB::connection($this->nomeConexao);
    }
}
