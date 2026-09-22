<?php

declare(strict_types=1);

namespace App\Modules\Rat\Domain\Events;

use App\Core\Events\DomainEvent;

/**
 * Relatorio da ocorrencia fechado pelo autor (status 0 -> 1).
 *
 * Emitido por RatWriteService::finalize (fechamento manual) e por
 * createWithData quando o payload chega com `finalize`. O fechamento
 * automatico por prazo (rat:close-expired) NAO emite: ninguem entregou nada,
 * a janela de 48h simplesmente venceu e nao ha autor a creditar.
 *
 * `prazo_edicao` e o prazo aplicavel (48h a partir da criacao) e
 * `finalizado_em` e a data de entrega; o par sustenta o bonus de
 * tempestividade no ranking. `actor_user_id` e capturado com Auth::id() no
 * service — as colunas de autoria do RAT nao tem FK para `users` e nao
 * reconstroem isso depois.
 */
final readonly class RelatorioFinalizadoV1 extends DomainEvent
{
    public function eventName(): string
    {
        return 'rat.relatorio.finalizado';
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
            'ocorrencia_id'  => $this->metadata['ocorrencia_id']  ?? null,
            'numero_bos'     => $this->metadata['numero_bos']     ?? null,
            'sequencial_ano' => $this->metadata['sequencial_ano'] ?? null,
            'status'         => $this->metadata['status']         ?? null,
            'actor_user_id'  => $this->metadata['actor_user_id']  ?? null,
            'prazo_edicao'   => $this->metadata['prazo_edicao']   ?? null,
            'finalizado_em'  => $this->metadata['finalizado_em']  ?? null,
            'origem'         => $this->metadata['origem']         ?? null,
        ];
    }
}
