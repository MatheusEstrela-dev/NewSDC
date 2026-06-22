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
        // Gate (config/resilience.db):
        //  - pooler_mode='transaction': SET de sessao fora de transacao VAZA no
        //    pool do PgBouncer -> no-op (confia no statement_timeout default da
        //    conexao/servidor).
        //  - statement_timeout_per_route=false: no-op por performance (cada
        //    request fazia ~4 round-trips DB so pra SET/reset; sob Azure isso
        //    dominava a latencia). Confia no default da conexao/servidor.
        // Flag permite A/B sem rebuild (so restart): medir per-rota vs default.
        if (config('resilience.db.pooler_mode') === 'transaction'
            || ! config('resilience.db.statement_timeout_per_route', true)) {
            return $next($request);
        }

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
