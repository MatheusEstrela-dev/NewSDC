<?php

declare(strict_types=1);

namespace App\Modules\Demandas\DTOs;

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

    /** @param array<string, mixed> $data ResolverDemandaRequest::validated() */
    public static function fromValidated(array $data): self
    {

        return new self(
            CarbonImmutable::parse($data['aberta_em']),
            CarbonImmutable::parse($data['resolvida_em']),
        );
    }
}
