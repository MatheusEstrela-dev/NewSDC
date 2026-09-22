<?php

declare(strict_types=1);

namespace App\Modules\Pae\Domain\Events;

use App\Core\Events\DomainEvent;

/**
 * Devolutiva de um ciclo de notificacao registrada: a revisao exigida do
 * empreendimento foi recebida e aceita.
 *
 * Emitido por PaeNotificacaoService::registrarDevolutiva.
 *
 * `ciclo` e o ciclo real de notificacao (1..PaeNotificacaoService::MAX_CICLOS).
 * `prazo_em` e dt_notificacao + PRAZO_DIAS e `entregue_em` e dt_devolutiva:
 * as duas sao colunas de marco, nao updated_at.
 *
 * Creditado e o servidor que registrou o aceite -- a revisao em si parte do
 * empreendimento, que nao e usuario do sistema e nao pode ser creditado.
 */
final readonly class RevisaoAceitaV1 extends DomainEvent
{
    public function eventName(): string
    {
        return 'pae.revisao.aceita';
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
            'notificacao_id'    => $this->metadata['notificacao_id']    ?? null,
            'analise_id'        => $this->metadata['analise_id']        ?? null,
            'protocolo_id'      => $this->metadata['protocolo_id']      ?? null,
            'ciclo'             => $this->metadata['ciclo']             ?? null,
            'num_sei'           => $this->metadata['num_sei']           ?? null,
            'actor_user_id'     => $this->metadata['actor_user_id']     ?? null,
            'credited_user_id'  => $this->metadata['credited_user_id']  ?? null,
            'validador_user_id' => $this->metadata['validador_user_id'] ?? null,
            'prazo_em'          => $this->metadata['prazo_em']          ?? null,
            'entregue_em'       => $this->metadata['entregue_em']       ?? null,
        ];
    }
}
