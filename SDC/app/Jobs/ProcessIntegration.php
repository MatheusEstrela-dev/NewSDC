<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use App\Services\Integration\IntegrationHubService;

class ProcessIntegration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public array $backoff = [15, 45, 120];

    public function __construct(
        public string $integrationId,
        public array $config,
        public ?int $userId = null
    ) {}

    public function handle(IntegrationHubService $integrationHub): void
    {
        try {
            // Atualiza status para "processing"
            Cache::put("integration:{$this->integrationId}", array_merge(
                Cache::get("integration:{$this->integrationId}"),
                ['status' => 'processing']
            ), 3600);

            // Executa integração
            $result = $integrationHub->executeSync($this->config, $this->userId);

            // Atualiza cache com resultado
            Cache::put("integration:{$this->integrationId}", [
                'config' => $this->config,
                'user_id' => $this->userId,
                'status' => 'completed',
                'result' => $result,
                'created_at' => Cache::get("integration:{$this->integrationId}")['created_at'],
                'completed_at' => now(),
            ], 3600);

            // Se tem callback_url, envia resultado
            if (isset($this->config['callback_url'])) {
                \Http::post($this->config['callback_url'], [
                    'integration_id' => $this->integrationId,
                    'status' => 'completed',
                    'result' => $result,
                ]);
            }

        } catch (\Exception $e) {
            Cache::put("integration:{$this->integrationId}", [
                'config' => $this->config,
                'user_id' => $this->userId,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'created_at' => Cache::get("integration:{$this->integrationId}")['created_at'],
                'failed_at' => now(),
            ], 3600);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        \Log::critical('Integration permanently failed', [
            'integration_id' => $this->integrationId,
            'error' => $exception->getMessage(),
            'user_id' => $this->userId,
        ]);
    }
}
