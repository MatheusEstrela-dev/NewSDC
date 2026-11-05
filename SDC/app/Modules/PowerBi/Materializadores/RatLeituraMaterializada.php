<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Materializadores;

use App\Support\Database\ConexaoPowerBi;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

/**
 * Leitura do bi_rat anonimizado no formato nested do export Power BI de hoje
 * (dados_gerais / recursos / envolvidos / vistoria / historico). Campos
 * pessoais nao existem no materializado: ficam ausentes ou nulos, e envolvidos
 * vem como contagem por categoria.
 */
final class RatLeituraMaterializada
{
    private const LISTAS = ['patologias', 'bens_afetados', 'orgaos_acionados', 'encaminhamentos'];

    /** Mesmos filtros do RatQueryService::applyFilters (sobre a data de criacao). */
    public function exportar(Request $request): array
    {
        $consulta = ConexaoPowerBi::leitura()->table('bi_rat.ocorrencias')->where('removido', false)->orderByDesc('criado_em');
        $this->filtrar($consulta, $request);

        $ocorrencias = $consulta->get();
        $ids = $ocorrencias->pluck('id')->all();
        $recursos = $this->filhas('recursos', $ids, 'seq');
        $envolvidos = $this->filhas('envolvidos_agregado', $ids);
        $vistorias = $this->vistorias($ids);

        return $ocorrencias->map(fn (object $o): array => $this->linha(
            $o,
            $recursos[$o->id] ?? [],
            $envolvidos[$o->id] ?? [],
            $vistorias[$o->id] ?? null
        ))->all();
    }

    /** Payload de uma RAT (rat-novo/{id}/power-bi); null se nao materializada. */
    public function porId(string $id): ?array
    {
        $o = ConexaoPowerBi::leitura()->table('bi_rat.ocorrencias')->where('id', $id)->where('removido', false)->first();
        if ($o === null) {
            return null;
        }

        $linha = $this->linha(
            $o,
            $this->filhas('recursos', [$id], 'seq')[$id] ?? [],
            $this->filhas('envolvidos_agregado', [$id])[$id] ?? [],
            $this->vistorias([$id])[$id] ?? null
        );

        return [
            'dados_gerais' => $linha['dados_gerais'] + (array) json_decode((string) $o->documento, true),
            'envolvidos' => $linha['envolvidos'],
            'recursos' => $linha['recursos'],
        ];
    }

    private function filtrar(Builder $q, Request $request): void
    {
        foreach (['protocolo', 'numero_bos'] as $campo) {
            if ($v = $request->input($campo)) {
                $q->where('numero_bos', 'like', '%'.$v.'%');
            }
        }
        if ($request->filled('status')) {
            $s = $request->input('status');
            match ($s) {
                'finalizado' => $q->where('status', 1),
                'em_andamento' => $q->where('status', '!=', 1),
                default => $q->where('status', $s),
            };
        }
        if ($ano = $request->input('ano')) {
            $q->whereRaw('EXTRACT(YEAR FROM criado_em) = ?', [(int) $ano]);
        }
        if ($d = $request->input('data_inicio')) {
            $q->whereDate('criado_em', '>=', $d);
        }
        if ($d = $request->input('data_fim')) {
            $q->whereDate('criado_em', '<=', $d);
        }
    }

    /** @return array<string, list<array<string, mixed>>> ocorrencia_id => linhas */
    private function filhas(string $tabela, array $ids, ?string $ordem = null): array
    {
        if ($ids === []) {
            return [];
        }
        $q = ConexaoPowerBi::leitura()->table('bi_rat.'.$tabela)->where('removido', false)->whereIn('ocorrencia_id', $ids);
        if ($ordem !== null) {
            $q->orderBy($ordem);
        }

        $saida = [];
        foreach ($q->get() as $l) {
            $a = (array) $l;
            unset($a['origem_atualizado_em'], $a['materializado_em'], $a['hash'], $a['removido']);
            $saida[$l->ocorrencia_id][] = $a;
        }

        return $saida;
    }

    /** @return array<string, array<string, mixed>> */
    private function vistorias(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $extras = implode(', ', array_map(fn (string $c): string => "to_json({$c}) AS {$c}_json", self::LISTAS));
        $q = ConexaoPowerBi::leitura()->table('bi_rat.vistorias')->where('removido', false)
            ->whereIn('ocorrencia_id', $ids)->selectRaw("*, {$extras}");

        $saida = [];
        foreach ($q->get() as $l) {
            $saida[$l->ocorrencia_id] = (array) $l;
        }

        return $saida;
    }

    /**
     * @param list<array<string, mixed>> $recursos
     * @param list<array<string, mixed>> $envolvidos
     * @param ?array<string, mixed> $vis
     */
    private function linha(object $o, array $recursos, array $envolvidos, ?array $vis): array
    {
        $doc = (array) json_decode((string) $o->documento, true);
        $dg = [
            'id' => $o->id, 'numero_bos' => $o->numero_bos, 'status' => (int) $o->status,
            'status_descricao' => $o->status_descricao, 'uf' => $o->uf, 'municipio' => $o->municipio,
            'data_fato' => $this->data($o->data_fato), 'ano' => $o->ano === null ? null : (string) $o->ano,
            'mes' => $o->mes === null ? null : sprintf('%02d', $o->mes),
            'cod_cobrade' => $o->cod_cobrade, 'cobrade_descricao' => $o->cobrade_descricao,
            'cod_ocorrencia' => $o->cod_ocorrencia, 'nome_operacao' => $o->nome_operacao,
            'nome_unidade' => $o->nome_unidade, 'municipio_responsavel' => $o->municipio_responsavel,
            'codigo_unidade' => $o->codigo_unidade, 'latitude' => $o->latitude, 'longitude' => $o->longitude,
            'local_pais' => $o->local_pais, 'local_bairro' => $o->local_bairro,
            'local_tipo_ocorrencia' => $o->local_tipo_ocorrencia,
            'data_inicio_atividade' => $this->data($o->data_inicio_atividade),
            'data_termino_atividade' => $this->data($o->data_termino_atividade),
            'comunicacao_data' => $this->data($o->comunicacao_data),
            'comunicacao_atendimento' => $o->comunicacao_atendimento,
            'created_at' => $this->data($o->criado_em), 'updated_at' => $this->data($o->atualizado_em),
        ];

        return [
            'dados_gerais' => $dg,
            'recursos' => $recursos,
            'envolvidos' => $envolvidos,
            'vistoria' => $vis === null ? null : $this->vistoria($vis),
            'historico' => [
                'historico_ocorrencia' => $doc['historico_ocorrencia'] ?? 'nao',
                'criado_por' => null,
                'criado_em' => $this->data($o->criado_em),
                'atualizado_em' => $this->data($o->atualizado_em),
                'prazo_edicao' => $this->data($o->prazo_edicao),
            ],
        ];
    }

    /** @param array<string, mixed> $v */
    private function vistoria(array $v): array
    {
        $pega = fn (array $chaves): array => array_intersect_key($v, array_flip($chaves));

        return [
            'local' => $pega(['tipo_area']),
            'imovel' => $pega(['tipo_imovel', 'tipo_construcao', 'tipo_edificacao', 'tipo_terreno_relevo',
                'sistema_estrutural', 'numero_pavimentos', 'estado_conservacao', 'regime_ocupacao', 'bairro', 'municipio']),
            'proprietario_morador' => $pega(['numero_moradores', 'ha_idosos', 'ha_criancas', 'ha_dificuldade_locomocao']),
            'infraestrutura' => $pega(['abastecimento_agua', 'esgotamento_sanitario', 'drenagem_superficial',
                'sistema_viario_acesso', 'tipo_revestimento', 'condicoes_acesso', 'numero_moradias_terreno',
                'distancia_encosta', 'material_construtivo', 'conservacao_estrutural']),
            'analise_tecnica' => $pega(['elementos_estruturais', 'elementos_construtivos',
                'agentes_potencializadores', 'processos_geodinamicos']),
            'patologias' => json_decode((string) $v['patologias_json'], true) ?? [],
            'bens_afetados' => json_decode((string) $v['bens_afetados_json'], true) ?? [],
            'orgaos_acionados' => json_decode((string) $v['orgaos_acionados_json'], true) ?? [],
            'encaminhamentos' => json_decode((string) $v['encaminhamentos_json'], true) ?? [],
            'destinacao_localizacao' => $pega(['tipo_destinacao', 'tipo_localizacao']),
        ];
    }

    private function data(?string $valor): ?string
    {
        return $valor === null ? null : substr(str_replace('T', ' ', $valor), 0, 19);
    }
}
