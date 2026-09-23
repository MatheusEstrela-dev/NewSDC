<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Adapters;

use App\Core\Events\DomainEvent;
use App\Modules\Ranking\Contracts\ModuleAdapter;
use App\Modules\Ranking\DTOs\FatoNormalizado;

final class PmdaAdapter implements ModuleAdapter
{
    public function modulo(): string { return 'Pmda'; }
    public function ruleKeys(): array { return ['pmda.plano_enviado']; }
    public function suporta(DomainEvent $evento): bool { return $evento->eventName() === 'pmda.plano.enviado'; }

    public function normalizar(DomainEvent $evento): ?FatoNormalizado
    {
        if (! $this->suporta($evento)) {
            return null;
        }
        $autor = filter_var($evento->metadata['actor_user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;

        return new FatoNormalizado(
            eventId: $evento->eventId, eventName: $evento->eventName(), modulo: 'Pmda',
            chaveCanonica: 'pmda:plano:'.$evento->aggregateId.':enviado', familia: 'pmda_plano_enviado',
            ruleKey: 'pmda.plano_enviado', ocorridoEm: $evento->occurredAt, competenciaEm: $evento->occurredAt,
            autoriaComprovada: $autor !== null, evidenciaComprovada: ($evento->metadata['envio_comprovado'] ?? false) === true,
            validada: false, actorUserId: $autor, creditedUserId: $autor, entregueEm: $evento->occurredAt,
            contexto: ['plano_id' => $evento->aggregateId, 'fonte' => 'pmda.plano.enviado'],
        );
    }
}
