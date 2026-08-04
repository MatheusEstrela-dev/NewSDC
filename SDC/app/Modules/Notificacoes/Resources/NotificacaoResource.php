<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Resources;

use App\Modules\Notificacoes\Models\Notificacao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forma como uma notificacao chega ao cliente.
 *
 * Deliberadamente plana e pequena: o painel do sino carrega dezenas destes em
 * toda abertura. Icone, cor e rotulo de tempo sao decididos no frontend a partir
 * de tipo e created_at, e nao viajam pela rede.
 *
 * @mixin Notificacao
 */
class NotificacaoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $dados = $this->data ?? [];

        return [
            'id' => $this->id,
            'type' => $dados['type'] ?? 'info',
            'module' => $dados['module'] ?? null,
            'title' => $dados['title'] ?? '',
            'message' => $dados['message'] ?? '',
            'action_url' => $dados['action_url'] ?? null,
            'action_text' => $dados['action_text'] ?? null,
            'group_key' => $this->group_key,

            // Quantos eventos esta linha representa. O frontend mostra o selo
            // "N novos" a partir daqui, em vez de contar linhas repetidas.
            'group_count' => (int) $this->group_count,

            'read' => $this->read_at !== null,
            'read_at' => $this->read_at?->toIso8601String(),

            // Instante do fato mais recente: e o que o usuario entende como
            // "quando isso aconteceu" numa linha agrupada.
            'created_at' => ($this->last_event_at ?? $this->created_at)?->toIso8601String(),
        ];
    }
}
