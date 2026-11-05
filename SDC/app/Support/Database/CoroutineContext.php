<?php

declare(strict_types=1);

namespace App\Support\Database;

use ArrayAccess;

/**
 * Costura entre o CoroutineDatabaseManager e o runtime de coroutines.
 *
 * O manager so precisa saber se esta dentro de uma coroutine, guardar estado
 * no contexto dela e agendar algo para o fim dela. Isolar isso atras de uma
 * interface deixa o manager testavel sem a extensao Swoole.
 */
interface CoroutineContext
{
    /**
     * Ha uma coroutine ativa onde o estado por coroutine faz sentido?
     */
    public function active(): bool;

    /**
     * Armazenamento da coroutine atual; morre junto com ela.
     *
     * @return ArrayAccess<string, mixed>
     */
    public function storage(): ArrayAccess;

    /**
     * Agenda a callable para quando a coroutine atual terminar.
     */
    public function defer(callable $callback): void;
}
