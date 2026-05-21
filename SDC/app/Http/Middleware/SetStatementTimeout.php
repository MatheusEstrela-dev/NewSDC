<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetStatementTimeout
{
    public function handle(Request $request, Closure $next, int $timeoutMs = 10000): Response
    {
        // SET LOCAL so funciona dentro de transacao explicita; fora dela
        // o Postgres trata como SET (escopo de sessao). Em Octane com
        // ATTR_PERSISTENT=true o valor vaza para a proxima request no
        // mesmo worker. Usamos SET + restore em finally para garantir
        // isolamento entre requests.
        DB::statement("SET statement_timeout = {$timeoutMs}");
        DB::statement('SET idle_in_transaction_session_timeout = 60000');

        try {
            return $next($request);
        } finally {
            DB::statement('SET statement_timeout = DEFAULT');
            DB::statement('SET idle_in_transaction_session_timeout = DEFAULT');
        }
    }
}
