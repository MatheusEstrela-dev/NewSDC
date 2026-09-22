<?php

declare(strict_types=1);

namespace App\Modules\Pae\Domain\Events;

use App\Core\Events\DomainEvent;

/**
 * Protocolo PAE registrado/enviado (entrada no fluxo).
 *
 * Emitido por PaeProtocoloService::create e pelo ramo de
 * PaeFormularioService::finalizar que gera o protocolo a partir da ficha.
 *
 * Executor e creditado sao a MESMA pessoa neste marco: quem registra o
 * protocolo e quem responde por ele (created_by / user_id). O analista
 * (analista_atual_id) ainda nao existe aqui e nao entra como creditado.
 *
 * `entregue_em` vem de pae_protocolos.dt_entrada -- coluna real de entrada.
 * `updated_at` nunca e usado como prova de entrega.
 */
final readonly class ProtocoloEnviadoV1 extends DomainEvent
{
    public function eventName(): string
    {
        return 'pae.protocolo.enviado';
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
            'protocolo_id'      => $this->metadata['protocolo_id']      ?? null,
            'num_protocolo'     => $this->metadata['num_protocolo']     ?? null,
            'ciclo'             => $this->metadata['ciclo']             ?? null,
            'empreendimento_id' => $this->metadata['empreendimento_id'] ?? null,
            'origem'            => $this->metadata['origem']            ?? null,
            'actor_user_id'     => $this->metadata['actor_user_id']     ?? null,
            'credited_user_id'  => $this->metadata['credited_user_id']  ?? null,
            'prazo_em'          => $this->metadata['prazo_em']          ?? null,
            'entregue_em'       => $this->metadata['entregue_em']       ?? null,
        ];
    }
}
