# Design: Refatoracao Modulo Decretacoes

**Data:** 2026-03-02
**Status:** Aprovado
**Objetivo:** Consolidar arquivos do modulo Decretacoes seguindo Clean Architecture e padrao Request -> DTO -> Controller -> Service -> Model

## Resumo

Reducao de 32 arquivos para 17 arquivos (47% de reducao) atraves de consolidacao estrategica mantendo principios SOLID.

## Estrutura Final

```
app/Modules/Decretacoes/
├── Controllers/
│   └── DecretacoesController.php       # Unificado
├── DTOs/
│   └── DecretacoesDTO.php              # Todos DTOs em 1 arquivo
├── Enums/
│   ├── StatusProcesso.php
│   ├── TipoDecreto.php
│   └── TipoProcesso.php
├── Models/
│   ├── Processo.php
│   ├── ProcessoAnexo.php
│   ├── ProcessoDanosHumanos.php
│   ├── ProcessoDanosMateriais.php
│   ├── ProcessoLog.php
│   └── ProcessoPrejuizo.php
├── Requests/
│   ├── StoreProcessoRequest.php
│   └── UpdateProcessoRequest.php
├── Services/
│   ├── ProcessoService.php             # ~600 linhas
│   ├── DesastreService.php             # ~200 linhas
│   └── HexagonIntegrationService.php   # Mantido (SRP - API externa)
└── DecretacoesServiceProvider.php
```

## Arquivos Removidos/Consolidados

### Pastas Eliminadas
- `Actions/` (3 arquivos) -> absorvidos em DesastreService
- `Support/` (1 arquivo) -> absorvido em DesastreService

### Services Consolidados em ProcessoService
- ProcessoFilterService.php (~96 linhas)
- ProcessoStatisticsService.php (~28 linhas)
- ProcessoSyncService.php (~65 linhas)
- ProcessoExportService.php (~185 linhas)
- EntradaProcessoService.php (~276 linhas)

### DTOs Consolidados em DecretacoesDTO.php
- CampoDTO.php
- ItemDTO.php
- DesastreDTO.php
- CategoriaDTO.php
- MunicipioDesastreDTO.php
- DisasterSubmissionDTO.php
- ProcessoDataDTO.php

### Controllers Unificados
- ProcessoController.php + EntradaProcessoController.php -> DecretacoesController.php

### Requests Unificados
- StoreEntradaProcessoRequest + StoreProcessoRequest -> StoreProcessoRequest
- UpdateEntradaProcessoRequest + UpdateProcessoRequest -> UpdateProcessoRequest

## Design dos Componentes

### ProcessoService (~600 linhas)

Organizado em regioes internas:

```php
class ProcessoService
{
    private HexagonIntegrationService $hexagonService;

    // CRUD
    public function list(array $filters, int $perPage): LengthAwarePaginator
    public function findById(int $id): ?Processo
    public function create(array $data): Processo
    public function update(int $id, array $data): bool
    public function delete(int $id): bool

    // Filtros
    public function getFilteredProcessos(Request $request): array
    public function getFilterOptions(): array
    public function hasActiveFilters(Request $request): bool
    public function getActiveFiltersSummary(Request $request): array
    private function applyFilters(Request $request): Builder

    // Estatisticas
    public function getStatistics(): array
    public function getDashboardStatistics(): array
    private function getVigentesCount(): int

    // Sync
    public function createProcesso(ProcessoDTO $dto): EntradaProcesso
    public function updateProcesso(ProcessoDTO $dto, int $id): EntradaProcesso
    private function syncMunicipalities(EntradaProcesso $processo, array $municipios): void
    private function syncInformacoesDecreto(EntradaProcesso $processo, ?string $info): void

    // Export PowerBI
    public function getNormalizedDataForPowerBI(Request $request): array
    private function buildRow(EntradaProcesso $entrada, $municipio, array $totals, array $danos): array
    private function calculateDesastreTotalsForEntry(EntradaProcesso $entrada): array
    private function calculateDanosHumanos($processoIds): Collection

    // Auxiliares
    public function prepareProcessoForEdit(EntradaProcesso $processo): EntradaProcesso
    public function loadMunicipiosWithDesastreData(EntradaProcesso $processo): Collection
    public function loadInformacoesDecreto(int $processoId): array
    public function getTotalDesastresCountFromEntradas(Collection $entradas): Collection
    public function getPedidoAhData(?string $numeroDecreto): Collection
}
```

### DesastreService (~200 linhas)

```php
class DesastreService
{
    // Principal
    public function processDesastresData(array $data, EntradaProcesso $processo): array

    // Processamento
    private function processSubmission(DesastreSubmissionDTO $dto, EntradaProcesso $processo): array
    private function processMunicipio(MunicipioData $municipio, EntradaProcesso $processo): void
    private function processDesastre(MunicipioData $municipio, DesastreData $desastre, EntradaProcesso $processo): void

    // Sync Protocolo
    private function syncMunicipioProtocolo(MunicipioData $municipio, EntradaProcesso $processo): void

    // Persistencia
    private function persistDesastreCampo(...): void
    private function deduplicate(array $searchCriteria): ?EntradaDesastre
    private function isZeroOrNull(mixed $value): bool

    // Formatacao
    private function formatValue(mixed $value, string $type): mixed
}
```

### DecretacoesDTO.php (~120 linhas)

Todas as classes readonly em um unico arquivo:
- ProcessoDTO
- DesastreSubmissionDTO
- MunicipioData
- CategoriaData
- DesastreData
- ItemData
- CampoData

### DecretacoesController (~180 linhas)

```php
class DecretacoesController extends Controller
{
    public function __construct(
        private readonly ProcessoService $processoService,
        private readonly DesastreService $desastreService
    ) {}

    // Listagem
    public function index(Request $request): Response

    // Detalhes
    public function show(int $id): Response

    // Forms
    public function create(): Response
    public function edit(int $id): Response

    // CRUD
    public function store(StoreProcessoRequest $request): RedirectResponse
    public function update(UpdateProcessoRequest $request, int $id): RedirectResponse
    public function destroy(int $id): RedirectResponse

    // Desastres
    public function storeDesastres(Request $request, int $processoId): JsonResponse

    // Export
    public function exportPowerBI(Request $request): JsonResponse
}
```

## Decisoes de Design

1. **HexagonIntegrationService separado**: Mantem SRP - integracao com API externa isolada facilita testes e manutencao
2. **ProcessoExportService absorvido**: Usuario optou por menos arquivos mesmo com service maior
3. **DTOs em arquivo unico**: Hierarquia coesa em um so lugar facilita navegacao
4. **Controller unificado**: Processo e EntradaProcesso tratados como mesmo dominio

## Fluxo de Dados

```
Request
   |
   v
StoreProcessoRequest (validacao)
   |
   v
DecretacoesController
   |
   +---> ProcessoDTO::fromRequest()
   |
   v
ProcessoService / DesastreService
   |
   v
Model (Processo, EntradaProcesso, etc.)
```

## Proximos Passos

1. Criar plano de implementacao detalhado
2. Implementar refatoracao em etapas
3. Atualizar rotas
4. Atualizar ServiceProvider
5. Remover arquivos obsoletos
