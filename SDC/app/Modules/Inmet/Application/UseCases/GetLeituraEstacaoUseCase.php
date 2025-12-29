<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Application\UseCases;

use App\Modules\Inmet\Infrastructure\ExternalServices\InmetApiClient;
use App\Modules\Inmet\Application\DTOs\LeituraMeteorologicaDTO;

class GetLeituraEstacaoUseCase
{
    public function __construct(
        private readonly InmetApiClient $inmetClient
    ) {
    }

    public function execute(string $codigoEstacao): ?LeituraMeteorologicaDTO
    {
        $data = $this->inmetClient->getLeituraEstacao($codigoEstacao);

        if (!$data) {
            return null;
        }

        return LeituraMeteorologicaDTO::fromInmetArray($data);
    }
}
