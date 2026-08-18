<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

use App\Modules\Cisterna\DTOs\NotificacaoDTO;
use App\Modules\Cisterna\Models\CisternaNotificacao;

/**
 * Na edicao o alvo nao muda: so o texto e o anexo.
 */
class UpdateNotificacaoRequest extends StoreNotificacaoRequest
{
    public function authorize(): bool
    {
        $notificacao = $this->notificacaoDaRota();

        return $notificacao !== null && ($this->user()?->can('update', $notificacao) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $notificacao = $this->notificacaoDaRota();

        if ($notificacao === null) {
            return;
        }

        $alias = array_search($notificacao->notificavel_type, NotificacaoDTO::TIPOS_PERMITIDOS, true);

        $this->merge([
            'notificavel_type' => $alias === false ? null : $alias,
            'notificavel_id' => $notificacao->notificavel_id,
        ]);
    }

    private function notificacaoDaRota(): ?CisternaNotificacao
    {
        $notificacao = $this->route('notificacao');

        return $notificacao instanceof CisternaNotificacao ? $notificacao : null;
    }
}
