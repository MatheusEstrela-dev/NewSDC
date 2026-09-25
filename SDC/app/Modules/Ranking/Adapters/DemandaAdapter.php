<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Adapters;

use App\Core\Events\DomainEvent;
use App\Modules\Demandas\Domain\Events\DemandaResolvidaV1;
use App\Modules\Ranking\Contracts\ModuleAdapter;
use App\Modules\Ranking\DTOs\FatoNormalizado;

/**
 * Demanda resolvida = entrega aceita, para quem resolveu. Mover card, comentar e
 * transferir valem zero e nem chegam aqui. Reabrir e resolver de novo produz a
 * mesma chave canonica, entao o livro segura o premio duplo.
 */
final class DemandaAdapter implements ModuleAdapter
{
    public function modulo(): string { return 'Demandas'; }

    public function ruleKeys(): array { return ['demandas.entrega_aceita']; }

    public function suporta(DomainEvent $evento): bool
    {
        return $evento instanceof DemandaResolvidaV1;
    }

    public function normalizar(DomainEvent $evento): ?FatoNormalizado
    {
        if (! $evento instanceof DemandaResolvidaV1) {
            return null;
        }
        $autor = $evento->resolvidoPorId > 0 ? $evento->resolvidoPorId : null;

        return new FatoNormalizado(
            eventId: $evento->eventId, eventName: $evento->eventName(), modulo: 'Demandas',
            chaveCanonica: 'demanda:'.$evento->aggregateId.':entrega_aceita', familia: 'demandas_entrega_aceita',
            ruleKey: 'demandas.entrega_aceita', ocorridoEm: $evento->occurredAt, competenciaEm: $evento->occurredAt,
            autoriaComprovada: $autor !== null, evidenciaComprovada: true, validada: false,
            actorUserId: $autor, creditedUserId: $autor, entregueEm: $evento->occurredAt,
            contexto: ['demanda_id' => $evento->aggregateId, 'fonte' => 'demanda.resolvida'],
        );
    }
}
