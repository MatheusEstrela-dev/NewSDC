<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Database\ConnectionSemaphore;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que adquire um slot do ConnectionSemaphore antes de a request
 * tocar no DB. Se o semaforo estiver saturado, retorna 503 com Retry-After,
 * funcionando como backpressure de primeira linha — antes do PgBouncer ou
 * de qualquer infra externa.
 */
class AcquireConnectionSlot
{
    public function __construct(private ConnectionSemaphore $semaphore) {}

    public function handle(Request $request, Closure $next): Response
    {
        $owner = $request->attributes->get('slot_owner') ?? (string) Str::uuid();
        $request->attributes->set('slot_owner', $owner);

        if (!$this->semaphore->acquire($owner)) {
            return response()->json([
                'error' => 'Service Busy',
                'message' => 'Banco em alta carga; tente novamente em instantes.',
            ], 503, ['Retry-After' => '1']);
        }

        try {
            return $next($request);
        } finally {
            $this->semaphore->release($owner);
        }
    }
}
