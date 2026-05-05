<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource completo para visualização/edição/impressão detalhada de um RAT.
 */
class RatResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var \App\Modules\Rat\Models\RatOcorrencia $this */
        return [
            'id'             => $this->id,
            'protocolo'      => $this->protocolo ?? $this->numero_bos ?? null,
            'status'         => $this->status,
            'status_label'   => $this->status === 1 ? 'Finalizado' : 'Rascunho',
            'tem_vistoria'   => $this->tem_vistoria ?? false,
            'dados_gerais'   => $this->dados_gerais ?? [],
            'local'          => $this->local ?? [],
            'endereco'       => $this->endereco ?? [],
            'comunicacao'    => $this->comunicacao ?? [],
            'recursos'       => collect($this->recursos ?? [])->map(function($c) {
                // Se $c já for um array e não um objeto/modelo (como agora que simplificamos o modelo)
                if (is_array($c)) {
                    $c = (object) $c;
                }
                if (!$c) return null;
                
                return [
                    'id'                   => $c->id ?? null,
                    'seq'                  => $c->seq ?? null,
                    'tipo_recurso'         => ($c->recurso_tipo ?? null) === 'pe' ? 'pessoal' : ($c->recurso_tipo ?? null),
                    'categoria'            => $c->viatura_tipo ?? null,
                    'identificacao'        => $c->viatura_placa ?? null,
                    'orgao_responsavel'    => $c->viatura_orgao ?? null,
                    'data_hora_saida'      => (isset($c->viatura_saida) && $c->viatura_saida != '0' && $c->viatura_saida != '') ? (Carbon::parse($c->viatura_saida)->format('Y-m-d\TH:i')) : null,
                    'data_hora_chegada'    => (isset($c->viatura_chegada) && $c->viatura_chegada != '0' && $c->viatura_chegada != '') ? (Carbon::parse($c->viatura_chegada)->format('Y-m-d\TH:i')) : null,
                    'km_percorrido'        => $c->viatura_km ?? null,
                    'local_origem'         => $c->viatura_local_origem ?? null,
                    'local_destino'        => $c->viatura_local_destino ?? null,
                    'quantidade'           => $c->viatura_quantidade ?? null,
                    'condicao'             => ($c->viatura_condicao ?? null) === 'boa' ? 'operacional' : ($c->viatura_condicao ?? null),
                    'descricao'            => $c->recurso_descricao ?? null,
                    'agentes'              => isset($c->agentes) ? collect($c->agentes)->map(fn($a) => [
                        'id'            => $a['id'] ?? null,
                        'corporacao'    => $a['corporacao'] ?? null,
                        'masp'          => $a['masp'] ?? null,
                        'nome_completo' => $a['nome_completo'] ?? null,
                        'funcao'        => $a['funcao'] ?? null,
                    ])->toArray() : [],
                ];
            })->filter()->values()->toArray(),
            'envolvidos'     => collect($this->envolvidos ?? [])->map(fn($c) => [
                'id'              => $c['id'] ?? null,
                'g_tipo_pessoa'   => $c['g_tipo_pessoa'] ?? null,
                'p_nome_completo' => $c['p_nome_completo'] ?? null,
                'p_cpf'           => $c['p_cpf'] ?? null,
                'p_sexo'          => $c['p_sexo'] ?? null,
            ])->filter()->values()->toArray(),
            'vistoria'       => $this->vistoria ?? [],
            'historico'      => $this->historico ?? [],
            'anexos'         => $this->anexos ?? [],
            'criado_por'     => $this->creator?->name ?? 'Sistema',
            'atualizado_por' => $this->updater?->name ?? null,
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}

