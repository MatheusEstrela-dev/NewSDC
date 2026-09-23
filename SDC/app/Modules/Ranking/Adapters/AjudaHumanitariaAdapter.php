<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Adapters;

use App\Core\Events\DomainEvent;
use App\Modules\Ranking\Adapters\Concerns\LeEvidenciaDoEvento;
use App\Modules\Ranking\Contracts\ModuleAdapter;
use App\Modules\Ranking\DTOs\FatoNormalizado;

final class AjudaHumanitariaAdapter implements ModuleAdapter
{
    use LeEvidenciaDoEvento;

    public function modulo(): string
    {
        return 'AjudaHumanitaria';
    }

    public function suporta(DomainEvent $evento): bool
    {
        return in_array($evento->eventName(), ['ajuda_humanitaria.pedido.enviado', 'ajuda_humanitaria.contas.homologadas'], true);
    }

    public function normalizar(DomainEvent $evento): ?FatoNormalizado
    {
        if (! $this->suporta($evento)) {
            return null;
        }

        $meta = $evento->metadata;
        $contas = $evento->eventName() === 'ajuda_humanitaria.contas.homologadas';
        $recurso = $contas ? 'prestacao' : 'pedido';
        $id = $this->id($meta[$recurso.'_id'] ?? null);
        if ($id === null) {
            return null;
        }

        $marco = $contas ? 'contas_aceitas' : 'pedido_completo';
        $data = $this->data($meta[$contas ? 'homologado_em' : 'enviado_em'] ?? null);
        $actor = $this->id($meta['actor_user_id'] ?? null);
        // A prestacao nao tem autor/enviador proprio. Criador do pedido e
        // homologador nao provam quem prestou contas.
        $creditado = $contas ? null : $actor;
        $validador = $contas ? $this->id($meta['validador_user_id'] ?? null) : null;

        return new FatoNormalizado(
            eventId: $evento->eventId,
            eventName: $evento->eventName(),
            modulo: $this->modulo(),
            chaveCanonica: 'ajuda_humanitaria:'.$recurso.':'.$id.':c1:'.$marco,
            familia: 'ajuda_humanitaria_'.$marco,
            ruleKey: 'ajuda_humanitaria.'.$marco,
            ocorridoEm: $evento->occurredAt,
            competenciaEm: $data ?? $evento->occurredAt,
            autoriaComprovada: $creditado !== null,
            evidenciaComprovada: $data !== null,
            validada: $contas && $data !== null && $validador !== null,
            actorUserId: $actor,
            creditedUserId: $creditado,
            validadorUserId: $validador,
            entregueEm: $data,
            contexto: [$recurso.'_id' => $id, 'lacuna_autoria' => $contas],
        );
    }
}
