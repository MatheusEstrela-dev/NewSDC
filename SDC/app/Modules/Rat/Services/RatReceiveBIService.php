<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\DTOs\RatReceiveBIDTO;
use App\Modules\Rat\Models\RatOcorrencia;

class RatReceiveBIService
{
    public function __construct(
        private readonly RatWriteService $writeService,
    ) {}

    public function receive(RatReceiveBIDTO $dto): RatOcorrencia
    {
        return $this->writeService->createWithData($dto->toArray());
    }
}
