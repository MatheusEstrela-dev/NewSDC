<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\Cronograma
 */
class CronogramaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'numero'                => $this->numero,
            'empenho'               => $this->empenho,
            'nota_empenho'          => $this->nota_empenho,
            'estado'                => $this->estado,
            'ativo'                 => (bool) $this->ativo,
            'ativado_em'            => $this->ativado_em?->toIso8601String(),
            'encerrado_em'          => $this->encerrado_em?->toIso8601String(),
            'arquivado'             => $this->arquivado_em !== null,
            'arquivado_em'          => $this->arquivado_em?->toIso8601String(),
            'dt_inicio'             => $this->dt_inicio?->toDateString(),
            'dt_final'              => $this->dt_final?->toDateString(),
            'dt_inicio_prorrogacao' => $this->dt_inicio_prorrogacao?->toDateString(),
            'dt_final_prorrogacao'  => $this->dt_final_prorrogacao?->toDateString(),
            'dt_inicio_efetiva'     => $this->dt_inicio_efetiva?->toDateString(),
            'dt_final_efetiva'      => $this->dt_final_efetiva?->toDateString(),
            'consumo_diario'        => (float) $this->consumo_diario,
            'dias'                  => (int) $this->dias,
            'fator'                 => (float) $this->fator,
            'usar_fator_manual'     => (bool) $this->usar_fator_manual,
            'fator_calculado'       => $this->fator_calculado,
            // Contratado/entregue vem da soma dos caminhoes alocados, nao do
            // `fator` (ver Cronograma::getVolumeContratadoAttribute).
            'volume_contratado_m3'  => $this->volume_contratado,
            'volume_entregue_m3'    => $this->volume_entregue,
            'execucao_percentual'   => $this->percentual_entregue,
            'justificativa'         => $this->justificativa,
            'observacao'            => $this->observacao,
            'cnpj'                  => $this->cnpj,
            'ponto_captacao_id'     => $this->ponto_captacao_id,
            'ponto_captacao'        => $this->whenLoaded('pontoCaptacao', fn () => [
                'id'        => $this->pontoCaptacao->id,
                'nome'      => $this->pontoCaptacao->nome,
                'tipo'      => $this->pontoCaptacao->tipo,
                'tipo_nome' => $this->pontoCaptacao->tipo_nome,
            ]),

            'ata_id'         => $this->ata_id,
            'ata'            => $this->whenLoaded('ata', fn () => [
                'id'        => $this->ata->id,
                'numero'    => $this->ata->numero,
                'dt_inicio' => $this->ata->dt_inicio?->toDateString(),
                'dt_final'  => $this->ata->dt_final?->toDateString(),
            ]),
            'lote_id'        => $this->lote_id,
            'lote'           => $this->whenLoaded('lote', fn () => [
                'id'     => $this->lote->id,
                'numero' => $this->lote->numero,
                'nome'   => $this->lote->nome,
            ]),
            'municipio_id'   => $this->municipio_id,
            'municipio'      => $this->whenLoaded('municipio', fn () => [
                'id'   => $this->municipio->id,
                'nome' => $this->municipio->nome,
                'uf'   => $this->municipio->uf,
            ]),
            'prestador_id'   => $this->prestador_id,
            'prestador'      => $this->whenLoaded('prestador', fn () => [
                'id'    => $this->prestador->id,
                'nome'  => $this->prestador->nome,
                'cnpj'  => $this->prestador->cnpj,
                'email' => $this->prestador->email ?? null,
            ]),
            'user'           => $this->whenLoaded('user', fn () => [
                'id'   => $this->user?->id,
                'name' => $this->user?->name,
            ]),

            'caminhoes_count' => (int) ($this->caminhoes_count ?? 0),
            'caminhoes'       => $this->whenLoaded('caminhoes', fn () => $this->caminhoes->map(fn ($cc) => [
                'id'             => $cc->id,
                'caminhao_id'    => $cc->caminhao_id,
                'placa'          => $cc->caminhao?->placa,
                'marca_modelo'   => trim(($cc->caminhao?->marca ?? '').' '.($cc->caminhao?->modelo ?? '')) ?: null,
                'capacidade_m3'  => (float) ($cc->caminhao?->capacidade_m3 ?? 0),
                'agua_prevista'  => (float) $cc->agua_prevista,
                'num_viagens'    => (int) $cc->num_viagens,
                'agua_entregue'  => (float) $cc->agua_entregue,
                'vr_total'       => (float) $cc->vr_total,
                'ordem'          => (int) $cc->ordem,
                'percentual'     => $cc->percentual_entregue,
            ])),

            'comprovantes' => $this->whenLoaded('comprovantes', fn () => $this->comprovantes->map(fn ($cp) => [
                'id'                => $cp->id,
                'nome_original'     => $cp->nome_original,
                'descricao'         => $cp->descricao,
                'mime_type'         => $cp->mime_type,
                'tamanho_formatado' => $cp->tamanho_formatado,
                'download_url'      => route('tdap.cronogramas.comprovantes.download', [$this->id, $cp->id]),
                'created_at'        => $cp->created_at?->toIso8601String(),
            ])),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
