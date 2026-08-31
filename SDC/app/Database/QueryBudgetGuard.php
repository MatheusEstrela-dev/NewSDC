<?php

declare(strict_types=1);

namespace App\Database;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Guarda de budget de queries por request. Sinaliza N+1 cedo via DB::listen.
 *
 * - Acima de warn_at: log warning com ultima SQL (suspeita).
 * - Acima de fail_at: log error (confirmado).
 * - isentar(): silencia o request corrente, para varredura em chunk legitima.
 *
 * Reset por request via Octane RequestReceived listener (em AppServiceProvider).
 */
class QueryBudgetGuard
{
    private int $count = 0;
    private bool $bound = false;
    private bool $isento = false;

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
     * Vale so para este request -- reset() em RequestReceived limpa a isencao.
     */
    public function isentar(): void
    {
        $this->isento = true;
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
            $this->count++;

            if ($this->isento) {
                return;
            }

            if ($this->count === $this->warnAt + 1) {
                Log::warning('Query budget warning crossed', [
                    'count' => $this->count,
                    'last_sql' => $event->sql,
                ]);
            }

            if ($this->count === $this->failAt + 1) {
                Log::error('Query budget exceeded - possivel N+1', [
                    'count' => $this->count,
                    'last_sql' => $event->sql,
                ]);
            }
        });
    }

    public function reset(): void
    {
        $this->count = 0;
        // A isencao e do request, nao do worker: sem zerar aqui, um export
        // silenciaria o guard para todos os requests seguintes daquele worker
        // Octane -- e o N+1 real de outra rota passaria sem aviso.
        $this->isento = false;
    }

    public function count(): int
    {
        return $this->count;
    }
}
