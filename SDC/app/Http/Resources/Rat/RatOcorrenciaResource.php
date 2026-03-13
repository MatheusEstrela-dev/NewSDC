<?php

declare(strict_types=1);

namespace App\Http\Resources\Rat;

use App\Models\Rat\RatOcorrencia;
use App\Models\Rat\Relatos\RatRelatoDadosGerais;
use App\Models\Rat\Relatos\RatRelatoEnvolvidos;
use App\Models\Rat\Relatos\RatRelatoRecurso;
use App\Models\Rat\Relatos\RatRelatoVistoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso de API para RatOcorrencia — serializa a ocorrência com seus relatos polimórficos.
 *
 * Usado pela API REST consumida pelo mobile e Power BI.
 *
 * @mixin RatOcorrencia
 */
class RatOcorrenciaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var RatOcorrencia $this */
        $relatos = $this->relationLoaded('relatosMorph')
            ? $this->relatosMorph->map(fn($relato) => $relato->conteudo)->filter()
            : collect();

        return [
            'id'                   => $this->id,
            'numero_bos'           => $this->numero_bos,
            'sequencial_ano'       => $this->sequencial_ano,
            'status'               => $this->status,
            'status_rotulo'        => $this->status === 0 ? 'Rascunho' : 'Finalizado',
            'prazo_edicao'         => $this->prazo_edicao?->toIso8601String(),
            'historico'            => $this->historico,
            'ocorrencia_origem_id' => $this->ocorrencia_origem_id,
            'criado_por'           => $this->created_by,
            'atualizado_por'       => $this->updated_by,
            'criado_em'            => $this->created_at?->toIso8601String(),
            'atualizado_em'        => $this->updated_at?->toIso8601String(),

            // Relatos polimórficos agrupados por tipo
            'dados_gerais' => $relatos
                ->filter(fn($c) => $c instanceof RatRelatoDadosGerais)
                ->map(fn(RatRelatoDadosGerais $dg) => [
                    'id'                       => $dg->id,
                    'data_fato'                => $dg->data_fato?->toIso8601String(),
                    'data_inicio_atividade'    => $dg->data_inicio_atividade?->toIso8601String(),
                    'data_termino_atividade'   => $dg->data_termino_atividade?->toIso8601String(),
                    'nat_cobrade_id'           => $dg->nat_cobrade_id,
                    'nat_nome_operacao'        => $dg->nat_nome_operacao,
                    'local_municipio'          => $dg->local_municipio,
                    'local_estadouf'           => $dg->local_estadouf,
                    'local_latitude'           => $dg->local_latitude,
                    'local_longitude'          => $dg->local_longitude,
                    'tem_vistoria'             => $dg->tem_vistoria,
                ])
                ->values()
                ->first(),

            'envolvidos' => $relatos
                ->filter(fn($c) => $c instanceof RatRelatoEnvolvidos)
                ->map(fn(RatRelatoEnvolvidos $e) => [
                    'id'             => $e->id,
                    'g_tipo_pessoa'  => $e->g_tipo_pessoa,
                    'g_lesao_grau'   => $e->g_lesao_grau,
                    'p_nome_completo'=> $e->p_nome_completo,
                    'p_cpf'          => $e->p_cpf,
                    'p_data_nascimento' => $e->p_data_nascimento?->toDateString(),
                    'p_sexo'         => $e->p_sexo,
                ])
                ->values(),

            'recursos' => $relatos
                ->filter(fn($c) => $c instanceof RatRelatoRecurso)
                ->map(fn(RatRelatoRecurso $rc) => [
                    'id'             => $rc->id,
                    'recurso_tipo'   => $rc->recurso_tipo,
                    'viatura_placa'  => $rc->viatura_placa,
                    'viatura_tipo'   => $rc->viatura_tipo,
                    'viatura_saida'  => $rc->viatura_saida?->toIso8601String(),
                    'viatura_chegada'=> $rc->viatura_chegada?->toIso8601String(),
                    'viatura_km'     => $rc->viatura_km,
                ])
                ->values(),

            'vistoria' => $relatos
                ->filter(fn($c) => $c instanceof RatRelatoVistoria)
                ->map(fn(RatRelatoVistoria $v) => [
                    'id'                    => $v->id,
                    'v_solicitante_nome'    => $v->v_solicitante_nome,
                    'v_tipo_imovel'         => $v->v_tipo_imovel,
                    'v_estado_conservacao'  => $v->v_estado_conservacao,
                    'v_latitude'            => $v->v_latitude,
                    'v_longitude'           => $v->v_longitude,
                ])
                ->values()
                ->first(),
        ];
    }
}
