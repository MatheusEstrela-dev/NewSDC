<?php

declare(strict_types=1);

namespace App\Support\Redis;

use Illuminate\Redis\Connections\PhpRedisConnection;
use RedisException;

final class PooledPhpRedisConnection extends PhpRedisConnection
{
    private bool $discardOnRelease = false;

    public function command($method, array $parameters = [])
    {
        try {
            return parent::command($method, $parameters);
        } catch (RedisException $e) {
            $this->discardOnRelease = true;

            throw $e;
        }
    }

    public function shouldDiscard(): bool
    {
        return $this->discardOnRelease;
    }
}
