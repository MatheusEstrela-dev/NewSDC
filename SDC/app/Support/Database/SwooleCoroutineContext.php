<?php

declare(strict_types=1);

namespace App\Support\Database;

use ArrayAccess;
use Swoole\Coroutine;

/**
 * CoroutineContext real: Coroutine::getContext() e Coroutine::defer().
 */
final class SwooleCoroutineContext implements CoroutineContext
{
    public function active(): bool
    {
        return extension_loaded('swoole')
            && class_exists(Coroutine::class)
            && Coroutine::getCid() > 0;
    }

    public function storage(): ArrayAccess
    {
        return Coroutine::getContext();
    }

    public function defer(callable $callback): void
    {
        Coroutine::defer($callback);
    }
}
