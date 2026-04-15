<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\DTOs\RatFilterDTO;
use App\Modules\Rat\Models\Rat;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Responsabilidade unica: fornecer dados do model Rat para a API de integracao.
 *
 * listForApi()      — paginado, retorna LengthAwarePaginator de Rat (consumo geral)
 * listForPowerBI()  — flat array desnormalizado por recurso x envolvido (Power BI)
 * findById()        — busca Rat por UUID
 */
class RatExportBIService
{
    public function __construct(
        private readonly RatRepositoryInterface $repository,
    ) {}

    /**
     * Lista paginada de RATs para consumo geral da API.
     */
    public function listForApi(RatFilterDTO $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }

    /**
     * Retorna array flat desnormalizado — uma linha por recurso x envolvido.
     * Quando nao ha recursos ou envolvidos, gera uma linha com nulls nos campos ausentes.
     */
    public function listForPowerBI(RatFilterDTO $filters): array
    {
        $allFilters = new RatFilterDTO(
            protocolo:  $filters->protocolo,
            status:     $filters->status,
            ano:        $filters->ano,
            municipio:  $filters->municipio,
            dataInicio: $filters->dataInicio,
            dataFim:    $filters->dataFim,
            perPage:    9999,
        );

        $rats = $this->repository->paginate($allFilters)->items();

        $rows = [];
        foreach ($rats as $rat) {
            $rows = array_merge($rows, $this->flattenRat($rat));
        }

        return $rows;
    }

    /**
     * Busca um RAT por UUID. Retorna null se nao encontrado.
     */
    public function findById(string $id): ?Rat
    {
        return $this->repository->findById($id);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function flattenRat(Rat $rat): array
    {
        $base       = $this->buildBaseRow($rat);
        $recursos   = $rat->recursos   ?? [];
        $envolvidos = $rat->envolvidos ?? [];

        if (empty($recursos) && empty($envolvidos)) {
            return [$this->mergeRow($base, null, null)];
        }

        if (empty($recursos)) {
            return array_map(fn($e) => $this->mergeRow($base, null, $e), $envolvidos);
        }

        if (empty($envolvidos)) {
            return array_map(fn($r) => $this->mergeRow($base, $r, null), $recursos);
        }

        $rows = [];
        foreach ($recursos as $recurso) {
            foreach ($envolvidos as $envolvido) {
                $rows[] = $this->mergeRow($base, $recurso, $envolvido);
            }
        }
        return $rows;
    }

    private function buildBaseRow(Rat $rat): array
    {
        $dg = $rat->dados_gerais ?? [];
        $lo = $rat->local        ?? [];
        $en = $rat->endereco     ?? [];
        $co = $rat->comunicacao  ?? [];

        return [
            'rat_id'                             => $rat->id,
            'protocolo'                          => $rat->protocolo,
            'status'                             => $rat->status,
            'tem_vistoria'                       => $rat->tem_vistoria,
            'created_at'                         => $rat->created_at?->toIso8601String(),
            'updated_at'                         => $rat->updated_at?->toIso8601String(),

            'dados_gerais_data_fato'             => $dg['data_fato']              ?? null,
            'dados_gerais_data_inicio_atividade' => $dg['data_inicio_atividade']  ?? null,
            'dados_gerais_data_termino_atividade'=> $dg['data_termino_atividade'] ?? null,
            'dados_gerais_nat_cobrade_id'        => $dg['nat_cobrade_id']         ?? null,
            'dados_gerais_nat_nome_operacao'     => $dg['nat_nome_operacao']      ?? null,

            'local_pais_id'                      => $lo['pais_id']    ?? null,
            'local_uf'                           => $lo['uf']         ?? null,
            'local_municipio'                    => $lo['municipio']  ?? null,

            'endereco_cep'                       => $en['cep']              ?? null,
            'endereco_logradouro'                => $en['logradouro']       ?? null,
            'endereco_numero'                    => $en['numero']           ?? null,
            'endereco_bairro'                    => $en['bairro']           ?? null,
            'endereco_complemento'               => $en['complemento']      ?? null,
            'endereco_tipo_localizacao'          => $en['tipo_localizacao'] ?? null,
            'endereco_latitude'                  => $en['latitude']         ?? null,
            'endereco_longitude'                 => $en['longitude']        ?? null,

            'comunicacao_tipo_solicitacao'       => $co['tipo_solicitacao'] ?? null,
            'comunicacao_data'                   => $co['data_comunicacao'] ?? null,
            'comunicacao_nome_solicitante'       => $co['nome_solicitante'] ?? null,
        ];
    }

    private function mergeRow(array $base, ?array $recurso, ?array $envolvido): array
    {
        return array_merge(
            $base,
            $this->buildRecursoColumns($recurso),
            $this->buildEnvolvidoColumns($envolvido),
        );
    }

    private function buildRecursoColumns(?array $r): array
    {
        return [
            'recurso_tipo_recurso'      => $r['tipo_recurso']      ?? null,
            'recurso_categoria'         => $r['categoria']         ?? null,
            'recurso_orgao_responsavel' => $r['orgao_responsavel'] ?? null,
            'recurso_identificacao'     => $r['identificacao']     ?? null,
            'recurso_condutor'          => $r['condutor']          ?? null,
            'recurso_descricao'         => $r['descricao']         ?? null,
            'recurso_data_saida'        => $r['data_saida']        ?? null,
            'recurso_data_chegada'      => $r['data_chegada']      ?? null,
            'recurso_km_percorrido'     => $r['km_percorrido']     ?? null,
            'recurso_local_origem'      => $r['local_origem']      ?? null,
            'recurso_local_destino'     => $r['local_destino']     ?? null,
        ];
    }

    private function buildEnvolvidoColumns(?array $e): array
    {
        return [
            'envolvido_tipo_pessoa'     => $e['tipo_pessoa']     ?? null,
            'envolvido_cpf'             => $e['cpf']             ?? null,
            'envolvido_nome'            => $e['nome']            ?? null,
            'envolvido_nome_social'     => $e['nome_social']     ?? null,
            'envolvido_data_nascimento' => $e['data_nascimento'] ?? null,
            'envolvido_idade_aparente'  => $e['idade_aparente']  ?? null,
            'envolvido_sexo'            => $e['sexo']            ?? null,
            'envolvido_nome_mae'        => $e['nome_mae']        ?? null,
            'envolvido_nome_pai'        => $e['nome_pai']        ?? null,
            'envolvido_ocupacao'        => $e['ocupacao']        ?? null,
            'envolvido_escolaridade'    => $e['escolaridade']    ?? null,
            'envolvido_municipio'       => $e['municipio']       ?? null,
            'envolvido_uf'              => $e['uf']              ?? null,
        ];
    }
}
