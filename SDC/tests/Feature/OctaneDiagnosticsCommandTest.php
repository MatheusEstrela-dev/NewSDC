<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OctaneDiagnosticsCommandTest extends TestCase
{
    public function test_octane_diagnostics_reports_required_fields_as_json(): void
    {
        config([
            'octane.server' => 'swoole',
            'octane.swoole.options.hook_flags' => 0,
            'octane.swoole.options.task_worker_num' => 4,
            'octane.swoole.options.task_enable_coroutine' => false,
        ]);

        $exitCode = Artisan::call('octane:diagnostics', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertSame('swoole', $payload['octane']['server']);
        $this->assertSame(0, $payload['octane']['hook_flags_effective']);
        $this->assertSame(4, $payload['octane']['task_worker_num']);
        $this->assertFalse($payload['octane']['task_enable_coroutine']);
        $this->assertArrayHasKey('pools', $payload);
    }
}
