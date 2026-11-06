<?php

declare(strict_types=1);

namespace App\Database;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swoole\Coroutine;

/**
 * Guarda de budget de queries por request. Sinaliza N+1 cedo via DB::listen.
 *
 * - Acima de warn_at: log warning com ultima SQL (suspeita).
 * - Acima de fail_at: log error (confirmado).
 * - isentar(): silencia o request corrente, para varredura em chunk legitima.
 *
 * Reset por request/task via Octane RequestReceived/TaskReceived (em AppServiceProvider).
 */
class QueryBudgetGuard
{
    private const CONTEXT_KEY = '__sdc_query_budget_guard';

    private const EMPTY_STATE = ['count' => 0, 'isento' => false];

    /** @var array{count: int, isento: bool} */
    private array $fallbackState = self::EMPTY_STATE;

    private bool $bound = false;

    public function __construct(
        private int $warnAt = 30,
        private int $failAt = 100,
    ) {}

    /**
     * Isenta o request corrente dos avisos, sem parar de contar.
     *
     * Existe para varredura em chunk deliberada, onde o numero de queries cresce
     * com o tamanho do dado e nao com o codigo: o export de cisternas faz
     * ceil(N/1000) * 4 queries -- 36 para as 8.199 linhas de hoje, ja acima do
     * warn_at de 30, e 400 se a base chegar a 100 mil. Nao ha N+1 nenhum ali.
     *
     * A alternativa seria subir o teto global, e o teto global e exatamente o que
     * pega N+1 de verdade nas outras rotas. Aviso falso em toda execucao custa
     * mais que isso: ensina a equipe a ignorar o alarme.
     *
     * Vale so para a execucao corrente; os eventos Octane limpam a isencao.
     */
    public function isentar(): void
    {
        $state = $this->state();
        $state['isento'] = true;
        $this->setState($state);
    }

    public function bind(): void
    {
        if ($this->bound) {
            return;
        }
        $this->bound = true;

        DB::listen(function (QueryExecuted $event): void {
            // Conta sempre, mesmo isento: count() continua sendo o numero real de
            // queries do request. So o aviso e que fica em silencio.
            $state = $this->state();
            $state['count']++;
            $this->setState($state);

            if ($state['isento']) {
                return;
            }

            if ($state['count'] === $this->warnAt + 1) {
                Log::warning('Query budget warning crossed', [
                    'count' => $state['count'],
                    'last_sql' => $event->sql,
                ]);
            }

            if ($state['count'] === $this->failAt + 1) {
                Log::error('Query budget exceeded - possivel N+1', [
                    'count' => $state['count'],
                    'last_sql' => $event->sql,
                ]);
            }
        });
    }

    public function reset(): void
    {
        $this->setState(self::EMPTY_STATE);
    }

    public function count(): int
    {
        return $this->state()['count'];
    }

    /** @return array{count: int, isento: bool} */
    private function state(): array
    {
        if ($this->inCoroutine()) {
            return Coroutine::getContext()[self::CONTEXT_KEY] ?? self::EMPTY_STATE;
        }

        return $this->fallbackState;
    }

    /** @param array{count: int, isento: bool} $state */
    private function setState(array $state): void
    {
        if ($this->inCoroutine()) {
            Coroutine::getContext()[self::CONTEXT_KEY] = $state;

            return;
        }

        $this->fallbackState = $state;
    }

    private function inCoroutine(): bool
    {
        return extension_loaded('swoole') && Coroutine::getCid() > 0;
    }
}
