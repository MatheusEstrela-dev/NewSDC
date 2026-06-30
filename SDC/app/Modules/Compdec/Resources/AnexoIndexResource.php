<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Resources;

use App\Modules\Compdec\Models\CompdecAnexo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Versao enxuta para listagem na tab Anexos.
 *
 * @property-read CompdecAnexo $resource
 *
 * @mixin CompdecAnexo
 */
class AnexoIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $media = $this->getFirstMedia(CompdecAnexo::MEDIA_ARQUIVO);

        return [
            'id' => $this->id,
            'tipo' => $this->tipo?->value,
            'tipo_label' => $this->tipo?->label(),
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'numero' => $this->numero,
            'data_emissao' => $this->data_emissao?->toDateString(),
            'data_validade' => $this->data_validade?->toDateString(),
            'status_validade' => $this->status_validade->value,
            'status_validade_label' => $this->status_validade->label(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'created_at_formatado' => $this->created_at?->format('d/m/Y H:i'),
            'tem_arquivo' => $media !== null,
            'arquivo_nome' => $media?->file_name,
            'arquivo_nome_original' => $media?->name,
            'arquivo_tamanho' => $media?->size,
            'arquivo_tamanho_formatado' => $media ? number_format((float) $media->size / 1024, 1, ',', '.') . ' KB' : null,
            'arquivo_enviado_em' => $media?->created_at?->toDateTimeString(),
            'arquivo_enviado_em_formatado' => $media?->created_at?->format('d/m/Y H:i'),
        ];
    }
}
