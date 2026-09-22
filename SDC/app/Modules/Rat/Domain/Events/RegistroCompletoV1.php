<?php

declare(strict_types=1);

namespace App\Modules\Rat\Domain\Events;

use App\Core\Events\DomainEvent;

/**
 * Ocorrencia RAT registrada com conteudo — nao o esqueleto vazio.
 *
 * Emitido por RatWriteService::createWithData quando a criacao chega com os
 * blocos de conteudo do formulario. `create()` (protocolo vazio) e
 * `saveDraft()` (autosave) NAO emitem: rascunho nao e entrega.
 *
 * `actor_user_id` vem de Auth::id() no momento da acao, dentro do service.
 * NAO ha como reconstruir isso depois: `rat_ocorrencias.created_by` e
 * string(191) sem FK para `users`, e `rat_relato_recursos.created_by` e
 * unsignedBigInteger — tambem sem FK. Quem consumir este evento deve usar
 * exclusivamente o metadata; ler as colunas de autoria produz credito que
 * ninguem consegue auditar.
 */
final readonly class RegistroCompletoV1 extends DomainEvent
{
    public function eventName(): string
    {
        return 'rat.ocorrencia.registrada';
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
            'registrado_em'  => $this->metadata['registrado_em']  ?? null,
        ];
    }
}
