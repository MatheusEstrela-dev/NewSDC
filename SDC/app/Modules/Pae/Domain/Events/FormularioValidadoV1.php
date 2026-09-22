<?php

declare(strict_types=1);

namespace App\Modules\Pae\Domain\Events;

use App\Core\Events\DomainEvent;

/**
 * Ficha PAE finalizada com o protocolo ja delegado a um analista.
 *
 * TRES PAPEIS DISTINTOS, e a separacao e o ponto do evento:
 *  - actor_user_id     quem operou a finalizacao (o $user do request);
 *  - credited_user_id  o AUTOR da ficha (pae_forms.created_by) -- e quem
 *                      recebe o ponto;
 *  - validador_user_id o analista do protocolo (pae_protocolos.analista_atual_id),
 *                      sem cuja delegacao a ficha nao pode sequer ser editada
 *                      (PaeFormularioService::assertAbasLiberadas).
 *
 * O validador NAO herda o premio. Quando o autor nao e reconstruivel, o evento
 * sai sem credited_user_id e o fato vai para apuracao -- jamais se promove o
 * validador a creditado.
 *
 * `entregue_em` sai nulo de proposito: pae_forms nao tem coluna de data de
 * finalizacao/validacao, e updated_at nao e prova de entrega.
 */
final readonly class FormularioValidadoV1 extends DomainEvent
{
    public function eventName(): string
    {
        return 'pae.formulario.validado';
    }

    public function eventVersion(): int
    {
        return 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'formulario_id'     => $this->metadata['formulario_id']     ?? null,
            'protocolo_id'      => $this->metadata['protocolo_id']      ?? null,
            'ciclo'             => $this->metadata['ciclo']             ?? null,
            'actor_user_id'     => $this->metadata['actor_user_id']     ?? null,
            'credited_user_id'  => $this->metadata['credited_user_id']  ?? null,
            'validador_user_id' => $this->metadata['validador_user_id'] ?? null,
            'prazo_em'          => $this->metadata['prazo_em']          ?? null,
            'entregue_em'       => $this->metadata['entregue_em']       ?? null,
        ];
    }
}
