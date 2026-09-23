<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Domain\Events;

use App\Core\Events\DomainEvent;

final readonly class PlanoEnviadoV1 extends DomainEvent
{
    public function eventName(): string { return 'pmda.plano.enviado'; }
    public function eventVersion(): int { return 1; }
    public function payload(): array { return ['plano_id' => $this->aggregateId]; }
}
