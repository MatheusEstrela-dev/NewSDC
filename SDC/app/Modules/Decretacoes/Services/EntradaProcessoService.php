<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Modules\Decretacoes\DTO\ProcessoRequestDTO;
use App\Modules\Decretacoes\Models\DecretoMunicipio;
use App\Modules\Decretacoes\Models\EntradaDecreto;
use App\Modules\Decretacoes\Models\EntradaDesastre;
use App\Modules\Decretacoes\Models\Processo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Service principal de EntradaProcesso - Gerencia CRUD e sincronizacao de dados.
 *
 * FLUXO DE DADOS:
 *   Controller -> EntradaProcessoService -> Models -> Banco
 *   Banco -> Models -> EntradaProcessoService -> Controller
 *
 * RESPONSABILIDADES:
 * - CRUD de Processos (criar, atualizar, deletar)
 * - Sincronizacao de municipios e informacoes de decreto
 * - Delegacao para services especializados (Query, Stats, Export)
 * - Integracao com Hexagon
 *
 * SERVICES DELEGADOS:
 * - ProcessoQueryService: Consultas e filtros
 * - ProcessoStatsService: Estatisticas do dashboard
 * - ProcessoExportService: Exportacao para PowerBI
 * - HexagonIntegrationService: Integracao externa
 */
class EntradaProcessoService
{
    public function __construct(
        private readonly HexagonIntegrationService $hexagonService,
        private readonly ProcessoQueryService $queryService,
        private readonly ProcessoStatsService $statsService,
        private readonly ProcessoExportService $exportService
    ) {
    }

    // =========================================================================
    // CRUD E SINCRONIZACAO
    // =========================================================================

    /**
     * Lista processos com filtros e paginacao.
     *
     * FLUXO: Filtros -> ProcessoQueryService -> LengthAwarePaginator
     *
     * @param array $filters Filtros de busca
     * @param int $perPage Itens por pagina
     * @return LengthAwarePaginator Lista paginada
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->queryService->list($filters, $perPage);
    }

    /**
     * Busca processo por ID com relacionamentos.
     *
     * FLUXO: ID -> Processo (com logs e municipios) -> Model ou null
     *
     * @param int $id ID do processo
     * @return Processo|null Processo encontrado ou null
     */
    public function findById(int $id): ?Processo
    {
        $processo = Processo::with(['logs', 'municipios'])->find($id);
        
        if ($processo) {
            $processo->totais = $this->queryService->calculateTotaisForProcesso($id);
        }
        
        return $processo;
    }

    /**
     * Cria novo processo.
     *
     * FLUXO: ProcessoRequestDTO -> Transacao -> Processo + Municipios + Decretos
     *
     * DESTINO: Tabelas dec_entrada_processos, dec_decreto_municipios, dec_entrada_decretos
     *
     * @param ProcessoRequestDTO $dto Dados do formulario
     * @return Processo Processo criado
     */
    public function createProcesso(ProcessoRequestDTO $dto): Processo
    {
        return DB::transaction(function () use ($dto) {
            $processo = Processo::create($dto->allData);
            $this->syncMunicipalities($processo, $dto->municipios);
            $this->syncInformacoesDecreto($processo, $dto->informacoesDecreto);

            return $processo;
        });
    }

    /**
     * Atualiza processo existente.
     *
     * FLUXO: ProcessoRequestDTO -> Transacao -> Update + Sync -> Hexagon (se alterado)
     *
     * @param ProcessoRequestDTO $dto Dados do formulario
     * @param int $id ID do processo
     * @return Processo Processo atualizado
     */
    public function updateProcesso(ProcessoRequestDTO $dto, int $id): Processo
    {
        $processo = DB::transaction(function () use ($dto, $id) {
            $processo = Processo::findOrFail($id);
            $processo->fill($dto->allData);
            $this->syncMunicipalities($processo, $dto->municipios);
            $this->syncInformacoesDecreto($processo, $dto->informacoesDecreto);
            $processo->save();

            // Sincroniza com Hexagon se houve alteracao
            if ($processo->wasChanged()) {
                $this->hexagonService->updateHexagonService($processo);
            }

            return $processo;
        });
    }

    /**
     * Remove processo (soft delete).
     *
     * @param int $id ID do processo
     * @return array Resultado com success e message
     */
    public function delete(int $id): array
    {
        $processo = Processo::find($id);

        if (!$processo) {
            return ['success' => false, 'message' => 'Processo nao encontrado.'];
        }

        if (!$processo->podeSerExcluido()) {
            return [
                'success' => false,
                'message' => 'Este processo nao pode ser excluido. Somente processos com status "Registro" podem ser removidos.'
            ];
        }

        $processo->delete();

        return ['success' => true, 'message' => 'Processo removido com sucesso!'];
    }

    // =========================================================================
    // METODOS PROXY PARA SERVICES ESPECIALIZADOS
    // =========================================================================

    /**
     * Obtem estatisticas do dashboard.
     *
     * DELEGADO PARA: ProcessoStatsService
     *
     * @param array $filters Filtros opcionais 
     * @return array Estatisticas formatadas
     */
    public function getStatistics(array $filters = []): array
    {
        return $this->statsService->getDashboardStatistics($filters);
    }

    /**
     * Obtem opcoes de filtro para formularios.
     *
     * DELEGADO PARA: ProcessoQueryService
     *
     * @return array Opcoes de filtro (tipos, status, etc)
     */
    public function getFilterOptions(): array
    {
        return $this->queryService->getFilterOptions();
    }

    /**
     * Obtem resumo dos filtros ativos.
     *
     * DELEGADO PARA: ProcessoQueryService
     *
     * @param \Illuminate\Http\Request $request Request com filtros
     * @return array Filtros ativos com labels
     */
    public function getActiveFiltersSummary(\Illuminate\Http\Request $request): array
    {
        return $this->queryService->getActiveFiltersSummary($request);
    }

    /**
     * Verifica se ha filtros ativos.
     *
     * DELEGADO PARA: ProcessoQueryService
     *
     * @param \Illuminate\Http\Request $request Request com filtros
     * @return bool True se ha filtros ativos
     */
    public function hasActiveFilters(\Illuminate\Http\Request $request): bool
    {
        return $this->queryService->hasActiveFilters($request);
    }

    /**
     * Obtem processos filtrados para exibicao.
     *
     * DELEGADO PARA: ProcessoQueryService
     *
     * @param \Illuminate\Http\Request $request Request com filtros
     * @return array Dados formatados com paginacao e filtros
     */
    public function getFilteredProcessos(\Illuminate\Http\Request $request): array
    {
        return $this->queryService->getFilteredProcessos($request);
    }

    /**
     * Obtem dados normalizados para PowerBI.
     *
     * DELEGADO PARA: ProcessoExportService
     *
     * DESTINO: Integracao PowerBI (dashboard externo)
     *
     * @param \Illuminate\Http\Request $request Request com filtros
     * @return array Dados normalizados para BI
     */
    public function getNormalizedDataForPowerBI(\Illuminate\Http\Request $request): array
    {
        return $this->exportService->getNormalizedDataForPowerBI($request);
    }

    /**
     * Enriquece processos do paginador com totais de desastres (batch).
     *
     * DELEGADO PARA: ProcessoQueryService
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator Paginador com processos
     */
    public function enrichWithTotais(\Illuminate\Pagination\LengthAwarePaginator $paginator): void
    {
        $this->queryService->enrichPaginatorWithTotais($paginator);
    }

    /**
     * Carrega municipios com dados de desastres para edicao.
     *
     * DELEGADO PARA: ProcessoQueryService
     *
     * @param Processo $processo Processo pai
     * @return array Municipios com dados de desastres hierarquicos
     */
    public function loadMunicipiosWithDesastreData(Processo $processo): array
    {
        return $this->queryService->loadMunicipiosWithDesastreData($processo)->toArray();
    }

    // =========================================================================
    // HELPERS DE SINCRONIZACAO (PRIVADOS)
    // =========================================================================

    /**
     * Sincroniza municipios do processo.
     *
     * LOGICA:
     * 1. Compara municipios atuais com novos
     * 2. Remove os que nao estao mais na lista (+ desastres associados)
     * 3. Adiciona os novos (com protocolo FIDE se municipio unico)
     *
     * DESTINO: Tabelas dec_decreto_municipios, dec_entrada_desastres
     *
     * @param Processo $processo Processo pai
     * @param array $municipios IDs dos municipios
     */
    private function syncMunicipalities(Processo $processo, array $municipios): void
    {
        $municipios = array_map('intval', $municipios);
        $currentMunicipios = DecretoMunicipio::where('entrada_processos_id', $processo->id)->pluck('municipio_id')->toArray();
        $municipiosToRemove = array_diff($currentMunicipios, $municipios);
        $municipiosToAdd = array_diff($municipios, $currentMunicipios);

        // Remove municipios e seus desastres associados
        if (!empty($municipiosToRemove)) {
            DecretoMunicipio::where('entrada_processos_id', $processo->id)->whereIn('municipio_id', $municipiosToRemove)->delete();
            EntradaDesastre::where('entrada_processo_id', $processo->id)->whereIn('municipio_id', $municipiosToRemove)->delete();
        }

        // Adiciona novos municipios
        if (!empty($municipiosToAdd)) {
            // Protocolo FIDE so e atribuido automaticamente se for municipio unico
            $protocoloFide = (count($municipios) === 1) ? ($processo->n_protocolo_fide ?? null) : null;
            foreach ($municipiosToAdd as $municipioId) {
                DecretoMunicipio::create(['entrada_processos_id' => $processo->id, 'municipio_id' => $municipioId, 'n_protocolo_fide' => $protocoloFide]);
            }
        }
    }

    /**
     * Sincroniza informacoes de decreto.
     *
     * LOGICA:
     * 1. Remove todos os decretos existentes
     * 2. Recria com os novos dados (JSON decodificado)
     *
     * DESTINO: Tabela dec_entrada_decretos
     *
     * @param Processo $processo Processo pai
     * @param string|null $informacoesDecreto JSON com informacoes de decreto
     */
    private function syncInformacoesDecreto(Processo $processo, ?string $informacoesDecreto): void
    {
        // Remove existentes
        EntradaDecreto::where('entrada_processos_id', $processo->id)->delete();

        // Recria com novos dados
        if ($informacoesDecreto) {
            $infDecretos = json_decode($informacoesDecreto);
            if ($infDecretos) {
                foreach ($infDecretos as $infDecreto) {
                    EntradaDecreto::create(['entrada_processos_id' => $processo->id, 'decreto_categoria_id' => $infDecreto->id, 'observacao' => $infDecreto->observacao]);
                }
            }
        }
    }
}
