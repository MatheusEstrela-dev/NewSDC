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
    ) {
        if ($this->abertaEm->greaterThan($this->resolvidaEm)) {
            throw new \DomainException('A data de abertura não pode ser posterior ao fechamento.');
        }
        if ($this->resolvidaEm->greaterThan(CarbonImmutable::now())) {
            throw new \DomainException('A data de fechamento não pode estar no futuro.');
        }
    }

    public static function fromRequest(ResolverDemandaRequest $request): self
    {
        $data = $request->validated();

        return new self(
            CarbonImmutable::parse($data['aberta_em']),
            CarbonImmutable::parse($data['resolvida_em']),
        );
    }
}
