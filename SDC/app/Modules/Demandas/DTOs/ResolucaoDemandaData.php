<?php

declare(strict_types=1);

namespace App\Modules\Demandas\DTOs;

use App\Modules\Demandas\Requests\ResolverDemandaRequest;
use Carbon\CarbonImmutable;

final readonly class ResolucaoDemandaData
{
    public function __construct(
        public CarbonImmutable $abertaEm,
        public CarbonImmutable $resolvidaEm,
    ) {}

    public static function fromRequest(ResolverDemandaRequest $request): self
    {
        $data = $request->validated();

        return new self(
            CarbonImmutable::parse($data['aberta_em']),
            CarbonImmutable::parse($data['resolvida_em']),
        );
    }
}
