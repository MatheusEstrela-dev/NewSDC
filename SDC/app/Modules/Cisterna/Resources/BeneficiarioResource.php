<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Cisterna\Models\CisternaBeneficiario
 */
class BeneficiarioResource extends JsonResource
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
            'telefone' => $this->telefone,
            'data_nascimento' => $this->data_nascimento?->toDateString(),
            'cadastro_unico' => $this->cadastro_unico,

            'municipio' => [
                'id' => $this->municipio_id,
                'nome' => $this->municipio?->nome,
                'uf' => $this->municipio?->uf,
            ],
            'comunidade' => [
                'id' => $this->comunidade_id,
                'nome' => $this->comunidade?->nome,
            ],
            'endereco' => $this->endereco,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,

            'ordem_servico' => $this->whenLoaded(
                'ordemServico',
                fn (): ?array => $this->ordemServico === null ? null : [
                    'id' => $this->ordemServico->id,
                    'nome' => $this->ordemServico->nome,
                    'lote' => $this->ordemServico->lote?->nome,
                ]
            ),

            'situacao_analise' => [
                'valor' => $this->situacao_analise->value,
                'rotulo' => $this->situacao_analise->label(),
                'observacao' => $this->situacao_analise_obs,
            ],
            'situacao_obra' => [
                'valor' => $this->situacao_obra->value,
                'rotulo' => $this->situacao_obra->label(),
            ],
            'ranqueamento_ordem' => $this->ranqueamento_ordem,

            'criterios_sociais' => [
                'qtd_pessoas' => $this->qtd_pessoas,
                'renda' => $this->renda,
                'renda_per_capita' => $this->renda_per_capita,
                'possui_deficiencia' => $this->possui_deficiencia,
                'possui_crianca' => $this->possui_crianca,
                'data_nascimento_crianca' => $this->data_nascimento_crianca?->toDateString(),
                'possui_idoso' => $this->possui_idoso,
                'chefiada_mulher' => $this->chefiada_mulher,
            ],

            'avaliacao_tecnica' => [
                'tipo_moradia' => $this->tipo_moradia,
                'tipo_moradia_outro' => $this->tipo_moradia_outro,
                'comprimento_telhado' => $this->comprimento_telhado,
                'largura_telhado' => $this->largura_telhado,
                'area_telhado' => $this->area_telhado,
                'comprimento_testada' => $this->comprimento_testada,
                'num_caidas_telhado' => $this->num_caidas_telhado,
                'cobertura_telhado' => $this->cobertura_telhado,
                'cobertura_outro' => $this->cobertura_outro,
                'possui_fogao_lenha' => $this->possui_fogao_lenha,
                'medida_telhado_area_fogao' => $this->medida_telhado_area_fogao,
                'testada_disp_parte_fogao' => $this->testada_disp_parte_fogao,
            ],

            'atendimento_pipa' => [
                'atendido' => $this->atendido_por_pipa,
                'responsaveis' => $this->whenLoaded(
                    'atendimentosPipa',
                    fn (): array => $this->atendimentosPipa->map(fn ($a): array => [
                        'valor' => $a->responsavel->value,
                        'rotulo' => $a->responsavel->label(),
                        'descricao' => $a->descricao,
                    ])->all()
                ),
            ],

            'responsaveis_cadastro' => [
                'agente_nome' => $this->agente_nome,
                'agente_cpf' => $this->agente_cpf,
                'engenheiro_nome' => $this->engenheiro_nome,
                'engenheiro_crea' => $this->engenheiro_crea,
            ],

            'observacoes' => $this->observacoes,

            // `->resolve()` nas duas: `Resource::collection()` embrulha o
            // resultado em `data`, e aninhado isso vira
            // `beneficiario.vistorias.data`. A tela de detalhe fazia
            // `.some()` sobre o que esperava ser lista, recebia o objeto do
            // wrapper e morria com TypeError -- pagina branca, sem erro no
            // servidor. Chave ausente quando a relacao nao veio carregada.
            'vistorias' => $this->when(
                $this->relationLoaded('vistorias'),
                fn (): array => VistoriaResource::collection($this->vistorias)->resolve(),
            ),
            'notificacoes' => $this->when(
                $this->relationLoaded('notificacoes'),
                fn (): array => NotificacaoResource::collection($this->notificacoes)->resolve(),
            ),

            'fotos_imovel' => $this->when(
                $this->relationLoaded('media'),
                fn (): array => $this->getMedia('fotos_imovel')->map(fn ($m): array => [
                    'id' => $m->id,
                    'url' => $m->getUrl(),
                    'thumb' => $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : null,
                    'angulo' => $m->getCustomProperty('angulo'),
                    'observacao' => $m->getCustomProperty('observacao'),
                ])->all()
            ),
            'comprovantes' => $this->when(
                $this->relationLoaded('media'),
                fn (): array => $this->getMedia('comprovantes')->map(fn ($m): array => [
                    'id' => $m->id,
                    'url' => $m->getUrl(),
                    'tipo' => $m->getCustomProperty('tipo'),
                    'nome' => $m->file_name,
                ])->all()
            ),

            'criado_em' => $this->created_at?->toIso8601String(),
            'atualizado_em' => $this->updated_at?->toIso8601String(),
        ];
    }
}
