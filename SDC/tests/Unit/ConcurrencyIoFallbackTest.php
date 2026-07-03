<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Concurrency\Concurrency;
use Tests\TestCase;

class ConcurrencyIoFallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'octane.server' => 'roadrunner',
            'octane.swoole.options.hook_flags' => 0,
            'octane.swoole.options.task_worker_num' => 0,
        ]);
    }

    public function test_database_parallel_is_disabled_without_swoole_hooks_and_pool(): void
    {
        $this->assertFalse(Concurrency::databaseParallelAvailable());
    }

    public function test_tasks_preserve_keys_when_falling_back_to_sequential_execution(): void
    {
        $result = Concurrency::tasks([
            'first' => static fn (): int => 10,
            'second' => static fn (): int => 20,
        ]);

        $this->assertSame([
            'first' => 10,
            'second' => 20,
        ], $result);
    }
}
