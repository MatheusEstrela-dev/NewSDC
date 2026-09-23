<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Adapters;

use App\Core\Events\DomainEvent;
use App\Modules\Ranking\Adapters\Concerns\LeEvidenciaDoEvento;
use App\Modules\Ranking\Contracts\ModuleAdapter;
use App\Modules\Ranking\DTOs\FatoNormalizado;

final class TdapAdapter implements ModuleAdapter
{
    use LeEvidenciaDoEvento;

    public function modulo(): string
    {
        return 'Tdap';
    }

    public function suporta(DomainEvent $evento): bool
    {
        return in_array($evento->eventName(), ['tdap.cronograma.ativado', 'tdap.viagem.validada'], true);
    }

    public function normalizar(DomainEvent $evento): ?FatoNormalizado
    {
        if (! $this->suporta($evento)) {
            return null;
        }

        $meta = $evento->metadata;
        $viagem = $evento->eventName() === 'tdap.viagem.validada';
        $recurso = $viagem ? 'viagem' : 'cronograma';
        $id = $this->id($meta[$recurso.'_id'] ?? null);
        if ($id === null) {
            return null;
        }

        $marco = $viagem ? 'viagem_validada' : 'cronograma_ativado';
        $data = $this->data($meta[$viagem ? 'validada_em' : 'ativado_em'] ?? null);
        $actor = $this->id($meta['actor_user_id'] ?? null);
        // A origem nao registra autor da viagem. Nem validador nem confirmador
        // municipal podem ser promovidos a autor por fallback.
        $creditado = $viagem ? null : $actor;
        $validador = $viagem ? $this->id($meta['validador_user_id'] ?? null) : null;

        return new FatoNormalizado(
            eventId: $evento->eventId,
            eventName: $evento->eventName(),
            modulo: $this->modulo(),
            chaveCanonica: 'tdap:'.$recurso.':'.$id.':c1:'.$marco,
            familia: 'tdap_'.$marco,
            ruleKey: 'tdap.'.$marco,
            ocorridoEm: $evento->occurredAt,
            competenciaEm: $data ?? $evento->occurredAt,
            autoriaComprovada: $creditado !== null,
            evidenciaComprovada: $data !== null,
            validada: $viagem && $validador !== null && $data !== null,
            actorUserId: $actor,
            creditedUserId: $creditado,
            validadorUserId: $validador,
            entregueEm: $data,
            contexto: [$recurso.'_id' => $id, 'lacuna_autoria' => $viagem],
        );
    }
}
