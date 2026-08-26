<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PmdaPlanoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'protocolo'         => $this->protocolo,
            'municipio_id'      => $this->municipio_id,
            'municipio'         => $this->whenLoaded('municipio', fn () => $this->municipio->nome ?? null),
            'status'            => $this->status->value,
            'status_label'      => $this->status->getLabel(),
            // Nome de cor da paleta do Badge; o componente aplica a receita de pill.
            'status_cor'        => $this->status->getCor(),
            // @deprecated Classe Tailwind crua. Sai quando nenhum consumidor usar.
            'status_color'      => $this->status->getColorClass(),
            'pode_copiar'       => $this->status->permiteCopia(),
            'data'              => $this->data?->toIso8601String(),
            'acoes'             => $this->acoes,
            'motivo'            => $this->motivo,
            'qtd_caminhao'      => $this->qtd_caminhao,
            'pop_at_municipio'  => $this->pop_at_municipio,
            // Etapa 6: acoes de resposta
            'acao_decreto_se'     => (bool) $this->acao_decreto_se,
            'acao_caminhao_pipa'  => (bool) $this->acao_caminhao_pipa,
            'acao_cestas_basicas' => (bool) $this->acao_cestas_basicas,
            'justificativa_apoio' => $this->justificativa_apoio,
            'cobra_iss'         => $this->cobra_iss,
            'num_lei_iss'       => $this->num_lei_iss,
            'aliquota_iss'      => $this->aliquota_iss,
            'resp_cob_iss'      => $this->resp_cob_iss,
            // Municipio / Prefeitura
            'nome_prefeito'     => $this->nome_prefeito,
            'tel_prefeitura'    => $this->tel_prefeitura,
            'tel_prefeito'      => $this->tel_prefeito,
            'cel_prefeito'      => $this->cel_prefeito,
            'endereco'          => $this->endereco,
            'bairro'            => $this->bairro,
            'cep'               => $this->cep,
            'email_prefeitura'  => $this->email_prefeitura,
            'populacao'         => $this->populacao,
            'pop_rural'         => $this->pop_rural,
            'area'              => $this->area,
            // COMPDEC
            'compdec_coordenador' => $this->compdec_coordenador,
            'compdec_decreto'     => $this->compdec_decreto,
            'compdec_lei'         => $this->compdec_lei,
            'compdec_tel'         => $this->compdec_tel,
            'compdec_email'       => $this->compdec_email,
            'pontos'            => $this->whenLoaded('pontos', fn () => $this->pontos->map(fn ($p) => [
                'id'         => $p->id,
                'nome'       => $p->nome,
                'tipo'       => $p->tipo,
                'tipo_label' => self::TIPOS_PONTO[$p->tipo] ?? '—',
                'situacao'   => $p->pivot->situacao ?? 'ATIVO',
                'capacidade' => $p->capacidade,
            ])->values()),
            'compdec_membros'   => $this->whenLoaded('compdecMembros', fn () => $this->compdecMembros->map(fn ($m) => [
                'id'       => $m->id,
                'nome'     => $m->nome,
                'cargo'    => $m->cargo,
                'telefone' => $m->telefone,
            ])->values()),
            'anexos'            => [
                'termo'  => $this->anexoInfo(\App\Modules\Pmda\Models\PmdaPlano::MEDIA_TERMO),
                'oficio' => $this->anexoInfo(\App\Modules\Pmda\Models\PmdaPlano::MEDIA_OFICIO),
            ],
            // Devolutiva da CEDEC: sem estes campos o plano voltava ao municipio
            // como "Em Edicao" comum, sem dizer que foi devolvido nem por que.
            'devolvido'         => (bool) $this->pedido_altera,
            'devolucao_motivo'  => $this->pedido_altera ? $this->motivo_analise : null,
            'devolucao_em'      => $this->pedido_altera ? $this->dt_estado?->toIso8601String() : null,
            'devolucao_por'     => $this->pedido_altera ? $this->resp_estado : null,
            'data_aprov'        => $this->data_aprov?->toIso8601String(),
            'dt_ultima_alteracao' => $this->dt_ultima_alteracao?->toIso8601String(),
            'comunidades_count' => $this->whenCounted('comunidades'),
            'comunidades'       => ComunidadeResource::collection($this->whenLoaded('comunidades')),
        ];
    }

    /** Rotulos de tipo de ponto de captacao (pip_pmda_ponto.tipo 1..6). */
    private const TIPOS_PONTO = [
        1 => 'COPASA',
        2 => 'COPANOR',
        3 => 'Barragem',
        4 => 'SAAE/DMAE',
        5 => 'Poço Público',
        6 => 'Poço Particular',
    ];

    /** Metadados do anexo de uma colecao (URL + nome do arquivo), ou null. */
    private function anexoInfo(string $colecao): ?array
    {
        $media = $this->resource->getFirstMedia($colecao);

        return $media === null ? null : [
            'url'  => $media->getUrl(),
            'nome' => $media->file_name,
        ];
    }
}
