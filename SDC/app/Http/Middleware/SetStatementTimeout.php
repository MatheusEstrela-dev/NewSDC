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
        DB::statement("SET LOCAL statement_timeout = {$timeoutMs}");
        DB::statement('SET LOCAL idle_in_transaction_session_timeout = 60000');

        return $next($request);
    }
}
