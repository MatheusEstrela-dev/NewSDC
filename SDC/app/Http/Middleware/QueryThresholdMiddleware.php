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
     * Chave em $request->attributes onde as queries da request corrente sao
     * acumuladas. Estado por-request (nao static, nao de instancia): sob
     * Octane/Swoole com enable_coroutine, requests podem intercalar no mesmo
     * worker e estado static misturaria queries de usuarios distintos.
     */
    private const REQUEST_ATTRIBUTE = 'query_threshold.queries';

    /**
     * O DB::listen e registrado uma unica vez por processo PHP; registrar a
     * cada request acumularia closures no worker persistente do Octane.
     */
    private static bool $listenerRegistered = false;

    public function handle(Request $request, Closure $next, int|string $threshold = 15): Response
    {
        $threshold = (int) $threshold;
        $request->attributes->set(self::REQUEST_ATTRIBUTE, []);

        if (!self::$listenerRegistered) {
            self::$listenerRegistered = true;

            DB::listen(static function (QueryExecuted $event): void {
                $current = app()->bound('request') ? app('request') : null;

                if (!$current instanceof Request) {
                    return;
                }

                $queries = $current->attributes->get(self::REQUEST_ATTRIBUTE);

                if (!is_array($queries)) {
                    return;
                }

                $queries[] = [
                    'sql' => $event->sql,
                    'time' => $event->time,
                    'bindings' => $event->bindings,
                ];

                $current->attributes->set(self::REQUEST_ATTRIBUTE, $queries);
            });
        }

        /** @var Response $response */
        $response = $next($request);

        /** @var array<int, array{sql: string, time: float, bindings: array<int, mixed>}> $queries */
        $queries = $request->attributes->get(self::REQUEST_ATTRIBUTE, []);
        $request->attributes->remove(self::REQUEST_ATTRIBUTE);

        $count = count($queries);

        if ($count > $threshold) {
            $totalTime = array_sum(array_column($queries, 'time'));

            $duplicadas = collect($queries)
                ->groupBy('sql')
                ->filter(fn ($group): bool => $group->count() > 1)
                ->map(fn ($group): int => $group->count())
                ->take(10)
                ->all();

            $topLentas = collect($queries)
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
