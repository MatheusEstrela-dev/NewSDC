<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Captura todas as queries SQL durante a request e dispara warning
 * quando o total ultrapassa o threshold configurado (default: 15).
 *
 * Uso em rotas:
 *   Route::middleware('compdec.query-threshold:15')->group(...);
 *
 * Quando excedido:
 *   - Loga warning no canal "compdec-perf" (top 5 queries lentas + duplicadas)
 *   - Em desenvolvimento, adiciona X-Query-Count e X-Query-Threshold-Exceeded ao response
 */
class QueryThresholdMiddleware
{
    /**
     * Estado static para sobreviver entre instances do middleware (que sao
     * criadas por request) sem acumular DB::listen closures em Octane.
     * Cada request reset $currentQueries; o listener eh registrado 1x por
     * processo PHP. Em Octane, cada worker processa requests sequencialmente,
     * entao o estado static eh seguro (1 request ativa por worker).
     *
     * @var array<int, array{sql: string, time: float, bindings: array<int, mixed>}>
     */
    private static array $currentQueries = [];
    private static bool $listenerRegistered = false;

    public function handle(Request $request, Closure $next, int|string $threshold = 15): Response
    {
        $threshold = (int) $threshold;
        self::$currentQueries = [];

        if (!self::$listenerRegistered) {
            self::$listenerRegistered = true;
            DB::listen(function (QueryExecuted $event): void {
                self::$currentQueries[] = [
                    'sql' => $event->sql,
                    'time' => $event->time,
                    'bindings' => $event->bindings,
                ];
            });
        }

        /** @var Response $response */
        $response = $next($request);

        $count = count(self::$currentQueries);

        if ($count > $threshold) {
            $totalTime = array_sum(array_column($this->queries, 'time'));

            $duplicadas = collect($this->queries)
                ->groupBy('sql')
                ->filter(fn ($group): bool => $group->count() > 1)
                ->map(fn ($group): int => $group->count())
                ->take(10)
                ->all();

            $topLentas = collect($this->queries)
                ->sortByDesc('time')
                ->take(5)
                ->map(fn (array $q): array => [
                    'sql' => mb_substr($q['sql'], 0, 200),
                    'time_ms' => $q['time'],
                ])
                ->values()
                ->all();

            Log::channel('compdec-perf')->warning('Query threshold exceeded', [
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'uri' => $request->path(),
                'count' => $count,
                'threshold' => $threshold,
                'total_time_ms' => round($totalTime, 2),
                'user_id' => $request->user()?->id,
                'top_lentas' => $topLentas,
                'duplicadas' => $duplicadas,
            ]);

            if (! app()->isProduction()) {
                $response->headers->set('X-Query-Count', (string) $count);
                $response->headers->set('X-Query-Threshold-Exceeded', 'true');
            }
        }

        return $response;
    }
}
