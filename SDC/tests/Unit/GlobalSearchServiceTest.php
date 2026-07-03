<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\GlobalSearchService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GlobalSearchServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'octane.server' => 'roadrunner',
            'octane.swoole.options.hook_flags' => 0,
        ]);

        Cache::flush();
    }

    public function test_search_runs_independent_queries_through_fallback_and_preserves_shape(): void
    {
        DB::shouldReceive('select')
            ->times(4)
            ->andReturn([]);

        $result = app(GlobalSearchService::class)->search('#ABC-123');

        $this->assertSame([
            'pae' => [],
            'decretacoes' => [],
            'rat' => [],
            'demandas' => [],
        ], $result);
    }

    public function test_search_short_queries_do_not_touch_database(): void
    {
        DB::shouldReceive('select')->never();

        $result = app(GlobalSearchService::class)->search('a');

        $this->assertSame([
            'pae' => [],
            'decretacoes' => [],
            'rat' => [],
            'demandas' => [],
        ], $result);
    }
}
