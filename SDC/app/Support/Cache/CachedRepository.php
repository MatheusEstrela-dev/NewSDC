<?php

declare(strict_types=1);

namespace App\Support\Cache;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Decorator generico de read-through cache com invalidacao por tag.
 * Reduz pressao de pool em endpoints de leitura intensiva.
 *
 * Uso:
 *   $cache = new CachedRepository('orgaos', ttlSeconds: 3600);
 *   $orgaos = $cache->remember('all', fn() => Orgao::orderBy('nome')->get());
 *
 *   // Em writes:
 *   $cache->flush();
 */
class CachedRepository
{
    public function __construct(
        private string $tag,
        private int $ttlSeconds = 300,
    ) {}

    public function remember(string $key, Closure $producer): mixed
    {
        return Cache::tags([$this->tag])->remember(
            $this->tag.':'.$key,
            $this->ttlSeconds,
            $producer
        );
    }

    public function flush(): void
    {
        Cache::tags([$this->tag])->flush();
    }
}
