<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Resources;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Cisterna\Models\CisternaBeneficiario
 */
class BeneficiarioIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cpf' => $this->cpf,
            'nome' => $this->nome,
            'municipio' => $this->municipio?->nome,
            'comunidade' => $this->comunidade?->nome,
            'situacao_analise' => [
                'valor' => $this->situacao_analise->value,
                'rotulo' => $this->situacao_analise->label(),
            ],
            'situacao_obra' => [
                'valor' => $this->situacao_obra->value,
                'rotulo' => $this->situacao_obra->label(),
            ],
            'ranqueamento_ordem' => $this->ranqueamento_ordem,
            'lote' => $this->ordemServico?->lote?->nome,
            'ordem_servico' => $this->ordemServico?->nome,
            // Substitui os tres whereHas do legado: as etapas concluidas vem
            // da relacao ja carregada, sem consulta extra por linha.
            'etapas_concluidas' => $this->when(
                $this->relationLoaded('vistorias'),
                fn (): array => $this->vistorias
                    ->filter(fn ($v): bool => $v->estaConcluida())
                    ->map(fn ($v): string => $v->etapa->value)
                    ->values()
                    ->all(),
                []
            ),
            'numero_instalacao' => $this->when(
                $this->relationLoaded('vistorias'),
                fn () => $this->vistoriaDaEtapa(EtapaVistoria::FORNECEDOR)?->numero_instalacao
            ),
        ];
    }
}
