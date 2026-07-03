<?php

declare(strict_types=1);

namespace App\Support\Security;

use App\Support\Concurrency\Concurrency;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PasswordVerifier
{
    public function verify(string $plain, string $hash): bool
    {
        if (! $this->taskWorkersAvailable()) {
            return $this->verifySynchronously($plain, $hash);
        }

        try {
            return $this->taskResultToBool(
                $this->runTaskWorkerVerification($plain, $hash)
            );
        } catch (\Throwable $e) {
            Log::warning('PasswordVerifier: task worker indisponivel; fallback sincrono.', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return $this->verifySynchronously($plain, $hash);
        }
    }

    /**
     * Retorna 1/0 em vez de bool para nao colidir com o contrato atual de
     * Concurrency::tasks(), onde false significa "task nao concluida".
     */
    protected function runTaskWorkerVerification(string $plain, string $hash): int
    {
        $tasks = [
            'password' => static function () use ($plain, $hash): int {
                return Hash::check($plain, $hash) ? 1 : 0;
            },
        ];

        $result = Concurrency::tasks($tasks, (int) config('octane.tasks.wait_ms', 5000));

        return (int) ($result['password'] ?? 0);
    }

    protected function taskWorkersAvailable(): bool
    {
        return Concurrency::taskWorkersAvailable();
    }

    protected function verifySynchronously(string $plain, string $hash): bool
    {
        return Hash::check($plain, $hash);
    }

    private function taskResultToBool(int $result): bool
    {
        return $result === 1;
    }
}
