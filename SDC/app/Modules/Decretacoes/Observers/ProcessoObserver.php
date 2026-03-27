<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Observers;

use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Decretacoes\Services\ProcessoStatsService;

class ProcessoObserver
{
    public function __construct(
        private readonly ProcessoStatsService $statsService
    ) {
    }

    public function created(Processo $processo): void
    {
        $this->statsService->clearCache();
    }

    public function updated(Processo $processo): void
    {
        $this->statsService->clearCache();
    }

    public function deleted(Processo $processo): void
    {
        $this->statsService->clearCache();
    }

    public function restored(Processo $processo): void
    {
        $this->statsService->clearCache();
    }
}
