<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Resources;

use App\Modules\NotasTecnicas\Models\NotaTecnica;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Uma linha da lista do workspace. `lida` vem calculada pelo
 * NotaTecnicaConsultaService::listar() contra a versao corrente.
 *
 * @mixin NotaTecnica
 */
final class NotaTecnicaListaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'titulo' => $this->titulo,
            'categoria' => $this->categoria?->nome,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_variante' => $this->status->variante(),
            'versao_atual' => $this->versaoAtual?->numero,
            'total_versoes' => (int) $this->versoes_count,
            'lida' => (bool) $this->lida,
            'favorita' => (bool) $this->favorita,
            'atualizada_em' => $this->versaoAtual?->created_at?->toIso8601String(),
            // So vem preenchido quando ha busca e o termo apareceu no PDF.
            'trecho' => $this->trecho ?? null,
        ];
    }
}
