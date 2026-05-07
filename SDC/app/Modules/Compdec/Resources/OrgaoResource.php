<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Resources;

use App\Modules\Compdec\Models\Orgao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Orgao $resource
 *
 * @mixin Orgao
 */
class OrgaoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nome' => $this->nome,

            'tipo' => $this->tipo?->value,
            'tipo_label' => $this->tipo?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),

            'municipio_id' => $this->municipio_id,
            'orgao_superior_id' => $this->orgao_superior_id,

            'municipio' => $this->whenLoaded('municipio', fn (): ?array => $this->municipio ? [
                'id' => $this->municipio->id,
                'nome' => $this->municipio->nome ?? null,
                'uf' => $this->municipio->uf ?? null,
            ] : null),

            'orgao_superior' => $this->whenLoaded('orgaoSuperior', fn (): ?array => $this->orgaoSuperior ? [
                'id' => $this->orgaoSuperior->id,
                'codigo' => $this->orgaoSuperior->codigo,
                'nome' => $this->orgaoSuperior->nome,
                'tipo' => $this->orgaoSuperior->tipo?->value,
            ] : null),

            // Contato
            'email' => $this->email,
            'email_secundario' => $this->email_secundario,
            'email_terciario' => $this->email_terciario,
            'telefone' => $this->telefone,
            'telefone_secundario' => $this->telefone_secundario,
            'fax' => $this->fax,
            'endereco' => $this->endereco,

            // Responsavel (cache derivado da equipe coordenadora)
            'responsavel_nome' => $this->responsavel_nome,
            'responsavel_cpf' => $this->responsavel_cpf,
            'responsavel_telefone' => $this->responsavel_telefone,
            'responsavel_email' => $this->responsavel_email,

            // Geo
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,

            // Atos legais
            'lei_criacao_numero' => $this->lei_criacao_numero,
            'lei_criacao_data' => $this->lei_criacao_data?->toDateString(),
            'decreto_numero' => $this->decreto_numero,
            'decreto_data' => $this->decreto_data?->toDateString(),
            'portaria_numero' => $this->portaria_numero,
            'portaria_data' => $this->portaria_data?->toDateString(),

            // Quantitativos
            'qtd_efetivo' => $this->qtd_efetivo,
            'qtd_nupdec' => $this->qtd_nupdec,

            // Capacidades booleanas
            'tem_sede_propria' => $this->tem_sede_propria,
            'tem_viatura' => $this->tem_viatura,
            'tem_plano_contingencia' => $this->tem_plano_contingencia,
            'tem_mapeamento_risco' => $this->tem_mapeamento_risco,
            'tem_simulado' => $this->tem_simulado,
            'tem_cartao_pdc' => $this->tem_cartao_pdc,

            // Capacidades soft (jsonb)
            'capacidades' => $this->capacidades,
            'abrangencia' => $this->abrangencia,

            // Foto coordenador (Spatie Media)
            'foto_url' => $this->getFirstMediaUrl(Orgao::MEDIA_FOTO_COORDENADOR) ?: null,

            // Prefeitura associada (1:1 via municipio_id)
            'prefeitura' => $this->whenLoaded('prefeitura', fn () => $this->prefeitura
                ? PrefeituraResource::make($this->prefeitura)
                : null),

            // Counts (preenchido via withCount)
            'equipes_count' => $this->when(isset($this->equipes_count), $this->equipes_count),
            'anexos_count' => $this->when(isset($this->anexos_count), $this->anexos_count),
            'planos_count' => $this->when(isset($this->planos_count), $this->planos_count),
            'usuarios_count' => $this->when(isset($this->usuarios_count), $this->usuarios_count),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'legacy_id' => $this->legacy_id,
        ];
    }
}
