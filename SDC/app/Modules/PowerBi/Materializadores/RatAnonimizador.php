<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Materializadores;

use App\Modules\Rat\Models\RatOcorrencia;
use Illuminate\Support\Carbon;

/**
 * Monta as linhas anonimizadas de bi_rat a partir da RAT e da linha nested do
 * RatExportBIService. Tudo que entra e por ALLOWLIST (campo a campo): campo
 * novo no service ou no dados_gerais nao chega ao BI sem ser listado aqui.
 *
 * LGPD: nenhum nome, CPF, e-mail, telefone, endereco de pessoa, criador,
 * solicitante, proprietario ou texto livre. Envolvidos so agregados.
 */
final class RatAnonimizador
{
    /** Chaves do dados_gerais cru que vao para o documento jsonb (sem pessoal e sem texto livre). */
    private const DOCUMENTO_DADOS_GERAIS = [
        'nat_codigo', 'nat_cobrade_id', 'nat_ocorrencia', 'nat_nome_operacao',
        'uni_responsavel_municipio', 'uni_responsavel_codigo', 'uni_responsavel_unidade',
        'uni_bo_cod_unidade', 'uni_bo_ano', 'uni_bo_sequencial',
        'com_ocorrencia_atendimento', 'local_pais', 'local_estadouf', 'local_municipio',
        'local_municipio_nome', 'local_ocorrencia_tipo', 'local_ocorrencia_estradas_rodovias',
        'tem_vistoria', 'status',
    ];

    private const OCORRENCIA = [
        'numero_bos', 'status', 'status_descricao', 'uf', 'municipio', 'data_fato', 'cod_cobrade',
        'cobrade_descricao', 'cod_ocorrencia', 'nome_operacao', 'nome_unidade', 'municipio_responsavel',
        'codigo_unidade', 'latitude', 'longitude', 'local_pais', 'local_bairro', 'local_tipo_ocorrencia',
        'data_inicio_atividade', 'data_termino_atividade', 'comunicacao_data', 'comunicacao_atendimento',
    ];

    private const RECURSO = ['tipo_recurso', 'categoria', 'numero_viatura', 'placa', 'orgao', 'descricao'];

    /** coluna bi => [secao da vistoria nested, chave]. */
    private const VISTORIA = [
        'tipo_area' => ['local', 'tipo_area'],
        'tipo_imovel' => ['imovel', 'tipo_imovel'],
        'tipo_construcao' => ['imovel', 'tipo_construcao'],
        'tipo_edificacao' => ['imovel', 'tipo_edificacao'],
        'tipo_terreno_relevo' => ['imovel', 'tipo_terreno_relevo'],
        'sistema_estrutural' => ['imovel', 'sistema_estrutural'],
        'numero_pavimentos' => ['imovel', 'numero_pavimentos'],
        'estado_conservacao' => ['imovel', 'estado_conservacao'],
        'regime_ocupacao' => ['imovel', 'regime_ocupacao'],
        'bairro' => ['imovel', 'bairro'],
        'municipio' => ['imovel', 'municipio'],
        'numero_moradores' => ['proprietario_morador', 'numero_moradores'],
        'ha_idosos' => ['proprietario_morador', 'ha_idosos'],
        'ha_criancas' => ['proprietario_morador', 'ha_criancas'],
        'ha_dificuldade_locomocao' => ['proprietario_morador', 'ha_dificuldade_locomocao'],
        'abastecimento_agua' => ['infraestrutura', 'abastecimento_agua'],
        'esgotamento_sanitario' => ['infraestrutura', 'esgotamento_sanitario'],
        'drenagem_superficial' => ['infraestrutura', 'drenagem_superficial'],
        'sistema_viario_acesso' => ['infraestrutura', 'sistema_viario_acesso'],
        'tipo_revestimento' => ['infraestrutura', 'tipo_revestimento'],
        'condicoes_acesso' => ['infraestrutura', 'condicoes_acesso'],
        'numero_moradias_terreno' => ['infraestrutura', 'numero_moradias_terreno'],
        'distancia_encosta' => ['infraestrutura', 'distancia_encosta'],
        'material_construtivo' => ['infraestrutura', 'material_construtivo'],
        'conservacao_estrutural' => ['infraestrutura', 'conservacao_estrutural'],
        'elementos_estruturais' => ['analise_tecnica', 'elementos_estruturais'],
        'elementos_construtivos' => ['analise_tecnica', 'elementos_construtivos'],
        'agentes_potencializadores' => ['analise_tecnica', 'agentes_potencializadores'],
        'processos_geodinamicos' => ['analise_tecnica', 'processos_geodinamicos'],
        'tipo_destinacao' => ['destinacao_localizacao', 'tipo_destinacao'],
        'tipo_localizacao' => ['destinacao_localizacao', 'tipo_localizacao'],
    ];

    /** Listas (text[]) da vistoria: o texto livre depois de "Outros:" e descartado. */
    private const VISTORIA_LISTAS = ['patologias', 'bens_afetados', 'orgaos_acionados', 'encaminhamentos'];

    /**
     * @param array<string, mixed> $linha linha nested do RatExportBIService
     * @return array{ocorrencia: array<string, mixed>, recursos: list<array<string, mixed>>, vistoria: ?array<string, mixed>, envolvidos: list<array<string, mixed>>}
     */
    public function pacote(RatOcorrencia $o, array $linha): array
    {
        $id = (string) $o->id;
        $recursos = $this->recursos($id, (array) ($linha['recursos'] ?? []));
        $envolvidos = (array) ($linha['envolvidos'] ?? []);

        return [
            'ocorrencia' => $this->ocorrencia($o, $linha, count($recursos), count($envolvidos)),
            'recursos' => $recursos,
            'vistoria' => is_array($linha['vistoria'] ?? null) ? $this->vistoria($id, $linha['vistoria']) : null,
            'envolvidos' => $this->envolvidos($id, $envolvidos),
        ];
    }

    /** @param array<string, mixed> $linha */
    private function ocorrencia(RatOcorrencia $o, array $linha, int $qtdRecursos, int $qtdEnvolvidos): array
    {
        $dg = (array) ($linha['dados_gerais'] ?? []);
        $hist = (array) ($linha['historico'] ?? []);

        $row = ['id' => (string) $o->id];
        foreach (self::OCORRENCIA as $coluna) {
            $row[$coluna] = $dg[$coluna] ?? null;
        }

        $row['ano'] = isset($dg['ano']) ? (int) $dg['ano'] : null;
        $row['mes'] = isset($dg['mes']) ? (int) $dg['mes'] : null;
        $row['criado_em'] = $hist['criado_em'] ?? null;
        $row['atualizado_em'] = $hist['atualizado_em'] ?? null;
        $row['prazo_edicao'] = $hist['prazo_edicao'] ?? null;
        $row['qtd_recursos'] = $qtdRecursos;
        $row['qtd_envolvidos'] = $qtdEnvolvidos;
        $row['documento'] = $this->documento($o, $hist);
        $atividade = $o->getAttribute('atividade_em');
        $row['origem_atualizado_em'] = $atividade === null ? null : Carbon::parse((string) $atividade, (string) config('app.timezone'));

        return $row;
    }

    /** @param array<string, mixed> $hist */
    private function documento(RatOcorrencia $o, array $hist): array
    {
        $cru = is_array($o->dados_gerais) ? $o->dados_gerais : [];
        $doc = [];
        foreach (self::DOCUMENTO_DADOS_GERAIS as $chave) {
            $valor = $cru[$chave] ?? null;
            if (is_scalar($valor)) {
                $doc[$chave] = $valor;
            }
        }
        // Sinal derivado do texto livre, sem o texto.
        $doc['historico_ocorrencia'] = $hist['historico_ocorrencia'] ?? 'nao';

        return $doc;
    }

    /**
     * @param list<array<string, mixed>> $recursos
     * @return list<array<string, mixed>>
     */
    private function recursos(string $id, array $recursos): array
    {
        $saida = [];
        foreach (array_values($recursos) as $i => $r) {
            $row = ['ocorrencia_id' => $id, 'seq' => $i + 1];
            foreach (self::RECURSO as $coluna) {
                $row[$coluna] = $r[$coluna] ?? null;
            }
            $saida[] = $row;
        }

        return $saida;
    }

    /** @param array<string, mixed> $vis */
    private function vistoria(string $id, array $vis): array
    {
        $row = ['ocorrencia_id' => $id];
        foreach (self::VISTORIA as $coluna => [$secao, $chave]) {
            $valor = $vis[$secao][$chave] ?? null;
            $row[$coluna] = is_scalar($valor) ? $valor : null;
        }
        foreach (self::VISTORIA_LISTAS as $coluna) {
            $row[$coluna] = $this->arrayPg(array_map(
                fn (string $rotulo): string => str_starts_with($rotulo, 'Outros') ? 'Outros' : $rotulo,
                array_map('strval', (array) ($vis[$coluna] ?? []))
            ));
        }

        return $row;
    }

    /**
     * Envolvidos viram contagem por categoria; nenhum campo individual passa.
     *
     * @param list<array<string, mixed>> $envolvidos
     * @return list<array<string, mixed>>
     */
    private function envolvidos(string $id, array $envolvidos): array
    {
        $grupos = [];
        foreach ($envolvidos as $e) {
            $categoria = [
                'tipo_pessoa' => (string) ($e['tipo_pessoa'] ?? ''),
                'sexo' => (string) ($e['sexo'] ?? ''),
                'condicao_fisica' => (string) ($e['condicao_fisica'] ?? ''),
                'lesao_grau' => (string) ($e['lesao_grau'] ?? ''),
            ];
            $chave = implode('|', $categoria);
            $grupos[$chave] ??= ['ocorrencia_id' => $id, ...$categoria, 'quantidade' => 0,
                'qtd_morador_rua' => 0, 'qtd_estrangeiro' => 0, 'qtd_turista' => 0];
            $grupos[$chave]['quantidade']++;
            $grupos[$chave]['qtd_morador_rua'] += empty($e['morador_rua']) ? 0 : 1;
            $grupos[$chave]['qtd_estrangeiro'] += empty($e['estrangeiro']) ? 0 : 1;
            $grupos[$chave]['qtd_turista'] += empty($e['turista']) ? 0 : 1;
        }

        return array_values($grupos);
    }

    /**
     * Literal de array do Postgres ('{"a","b"}'): o GravadorBi serializa array
     * PHP como json, que nao e aceito por uma coluna text[].
     *
     * @param list<string> $itens
     */
    private function arrayPg(array $itens): string
    {
        $itens = array_map(
            fn (string $i): string => '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $i).'"',
            $itens
        );

        return '{'.implode(',', $itens).'}';
    }
}
