<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Resources;

use App\Modules\Compdec\Models\CompdecPlanoContingencia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read CompdecPlanoContingencia $resource
 *
 * @mixin CompdecPlanoContingencia
 */
class PlanoContingenciaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'orgao_id' => $this->orgao_id,
            'versao' => $this->versao,
            'tamanho_bytes' => $this->tamanho_bytes,
            'tamanho_legivel' => $this->formatBytes($this->tamanho_bytes),
            'observacoes' => $this->observacoes,
            'ativo' => $this->ativo,
            'aprovado_em' => $this->aprovado_em?->toIso8601String(),
            'aprovado_por' => $this->aprovado_por,
            'aprovador' => $this->whenLoaded('aprovador', fn (): ?array => $this->aprovador ? [
                'id' => $this->aprovador->id,
                'name' => $this->aprovador->name,
            ] : null),
            'esta_aprovado' => $this->esta_aprovado,
            'tem_arquivo' => $this->getFirstMedia(CompdecPlanoContingencia::MEDIA_ARQUIVO) !== null,
            'arquivo_url' => $this->arquivo_url,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'legacy_id' => $this->legacy_id,
        ];
    }

    private function formatBytes(?int $bytes): ?string
    {
        if (! $bytes) return null;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1) . ' KB';

        return number_format($bytes / (1024 * 1024), 2) . ' MB';
    }
}
