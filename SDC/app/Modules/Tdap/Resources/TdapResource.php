<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use App\Modules\Tdap\Models\Vistoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
|--------------------------------------------------------------------------
| TDAP Resources (arquivo consolidado)
|--------------------------------------------------------------------------
|
| Todas as JsonResource do modulo TDAP residem aqui. Classes mantem nomes
| originais (<Entidade>Resource para detalhe, <Entidade>IndexResource para
| listagem leve) — Controllers e Services importam exatamente como antes.
|
| Autoload via Composer classmap (composer.json: autoload.classmap)
| resolve as classes independente do nome do arquivo. Apos
| adicionar/remover, rodar:
|   docker exec newsdc_frankenphp_local composer dump-autoload -o
|
*/

/**
 * @mixin \App\Modules\Tdap\Models\Ata
 */
class AtaIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hoje = now()->startOfDay();
        $inicio = $this->dt_inicio;
        $final = $this->dt_final;
        $vigente = $this->ativo && $inicio && $final && $inicio->lte($hoje) && $final->gte($hoje);

        return [
            'id'          => $this->id,
            'numero'      => $this->numero,
            'dt_inicio'   => $inicio?->toDateString(),
            'dt_final'    => $final?->toDateString(),
            'ativo'       => (bool) $this->ativo,
            'vigente'     => (bool) $vigente,
            'lotes_count' => (int) ($this->lotes_count ?? 0),
        ];
    }
}

/**
 * @mixin \App\Modules\Tdap\Models\Ata
 */
class AtaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hoje = now()->startOfDay();
        $inicio = $this->dt_inicio;
        $final = $this->dt_final;
        $vigente = $this->ativo && $inicio && $final && $inicio->lte($hoje) && $final->gte($hoje);

        return [
            'id'             => $this->id,
            'numero'         => $this->numero,
            'dt_inicio'      => $inicio?->toDateString(),
            'dt_final'       => $final?->toDateString(),
            'historico'      => $this->historico,
            'ativo'          => (bool) $this->ativo,
            'vigente'        => (bool) $vigente,
            'observacoes'    => $this->observacoes,
            'lotes_count'    => $this->whenCounted('lotes'),
            'lotes'          => $this->whenLoaded('lotes', fn () => $this->lotes->map(fn ($l) => [
                'id'             => $l->id,
                'numero'         => $l->numero,
                'nome'           => $l->nome,
                'municipio_id'   => $l->municipio_id,
                'municipio_nome' => $l->municipio?->nome,
                'municipio_uf'   => $l->municipio?->uf,
                'prestador_id'   => $l->prestador_id,
                'prestador_nome' => $l->prestador?->nome,
                'qtd_agua_m3'    => (float) $l->qtd_agua_m3,
                'valor_m3'       => (float) $l->valor_m3,
                'ativo'          => (bool) $l->ativo,
            ])),
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}

/**
 * @mixin \App\Modules\Tdap\Models\Caminhao
 */
class CaminhaoIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'placa'          => $this->placa,
            'marca'          => $this->marca,
            'modelo'         => $this->modelo,
            'ano'            => $this->ano,
            'capacidade_m3'  => (float) $this->capacidade_m3,
            'ativo'          => (bool) $this->ativo,
            'prestador_nome' => $this->whenLoaded('prestador', fn () => $this->prestador->nome),
            'prestador_cnpj' => $this->whenLoaded('prestador', fn () => $this->prestador->cnpj),
        ];
    }
}

/**
 * @mixin \App\Modules\Tdap\Models\Caminhao
 */
class CaminhaoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'prestador_id'  => $this->prestador_id,
            'prestador'     => $this->whenLoaded('prestador', fn () => [
                'id'    => $this->prestador->id,
                'nome'  => $this->prestador->nome,
                'cnpj'  => $this->prestador->cnpj,
                'email' => $this->prestador->email ?? null,
            ]),
            'placa'         => $this->placa,
            'marca'         => $this->marca,
            'modelo'        => $this->modelo,
            'cor'           => $this->cor,
            'ano'           => $this->ano,
            'capacidade_m3' => (float) $this->capacidade_m3,
            'ativo'         => (bool) $this->ativo,
            'observacoes'   => $this->observacoes,
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}

/**
 * @mixin \App\Modules\Tdap\Models\CronoCaminhao
 */
class CronoCaminhaoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'cronograma_id'  => $this->cronograma_id,
            'caminhao_id'    => $this->caminhao_id,
            'comunidade_id'  => $this->comunidade_id,
            'agua_prevista'  => (float) $this->agua_prevista,
            'num_viagens'    => (int) $this->num_viagens,
            'agua_entregue'  => (float) $this->agua_entregue,
            'vr_total'       => (float) $this->vr_total,
            'ordem'          => (int) $this->ordem,
            'percentual'     => $this->percentual_entregue,
            'caminhao'       => $this->whenLoaded('caminhao', fn () => [
                'id'            => $this->caminhao?->id,
                'placa'         => $this->caminhao?->placa,
                'marca'         => $this->caminhao?->marca,
                'modelo'        => $this->caminhao?->modelo,
                'capacidade_m3' => (float) ($this->caminhao?->capacidade_m3 ?? 0),
                'ativo'         => (bool) ($this->caminhao?->ativo ?? false),
            ]),
            'viagens_validadas_count' => (int) ($this->viagens_validadas_count ?? 0),
            'viagens_pendentes_count' => (int) ($this->viagens_pendentes_count ?? 0),
        ];
    }
}

/**
 * @mixin \App\Modules\Tdap\Models\Cronograma
 */
class CronogramaIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'numero'          => $this->numero,
            'estado'          => $this->estado,
            'ativo'           => (bool) $this->ativo,
            'encerrado_em'    => $this->encerrado_em?->toIso8601String(),
            'dt_inicio'       => $this->dt_inicio?->toDateString(),
            'dt_final'        => $this->dt_final?->toDateString(),
            'ata_numero'      => $this->whenLoaded('ata', fn () => $this->ata?->numero),
            'lote_numero'     => $this->whenLoaded('lote', fn () => $this->lote?->numero),
            'municipio_nome'  => $this->whenLoaded('municipio', fn () => $this->municipio?->nome),
            'municipio_uf'    => $this->whenLoaded('municipio', fn () => $this->municipio?->uf),
            'prestador_nome'  => $this->whenLoaded('prestador', fn () => $this->prestador?->nome),
            'caminhoes_count' => (int) ($this->caminhoes_count ?? 0),
            'volume_contratado_m3' => $this->volume_contratado,
        ];
    }
}

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
            'dt_inicio'             => $this->dt_inicio?->toDateString(),
            'dt_final'              => $this->dt_final?->toDateString(),
            'dt_inicio_prorrogacao' => $this->dt_inicio_prorrogacao?->toDateString(),
            'dt_final_prorrogacao'  => $this->dt_final_prorrogacao?->toDateString(),
            'dt_inicio_efetiva'     => $this->dt_inicio_efetiva?->toDateString(),
            'dt_final_efetiva'      => $this->dt_final_efetiva?->toDateString(),
            'consumo_diario'        => (float) $this->consumo_diario,
            'dias'                  => (int) $this->dias,
            'fator'                 => (float) $this->fator,
            'volume_contratado_m3'  => $this->volume_contratado,
            'justificativa'         => $this->justificativa,
            'observacao'            => $this->observacao,
            'cnpj'                  => $this->cnpj,
            'ponto_captacao_id'     => $this->ponto_captacao_id,

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

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

/**
 * @mixin \App\Modules\Tdap\Models\CronoViagem
 */
class CronoViagemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'crono_caminhao_id' => $this->crono_caminhao_id,
            'status'            => $this->status,
            'validado'          => $this->validado,
            'data_registro'     => $this->data_registro?->toIso8601String(),
            'data_aprovacao'    => $this->data_aprovacao?->toIso8601String(),
            'obs'               => $this->obs,
            'obs_aprovacao'     => $this->obs_aprovacao,
            'validador'         => $this->whenLoaded('validador', fn () => [
                'id'   => $this->validador?->id,
                'name' => $this->validador?->name,
            ]),
            'cronograma_numero' => $this->whenLoaded('cronoCaminhao', fn () => $this->cronoCaminhao?->cronograma?->numero),
            'caminhao_placa'    => $this->whenLoaded('cronoCaminhao', fn () => $this->cronoCaminhao?->caminhao?->placa),
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}

/**
 * @mixin \App\Modules\Tdap\Models\Historico
 */
class HistoricoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'data_evento' => $this->data_evento?->toIso8601String(),
            'tipo_evento' => $this->tipo_evento,
            'entity_type' => $this->entity_type,
            'entity_id'   => $this->entity_id,
            'obs'         => $this->obs,
            'payload'     => $this->payload,
            'user'        => $this->whenLoaded('user', fn () => [
                'id'   => $this->user?->id,
                'name' => $this->user?->name,
            ]),
        ];
    }
}

/**
 * @mixin \App\Modules\Tdap\Models\Lote
 */
class LoteIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'numero'         => $this->numero,
            'nome'           => $this->nome,
            'ata_id'         => $this->ata_id,
            'ata_numero'     => $this->whenLoaded('ata', fn () => $this->ata->numero),
            'municipio_id'   => $this->municipio_id,
            'municipio_nome' => $this->whenLoaded('municipio', fn () => $this->municipio->nome),
            'municipio_uf'   => $this->whenLoaded('municipio', fn () => $this->municipio->uf),
            'prestador_id'   => $this->prestador_id,
            'prestador_nome' => $this->whenLoaded('prestador', fn () => $this->prestador->nome),
            'prestador_cnpj' => $this->whenLoaded('prestador', fn () => $this->prestador->cnpj),
            'qtd_agua_m3'    => (float) $this->qtd_agua_m3,
            'valor_m3'       => (float) $this->valor_m3,
            'valor_total'    => (float) $this->valor_total,
            'ativo'          => (bool) $this->ativo,
        ];
    }
}

/**
 * @mixin \App\Modules\Tdap\Models\Lote
 */
class LoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'ata_id'       => $this->ata_id,
            'ata'          => $this->whenLoaded('ata', fn () => [
                'id'        => $this->ata->id,
                'numero'    => $this->ata->numero,
                'dt_inicio' => $this->ata->dt_inicio?->toDateString(),
                'dt_final'  => $this->ata->dt_final?->toDateString(),
            ]),
            'municipio_id' => $this->municipio_id,
            'municipio'    => $this->whenLoaded('municipio', fn () => [
                'id'   => $this->municipio->id,
                'nome' => $this->municipio->nome,
                'uf'   => $this->municipio->uf,
            ]),
            'prestador_id' => $this->prestador_id,
            'prestador'    => $this->whenLoaded('prestador', fn () => [
                'id'    => $this->prestador->id,
                'nome'  => $this->prestador->nome,
                'cnpj'  => $this->prestador->cnpj,
                'email' => $this->prestador->email ?? null,
            ]),
            'numero'       => $this->numero,
            'nome'         => $this->nome,
            'qtd_agua_m3'  => (float) $this->qtd_agua_m3,
            'valor_m3'     => (float) $this->valor_m3,
            'valor_total'  => (float) $this->valor_total,
            'ativo'        => (bool) $this->ativo,
            'observacoes'  => $this->observacoes,
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}

/**
 * @mixin \App\Modules\Tdap\Models\Prestador
 */
class PrestadorIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'cnpj_formatado'  => TdapResourceHelpers::formatarCnpj($this->cnpj),
            'nome'            => $this->nome,
            'representante'   => $this->representante,
            'email'           => $this->email,
            'cidade'          => $this->cidade,
            'uf'              => $this->uf,
            'ativo'           => (bool) $this->ativo,
            'caminhoes_count' => (int) ($this->caminhoes_count ?? 0),
        ];
    }
}

/**
 * @mixin \App\Modules\Tdap\Models\Prestador
 */
class PrestadorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'cnpj'           => $this->cnpj,
            'cnpj_formatado' => TdapResourceHelpers::formatarCnpj($this->cnpj),
            'nome'           => $this->nome,
            'representante'  => $this->representante,
            'email'          => $this->email,
            'tel1'           => $this->tel1,
            'tel2'           => $this->tel2,
            'endereco'       => $this->endereco,
            'bairro'         => $this->bairro,
            'cidade'         => $this->cidade,
            'uf'             => $this->uf,
            'cep'            => $this->cep,
            'ativo'          => (bool) $this->ativo,
            'observacoes'    => $this->observacoes,
            'caminhoes_count' => $this->whenCounted('caminhoes'),
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}

/**
 * @mixin \App\Modules\Tdap\Models\ProcessoTdap
 */
class ProcessoTdapIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'numero'         => $this->numero,
            'estado'         => $this->estado?->value,
            'estado_label'   => $this->estado?->label(),
            'swimlane_atual' => $this->swimlane_atual?->value,
            'swimlane_label' => $this->swimlane_atual?->label(),
            'swimlane_cor'   => $this->swimlane_atual?->cor(),
            'municipio_id'   => $this->municipio_id,
            'municipio_nome' => $this->whenLoaded('municipio', fn () => $this->municipio?->nome),
            'municipio_uf'   => $this->whenLoaded('municipio', fn () => $this->municipio?->uf),
            'aberto_em'      => $this->aberto_em?->toIso8601String(),
            'encerrado_em'   => $this->encerrado_em?->toIso8601String(),
        ];
    }
}

/**
 * @mixin \App\Modules\Tdap\Models\ProcessoTdap
 */
class ProcessoTdapResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $transicoesPermitidas = $this->estado
            ? array_map(
                fn ($e) => ['value' => $e->value, 'label' => $e->label()],
                $this->estado->transicoesPermitidas(),
            )
            : [];

        return [
            'id'             => $this->id,
            'numero'         => $this->numero,
            'estado'         => $this->estado?->value,
            'estado_label'   => $this->estado?->label(),
            'swimlane_atual' => $this->swimlane_atual?->value,
            'swimlane_label' => $this->swimlane_atual?->label(),
            'swimlane_cor'   => $this->swimlane_atual?->cor(),

            'municipio_id'   => $this->municipio_id,
            'municipio'      => $this->whenLoaded('municipio', fn () => [
                'id'   => $this->municipio?->id,
                'nome' => $this->municipio?->nome,
                'uf'   => $this->municipio?->uf,
            ]),

            'decretacao_id'  => $this->decretacao_id,
            'pae_form_id'    => $this->pae_form_id,
            'contexto'       => $this->contexto,

            'aberto_em'      => $this->aberto_em?->toIso8601String(),
            'encerrado_em'   => $this->encerrado_em?->toIso8601String(),
            'aberto_por'     => $this->whenLoaded('abertoPor', fn () => [
                'id'   => $this->abertoPor?->id,
                'name' => $this->abertoPor?->name,
            ]),

            'transicoes_permitidas' => $transicoesPermitidas,
            'eh_terminal'    => (bool) $this->isTerminal(),

            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}

/**
 * @mixin Vistoria
 */
class VistoriaIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'nome'            => $this->nome,
            'edital'          => $this->edital,
            'lote'            => $this->lote,
            'data'            => $this->data?->toDateString(),
            'parecer'         => $this->parecer?->value,
            'parecer_label'   => $this->parecer?->label(),
            'esta_vigente'    => (bool) $this->esta_vigente,
            'ficha'           => $this->ficha,
            'caminhao_placa'  => $this->whenLoaded('caminhao', fn () => $this->caminhao?->placa),
            'caminhao_modelo' => $this->whenLoaded('caminhao', fn () => trim(($this->caminhao?->marca ?? '').' '.($this->caminhao?->modelo ?? '')) ?: null),
            'prestador_nome'  => $this->whenLoaded('caminhao', fn () => $this->caminhao?->prestador?->nome),
        ];
    }
}

/**
 * @mixin Vistoria
 */
class VistoriaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $base = [
            'id'              => $this->id,
            'nome'            => $this->nome,
            'edital'          => $this->edital,
            'lote'            => $this->lote,
            'placa_id'        => $this->placa_id,
            'modelo'          => $this->modelo,
            'cor'             => $this->cor,
            'data'            => $this->data?->toDateString(),
            'ano'             => $this->ano,
            'capacidade'      => (float) $this->capacidade,
            'parecer'         => $this->parecer?->value,
            'parecer_label'   => $this->parecer?->label(),
            'esta_vigente'    => (bool) $this->esta_vigente,
            'ficha'           => $this->ficha,
            'observacoes'     => $this->observacoes,
            'caminhao'        => $this->whenLoaded('caminhao', fn () => [
                'id'             => $this->caminhao->id,
                'placa'          => $this->caminhao->placa,
                'marca'          => $this->caminhao->marca,
                'modelo'         => $this->caminhao->modelo,
                'cor'            => $this->caminhao->cor,
                'ano'            => $this->caminhao->ano,
                'capacidade_m3'  => (float) ($this->caminhao->capacidade_m3 ?? 0),
                'prestador'      => $this->caminhao->prestador ? [
                    'id'   => $this->caminhao->prestador->id,
                    'nome' => $this->caminhao->prestador->nome,
                    'cnpj' => $this->caminhao->prestador->cnpj,
                ] : null,
            ]),
            'user'            => $this->whenLoaded('user', fn () => [
                'id'   => $this->user?->id,
                'name' => $this->user?->name,
            ]),
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];

        // Embute todos os campos boolean + obs no payload (frontend consome direto)
        foreach (array_merge(Vistoria::ITENS_ESTRUTURAIS, Vistoria::ITENS_TANQUE) as $campo) {
            $base[$campo] = (bool) ($this->{$campo} ?? false);
            $base["{$campo}_obs"] = $this->{"{$campo}_obs"};
        }

        return $base;
    }
}

/**
 * Helpers compartilhados pelas Resources TDAP.
 */
final class TdapResourceHelpers
{
    public static function formatarCnpj(string $cnpj): string
    {
        if (strlen($cnpj) !== 14) {
            return $cnpj;
        }

        return substr($cnpj, 0, 2).'.'
            .substr($cnpj, 2, 3).'.'
            .substr($cnpj, 5, 3).'/'
            .substr($cnpj, 8, 4).'-'
            .substr($cnpj, 12, 2);
    }
}
