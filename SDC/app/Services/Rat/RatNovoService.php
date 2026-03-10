<?php

declare(strict_types=1);

namespace App\Services\Rat;

use App\Models\Rat\RatOcorrencia;
use App\Models\Rat\Relatos\RatRelatoDadosGerais;
use App\Models\Rat\Relatos\RatRelatoEnvolvidos;
use App\Models\Rat\Relatos\RatRelatoRecurso;
use Illuminate\Http\Request;

/**
 * Lógica de extração de dados para relatórios e Power BI,
 * manipulando a estrutura polimórfica de relatos.
 *
 * Métodos públicos:
 *   getNormalizedDataForPowerBI() — retorna payload normalizado de uma ocorrência
 *   extractDadosGerais()          — extrai dados gerais da ocorrência
 *   extractEnvolvidos()           — extrai todos os envolvidos
 *   extractRecursos()             — extrai todos os recursos com guarnições
 */
class RatNovoService
{
    /**
     * Retorna estrutura normalizada para consumo pelo Power BI.
     */
    public function getNormalizedDataForPowerBI(Request $request): array
    {
        $id          = $request->route('id') ?? $request->input('ocorrencia_id');
        $ocorrencia  = RatOcorrencia::with('relatosMorph')->findOrFail($id);

        return [
            'ocorrencia'   => $ocorrencia->only(['id', 'numero_bos', 'status', 'prazo_edicao']),
            'dados_gerais' => $this->extractDadosGerais($ocorrencia),
            'envolvidos'   => $this->extractEnvolvidos($ocorrencia),
            'recursos'     => $this->extractRecursos($ocorrencia),
        ];
    }

    /**
     * Extrai os dados gerais registrados nos relatos da ocorrência.
     */
    public function extractDadosGerais(RatOcorrencia $ocorrencia): array
    {
        return $ocorrencia->relatosMorph()
            ->get()
            ->map(fn($r) => $r->conteudo)
            ->filter(fn($c) => $c instanceof RatRelatoDadosGerais)
            ->map(fn(RatRelatoDadosGerais $dg) => [
                'data_fato'         => $dg->data_fato?->toIso8601String(),
                'rat_codigo'        => $dg->nat_codigo,
                'cobrade_id'        => $dg->nat_cobrade_id,
                'municipio'         => $dg->local_municipio,
                'uf'                => $dg->local_estadouf,
                'nome_operacao'     => $dg->nat_nome_operacao,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Extrai a lista de envolvidos registrados nos relatos.
     */
    public function extractEnvolvidos(RatOcorrencia $ocorrencia): array
    {
        return $ocorrencia->relatosMorph()
            ->get()
            ->map(fn($r) => $r->conteudo)
            ->filter(fn($c) => $c instanceof RatRelatoEnvolvidos)
            ->map(fn(RatRelatoEnvolvidos $e) => [
                'nome_completo' => $e->p_nome_completo,
                'tipo_pessoa'   => $e->g_tipo_pessoa,
                'cpf'           => $e->p_cpf,
                'lesao_grau'    => $e->g_lesao_grau,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Extrai recursos utilizados com guarnições aninhadas.
     */
    public function extractRecursos(RatOcorrencia $ocorrencia): array
    {
        return $ocorrencia->relatosMorph()
            ->get()
            ->map(fn($r) => $r->conteudo)
            ->filter(fn($c) => $c instanceof RatRelatoRecurso)
            ->map(fn(RatRelatoRecurso $rc) => [
                'tipo'              => $rc->recurso_tipo,
                'viatura_placa'     => $rc->viatura_placa,
                'viatura_tipo'      => $rc->viatura_tipo,
                'recursos_empregados' => $rc->recursosEmpregados()
                    ->with('componentesGuarnicao')
                    ->get()
                    ->map(fn($re) => [
                        'placa'     => $re->viatura_placa,
                        'guarnicao' => $re->componentesGuarnicao
                            ->map(fn($cg) => [
                                'nome_completo' => $cg->nome_completo,
                                'matricula'     => $cg->matricula,
                                'is_condutor'   => $cg->is_condutor,
                            ])
                            ->toArray(),
                    ])
                    ->toArray(),
            ])
            ->values()
            ->toArray();
    }
}
