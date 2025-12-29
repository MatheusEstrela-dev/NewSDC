<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Application\UseCases;

use App\Modules\Inmet\Infrastructure\ExternalServices\InmetApiClient;
use App\Modules\Inmet\Application\DTOs\LeituraMeteorologicaDTO;
use Illuminate\Support\Collection;

class GetLeiturasAtuaisUseCase
{
    public function __construct(
        private readonly InmetApiClient $inmetClient
    ) {
    }

    public function execute(string $uf = 'MG'): Collection
    {
        $leituras = $this->inmetClient->getLeiturasRecentes($uf);

        if (empty($leituras)) {
            return collect([]);
        }

        return collect($leituras)
            ->map(fn($data) => LeituraMeteorologicaDTO::fromInmetArray($data))
            ->sortByDesc('precipitacao')
            ->values();
    }
}
