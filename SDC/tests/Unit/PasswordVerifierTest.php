<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Security\PasswordVerifier;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class PasswordVerifierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'hashing.driver' => 'bcrypt',
            'hashing.bcrypt.rounds' => 4,
            'octane.server' => 'roadrunner',
        ]);
    }

    public function test_verify_uses_synchronous_fallback_outside_swoole_task_workers(): void
    {
        $hash = Hash::make('senha-correta');
        $verifier = new PasswordVerifier();

        $this->assertTrue($verifier->verify('senha-correta', $hash));
        $this->assertFalse($verifier->verify('senha-errada', $hash));
    }

    public function test_task_worker_result_zero_maps_to_invalid_password_without_false_payload(): void
    {
        $verifier = new class extends PasswordVerifier {
            protected function taskWorkersAvailable(): bool
            {
                return true;
            }

            protected function runTaskWorkerVerification(string $plain, string $hash): int
            {
                return 0;
            }

            protected function verifySynchronously(string $plain, string $hash): bool
            {
                throw new RuntimeException('sync fallback should not be used');
            }
        };

        $this->assertFalse($verifier->verify('senha-errada', Hash::make('senha-correta')));
    }

    public function test_task_worker_failure_falls_back_to_synchronous_verification(): void
    {
        $hash = Hash::make('senha-correta');

        $verifier = new class extends PasswordVerifier {
            protected function taskWorkersAvailable(): bool
            {
                return true;
            }

            protected function runTaskWorkerVerification(string $plain, string $hash): int
            {
                throw new RuntimeException('task timeout');
            }
        };

        $this->assertTrue($verifier->verify('senha-correta', $hash));
        $this->assertFalse($verifier->verify('senha-errada', $hash));
    }
}
