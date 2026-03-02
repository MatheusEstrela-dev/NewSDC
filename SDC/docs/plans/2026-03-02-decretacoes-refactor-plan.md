# Decretacoes Module Refactoring Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Consolidate 32 files to 17 files (47% reduction) in the Decretacoes module following Clean Architecture.

**Architecture:** Request -> DTO -> Controller -> Service -> Model. Absorb Actions/Support into Services, consolidate DTOs into single file, unify Controllers.

**Tech Stack:** Laravel 10+, PHP 8.2+, Inertia.js

**Design Doc:** `docs/plans/2026-03-02-decretacoes-refactor-design.md`

---

## Phase 1: Create Consolidated DTOs

### Task 1: Create DecretacoesDTO.php

**Files:**
- Create: `app/Modules/Decretacoes/DTOs/DecretacoesDTO.php`
- Reference: `app/Modules/Decretacoes/DTOs/ProcessoDataDTO.php`
- Reference: `app/Modules/Decretacoes/DTOs/DisasterSubmissionDTO.php`
- Reference: `app/Modules/Decretacoes/DTOs/MunicipioDesastreDTO.php`
- Reference: `app/Modules/Decretacoes/DTOs/CategoriaDTO.php`
- Reference: `app/Modules/Decretacoes/DTOs/DesastreDTO.php`
- Reference: `app/Modules/Decretacoes/DTOs/ItemDTO.php`
- Reference: `app/Modules/Decretacoes/DTOs/CampoDTO.php`

**Step 1: Create consolidated DTO file**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\DTOs;

use Illuminate\Http\Request;

readonly class ProcessoDTO
{
    public function __construct(
        public array $allData,
        public array $municipios = [],
        public ?string $informacoesDecreto = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            allData: $request->all(),
            municipios: $request->input('municipios', []),
            informacoesDecreto: $request->input('informacoes_decreto'),
        );
    }
}

readonly class CampoData
{
    public function __construct(
        public int $id,
        public string $titulo,
        public string $tipo,
        public mixed $valor,
        public ?int $entradaDesastreId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            titulo: $data['titulo'] ?? '',
            tipo: $data['tipo'] ?? 'text',
            valor: $data['valor'] ?? null,
            entradaDesastreId: isset($data['entrada_desastre_id']) ? (int) $data['entrada_desastre_id'] : null,
        );
    }
}

readonly class ItemData
{
    /** @param CampoData[] $campos */
    public function __construct(
        public int $id,
        public array $campos,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            campos: array_map(fn(array $campo) => CampoData::fromArray($campo), $data['campos'] ?? []),
        );
    }
}

class DesastreData
{
    public ?int $entradaCategoriaDesastreId = null;

    /** @param ItemData[] $items */
    public function __construct(
        public readonly int $id,
        public readonly ?string $descricao,
        public readonly array $items,
    ) {}

    public static function fromArray(array $data): self
    {
        $instance = new self(
            id: (int) $data['id'],
            descricao: $data['descricao'] ?? null,
            items: array_map(fn(array $item) => ItemData::fromArray($item), $data['items'] ?? []),
        );
        $instance->entradaCategoriaDesastreId = isset($data['entrada_categoria_desastre_id'])
            ? (int) $data['entrada_categoria_desastre_id']
            : null;
        return $instance;
    }
}

readonly class CategoriaData
{
    /** @param DesastreData[] $desastres */
    public function __construct(
        public int $id,
        public array $desastres,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            desastres: array_map(fn(array $desastre) => DesastreData::fromArray($desastre), $data['desastres'] ?? []),
        );
    }
}

readonly class MunicipioData
{
    /** @param CategoriaData[] $categorias */
    public function __construct(
        public int $id,
        public ?string $nProtocoloFide,
        public array $categorias,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            nProtocoloFide: $data['n_protocolo_fide'] ?? null,
            categorias: array_map(fn(array $categoria) => CategoriaData::fromArray($categoria), $data['categorias'] ?? []),
        );
    }
}

readonly class DesastreSubmissionDTO
{
    /** @param MunicipioData[] $municipios */
    public function __construct(
        public array $municipios,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            municipios: array_map(fn(array $municipio) => MunicipioData::fromArray($municipio), $data['municipios'] ?? []),
        );
    }
}
```

**Step 2: Verify syntax**

Run: `php -l app/Modules/Decretacoes/DTOs/DecretacoesDTO.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add app/Modules/Decretacoes/DTOs/DecretacoesDTO.php
git commit -m "feat(decretacoes): add consolidated DecretacoesDTO file"
```

---

## Phase 2: Create DesastreService

### Task 2: Create DesastreService.php

**Files:**
- Create: `app/Modules/Decretacoes/Services/DesastreService.php`
- Reference: `app/Modules/Decretacoes/Services/DesastreDataService.php`
- Reference: `app/Modules/Decretacoes/Actions/ProcessDisasterSubmissionAction.php`
- Reference: `app/Modules/Decretacoes/Actions/SyncMunicipioProtocoloAction.php`
- Reference: `app/Modules/Decretacoes/Actions/PersistDesastreCampoAction.php`
- Reference: `app/Modules/Decretacoes/Support/ValueFormatter.php`

**Step 1: Create consolidated DesastreService**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Models\Decreto\DecretoMunicipio;
use App\Models\Decreto\EntradaCategoriaDesastre;
use App\Models\Decreto\EntradaDesastre;
use App\Models\Decreto\EntradaProcesso;
use App\Modules\Decretacoes\DTOs\CampoData;
use App\Modules\Decretacoes\DTOs\DesastreData;
use App\Modules\Decretacoes\DTOs\DesastreSubmissionDTO;
use App\Modules\Decretacoes\DTOs\ItemData;
use App\Modules\Decretacoes\DTOs\MunicipioData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DesastreService
{
    public function processDesastresData(array $data, EntradaProcesso $processo): array
    {
        $dto = DesastreSubmissionDTO::fromArray($data);
        return $this->processSubmission($dto, $processo);
    }

    private function processSubmission(DesastreSubmissionDTO $dto, EntradaProcesso $processo): array
    {
        try {
            DB::beginTransaction();

            foreach ($dto->municipios as $municipioData) {
                $this->processMunicipio($municipioData, $processo);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Dados de desastres processados com sucesso',
            ];
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error processing disaster data', [
                'error' => $e->getMessage(),
                'processo_id' => $processo->id,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao processar dados de desastres: ' . $e->getMessage(),
            ];
        }
    }

    private function processMunicipio(MunicipioData $municipioData, EntradaProcesso $processo): void
    {
        $this->syncMunicipioProtocolo($municipioData, $processo);

        foreach ($municipioData->categorias as $categoriaData) {
            foreach ($categoriaData->desastres as $desastreData) {
                $this->processDesastre($municipioData, $desastreData, $processo);
            }
        }
    }

    private function syncMunicipioProtocolo(MunicipioData $municipioData, EntradaProcesso $processo): void
    {
        if ($municipioData->nProtocoloFide === null) {
            return;
        }

        DecretoMunicipio::updateOrCreate(
            [
                'entrada_processos_id' => $processo->id,
                'municipio_id' => $municipioData->id,
            ],
            [
                'n_protocolo_fide' => $municipioData->nProtocoloFide,
            ]
        );
    }

    private function processDesastre(MunicipioData $municipioData, DesastreData $desastreData, EntradaProcesso $processo): void
    {
        $entradaCategoria = EntradaCategoriaDesastre::updateOrCreate(
            [
                'municipio_id'        => $municipioData->id,
                'categoria_id'        => $desastreData->id,
                'entrada_processo_id' => $processo->id,
            ],
            [
                'descricao' => $desastreData->descricao,
            ]
        );

        $desastreData->entradaCategoriaDesastreId = $entradaCategoria->id;

        foreach ($desastreData->items as $itemData) {
            foreach ($itemData->campos as $campoData) {
                $this->persistDesastreCampo($municipioData, $desastreData, $itemData, $campoData, $processo);
            }
        }
    }

    private function persistDesastreCampo(
        MunicipioData $municipioData,
        DesastreData $desastreData,
        ItemData $itemData,
        CampoData $campoData,
        EntradaProcesso $processo
    ): void {
        if ($campoData->valor === null) {
            return;
        }

        $formattedValue = $this->formatValue($campoData->valor, $campoData->tipo);

        $searchCriteria = [
            'municipio_id'                  => $municipioData->id,
            'item_campo_id'                 => $campoData->id,
            'entrada_processo_id'           => $processo->id,
            'item_id'                       => $itemData->id,
            'entrada_categoria_desastre_id' => $desastreData->entradaCategoriaDesastreId
        ];

        $values = $searchCriteria + [
            'campo_titulo' => $campoData->titulo,
            'valor'        => $formattedValue,
        ];

        EntradaDesastre::updateOrCreate(
            ['id' => $campoData->entradaDesastreId],
            $values
        );

        $entradaDesastre = $this->deduplicate($searchCriteria);

        if ($entradaDesastre) {
            DecretoMunicipio::where('entrada_processos_id', $processo->id)
                ->where('municipio_id', $municipioData->id)
                ->update(['updated_at' => $entradaDesastre->updated_at]);
        }
    }

    private function deduplicate(array $searchCriteria): ?EntradaDesastre
    {
        $entradas = EntradaDesastre::where($searchCriteria)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        if ($entradas->count() <= 1) {
            return $entradas->first();
        }

        $entradasNaoZeradas = $entradas->reject(fn($entrada) => $this->isZeroOrNull($entrada->valor));
        $entradaMantida = $entradasNaoZeradas->first() ?? $entradas->first();

        $entradas->filter(fn($entrada) => $entrada->id !== $entradaMantida->id)
            ->each(fn($duplicado) => $duplicado->delete());

        return $entradaMantida;
    }

    private function isZeroOrNull(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        $trimmed = trim((string) $value);
        return is_numeric($trimmed) && (float) $trimmed === 0.0;
    }

    private function formatValue(mixed $value, string $type): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($type) {
            'number' => (int) str_replace('.', '', (string) $value),
            'currency' => (float) str_replace(',', '.', str_replace('.', '', (string) $value)),
            default => $value,
        };
    }
}
```

**Step 2: Verify syntax**

Run: `php -l app/Modules/Decretacoes/Services/DesastreService.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add app/Modules/Decretacoes/Services/DesastreService.php
git commit -m "feat(decretacoes): add consolidated DesastreService"
```

---

## Phase 3: Consolidate ProcessoService

### Task 3: Create consolidated ProcessoService.php

**Files:**
- Modify: `app/Modules/Decretacoes/Services/ProcessoService.php`
- Reference: `app/Modules/Decretacoes/Services/EntradaProcessoService.php`
- Reference: `app/Modules/Decretacoes/Services/ProcessoFilterService.php`
- Reference: `app/Modules/Decretacoes/Services/ProcessoStatisticsService.php`
- Reference: `app/Modules/Decretacoes/Services/ProcessoSyncService.php`
- Reference: `app/Modules/Decretacoes/Services/ProcessoExportService.php`

**Step 1: Backup original ProcessoService**

Run: `cp app/Modules/Decretacoes/Services/ProcessoService.php app/Modules/Decretacoes/Services/ProcessoService.php.bak`

**Step 2: Replace ProcessoService with consolidated version**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Http\Filters\EntradaProcessoFilter;
use App\Http\Resources\Decretacoes\ProcessosIndexResource;
use App\Models\Decreto\DecretoMunicipio;
use App\Models\Decreto\DesastreGrupo;
use App\Models\Decreto\EntradaDecreto;
use App\Models\Decreto\EntradaDesastre;
use App\Models\Decreto\EntradaProcesso;
use App\Modules\Decretacoes\DTOs\ProcessoDTO;
use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Shared\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProcessoService extends BaseService
{
    public function __construct(
        private readonly HexagonIntegrationService $hexagonService
    ) {}

    // ========== CRUD (Processo Model) ==========

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Processo::query()->with(['anexos', 'logs', 'municipios']);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('numero_processo', 'like', "%{$filters['search']}%")
                  ->orWhere('municipio', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['tipo_decreto'])) {
            $query->where('tipo_decreto', $filters['tipo_decreto']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?Processo
    {
        return Processo::with(['anexos', 'danosHumanos', 'danosMateriais', 'prejuizos', 'logs'])->find($id);
    }

    public function create(array $data): Processo
    {
        return Processo::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $processo = Processo::find($id);
        if (!$processo) {
            return false;
        }
        return $processo->update($data);
    }

    public function delete(int $id): bool
    {
        $processo = Processo::find($id);
        if (!$processo) {
            return false;
        }
        return $processo->delete();
    }

    // ========== Filter Methods ==========

    public function getFilteredProcessos(Request $request): array
    {
        $query = $this->applyFilters($request);
        $data = (new ProcessosIndexResource($query))->toArray();
        $data['filter_options'] = $this->getFilterOptions();
        return $data;
    }

    public function getFilterOptions(): array
    {
        return EntradaProcessoFilter::getFilterOptions();
    }

    public function hasActiveFilters(Request $request): bool
    {
        $filterParams = [
            'search', 'data_entrada', 'data_entrada_inicio', 'data_entrada_fim', 'processo', 'reconhecimento',
            'analista', 'situacao_anormalidade', 'data_decreto_inicio',
            'data_decreto_fim', 'vigencia_status', 'tipo_desastre_id', 'municipio_id', 'n_protocolo_fide'
        ];
        return $request->hasAny($filterParams);
    }

    public function getActiveFiltersSummary(Request $request): array
    {
        $activeFilters = [];
        $filterLabels = [
            'search' => 'Busca',
            'data_entrada' => 'Data Entrada',
            'data_entrada_inicio' => 'Data Entrada Inicio',
            'data_entrada_fim' => 'Data Entrada Fim',
            'processo' => 'Tipo Processo',
            'reconhecimento' => 'Status',
            'analista' => 'Analista',
            'situacao_anormalidade' => 'Situacao',
            'data_decreto_inicio' => 'Data Decreto Inicio',
            'data_decreto_fim' => 'Data Decreto Fim',
            'vigencia_status' => 'Vigencia',
            'tipo_desastre_id' => 'Tipo Desastre',
            'municipio_id' => 'Municipio',
            'n_protocolo_fide' => 'Protocolo FIDE'
        ];

        $cobrade = collect(include app_path('Enums/classificacao_desastres.php'));

        foreach ($filterLabels as $param => $label) {
            if ($request->filled($param)) {
                $value = $request->input($param);
                $entry = [
                    'param' => $param,
                    'label' => $label,
                    'value' => $value,
                ];

                if ($param === 'tipo_desastre_id') {
                    $match = $cobrade->firstWhere('id', (int) $value);
                    if ($match) {
                        $labelParts = array_filter([
                            $match['cobrade'] ?? null,
                            $match['a_definicao'] ?? $match['subtipo'] ?? $match['tipo'] ?? $match['subgrupo'] ?? $match['grupo'] ?? null,
                        ]);
                        $entry['display_value'] = implode(' - ', $labelParts);
                    }
                }

                $activeFilters[] = $entry;
            }
        }

        return $activeFilters;
    }

    private function applyFilters(Request $request): Builder
    {
        $query = EntradaProcesso::query();
        $filter = new EntradaProcessoFilter($request);
        $filteredQuery = $filter->apply($query);
        $filteredQuery->orderBy('data_entrada', 'desc');
        return $filteredQuery;
    }

    // ========== Statistics Methods ==========

    public function getStatistics(): array
    {
        return [
            'total' => Processo::count(),
            'em_analise' => Processo::where('status', 'em_analise')->count(),
            'aprovados' => Processo::where('status', 'aprovado')->count(),
            'rejeitados' => Processo::where('status', 'rejeitado')->count(),
        ];
    }

    public function getDashboardStatistics(): array
    {
        return [
            'total_processos' => EntradaProcesso::count(),
            'processos_vigentes' => $this->getVigentesCount(),
            'processos_municipais' => EntradaProcesso::where('processo', 'MUNICIPAL')->count(),
            'processos_estaduais' => EntradaProcesso::where('processo', 'ESTADUAL')->count(),
        ];
    }

    private function getVigentesCount(): int
    {
        return EntradaProcesso::where(function ($q) {
            $q->whereNull('data_publicacao_mg')
              ->orWhereRaw('DATE_ADD(data_publicacao_mg, INTERVAL prazo_vigencia DAY) >= CURDATE()');
        })->count();
    }

    // ========== Sync Methods ==========

    public function createProcesso(ProcessoDTO $dto): EntradaProcesso
    {
        return DB::transaction(function () use ($dto) {
            $processo = EntradaProcesso::create($dto->allData);
            $this->syncMunicipalities($processo, $dto->municipios);
            $this->syncInformacoesDecreto($processo, $dto->informacoesDecreto);
            return $processo;
        });
    }

    public function updateProcesso(ProcessoDTO $dto, int $id): EntradaProcesso
    {
        return DB::transaction(function () use ($dto, $id) {
            $processo = EntradaProcesso::findOrFail($id);
            $processo->fill($dto->allData);
            $this->syncMunicipalities($processo, $dto->municipios);
            $this->syncInformacoesDecreto($processo, $dto->informacoesDecreto);
            $processo->save();

            if ($processo->wasChanged()) {
                $this->hexagonService->updateHexagonService($processo);
            }

            return $processo;
        });
    }

    private function syncMunicipalities(EntradaProcesso $processo, array $municipios): void
    {
        $municipios = array_map('intval', $municipios);
        $currentMunicipios = DecretoMunicipio::where('entrada_processos_id', $processo->id)
            ->pluck('municipio_id')
            ->toArray();

        $municipiosToRemove = array_diff($currentMunicipios, $municipios);
        $municipiosToAdd = array_diff($municipios, $currentMunicipios);

        if (!empty($municipiosToRemove)) {
            DecretoMunicipio::where('entrada_processos_id', $processo->id)
                ->whereIn('municipio_id', $municipiosToRemove)
                ->delete();

            EntradaDesastre::where('entrada_processo_id', $processo->id)
                ->whereIn('municipio_id', $municipiosToRemove)
                ->delete();
        }

        if (!empty($municipiosToAdd)) {
            $protocoloFide = (count($municipios) === 1) ? ($processo->n_protocolo_fide ?? null) : null;

            foreach ($municipiosToAdd as $municipioId) {
                DecretoMunicipio::create([
                    'entrada_processos_id' => $processo->id,
                    'municipio_id' => $municipioId,
                    'n_protocolo_fide' => $protocoloFide
                ]);
            }
        }
    }

    private function syncInformacoesDecreto(EntradaProcesso $processo, ?string $informacoesDecreto): void
    {
        EntradaDecreto::where('entrada_processos_id', $processo->id)->delete();

        if ($informacoesDecreto) {
            $infDecretos = json_decode($informacoesDecreto);
            if ($infDecretos) {
                foreach ($infDecretos as $infDecreto) {
                    EntradaDecreto::create([
                        'entrada_processos_id' => $processo->id,
                        'decreto_categoria_id' => $infDecreto->id,
                        'observacao' => $infDecreto->observacao
                    ]);
                }
            }
        }
    }

    // ========== Export PowerBI Methods ==========

    public function getNormalizedDataForPowerBI(Request $request): array
    {
        $query = $this->applyFilters($request);

        if ($request->input('include_deleted', false)) {
            $query->withTrashed();
        }

        $entradas = $query->get();
        $normalizedData = [];

        foreach ($entradas as $entrada) {
            $entrada->load(['municipios', 'desastres']);
            $desastreTotals = $this->calculateDesastreTotalsForEntry($entrada);

            if ($entrada->municipios->isEmpty()) {
                $normalizedData[] = $this->buildRow($entrada, null, [], []);
                continue;
            }

            foreach ($entrada->municipios as $municipio) {
                $municipioTotals = $desastreTotals['por_municipio'][$municipio->id] ?? [];
                $danosHumanos = $desastreTotals['danos_humanos_por_municipio'][$municipio->id] ?? [];
                $normalizedData[] = $this->buildRow($entrada, $municipio, $municipioTotals, $danosHumanos);
            }
        }

        return $normalizedData;
    }

    private function buildRow(EntradaProcesso $entrada, $municipio, array $municipioTotals, array $danosHumanos): array
    {
        $row = [
            'id' => $entrada->id,
            'uf' => 'MG',
            'municipio' => $municipio ? ($municipio->p_nome ?? $municipio->nome) : null,
            'codigo_ibge' => $municipio->Codmundv ?? null,
            'macroregiao' => $municipio->macroregiao ?? null,
            'latitude' => $municipio->latitude ?? null,
            'longitude' => $municipio->longitude ?? null,
            'latitude_dec' => $municipio->latitude_dec ?? null,
            'longitude_dec' => $municipio->longitude_dec ?? null,
            'data_registro' => $entrada->data_entrada,
            'data_criacao' => $entrada->created_at,
            'deletado' => $entrada->trashed(),
            'data_delecao' => $entrada->deleted_at,
            'protocolo' => $entrada->n_protocolo_fide,
            'cobrade' => $entrada->tipo_desastre_cobrade,
            'tipo_desastre' => $entrada->tipo_desastre_nome,
            'status' => $entrada->reconhecimento,
            'data_fato' => $entrada->data_ocorrencia_desastre,
            'data_decreto_municipal' => $entrada->data_decreto_municipal,
            'data_publicacao_mg' => $entrada->data_publicacao_mg,
            'prazo_vigencia_dias' => $entrada->prazo_vigencia,
            'data_vencimento' => $entrada->data_vencimento,
            'dias_restantes' => $entrada->dias_restantes,
            'tipo_decreto' => $this->mapearTipoDecreto($entrada->situacao_anormalidade),
            'processo' => $entrada->processo,
            'analista' => $entrada->analista,
            'obitos' => $danosHumanos['obitos'] ?? 0,
            'feridos' => $danosHumanos['feridos'] ?? 0,
            'desalojados' => $danosHumanos['desalojados'] ?? 0,
            'desabrigados' => $danosHumanos['desabrigados'] ?? 0,
            'desaparecidos' => $danosHumanos['desaparecidos'] ?? 0,
            'outros_afetados' => $danosHumanos['outros_afetados'] ?? 0,
        ];

        $row['danos_humanos_quantidade'] = array_sum([
            $row['obitos'], $row['feridos'], $row['desalojados'],
            $row['desabrigados'], $row['desaparecidos'], $row['outros_afetados']
        ]);

        $row['danos_materiais_danificadas'] = $municipioTotals['DANOS MATERIAIS']['Quantidades danificadas'] ?? 0;
        $row['danos_materiais_destruidas'] = $municipioTotals['DANOS MATERIAIS']['Quantidades destruidas'] ?? 0;
        $row['danos_materiais_valor'] = $municipioTotals['DANOS MATERIAIS']['Valor (R$)'] ?? 0;
        $row['prejuizos_publicos_valor'] = $municipioTotals['PREJUIZOS ECONOMICOS PUBLICOS']['Valor do prejuizo (R$)'] ?? 0;
        $row['prejuizos_privados_valor'] = $municipioTotals['PREJUIZOS ECONOMICOS PRIVADOS']['Valor do prejuizo (R$)'] ?? 0;

        return $row;
    }

    private function calculateDesastreTotalsForEntry(EntradaProcesso $entrada): array
    {
        $processoIds = collect([$entrada->id]);

        $allTotals = DB::table('dec_entrada_categoria_desastres as ecd')
            ->join('dec_entrada_desastres as ed', 'ecd.id', '=', 'ed.entrada_categoria_desastre_id')
            ->join('dec_desastre_item_campos as dic', 'ed.item_campo_id', '=', 'dic.id')
            ->join('dec_desastre_categorias as dc', 'ecd.categoria_id', '=', 'dc.id')
            ->join('cedec_municipio as m', 'ed.municipio_id', '=', 'm.id')
            ->whereIn('ecd.entrada_processo_id', $processoIds)
            ->whereIn('dic.tipo', ['number', 'currency'])
            ->select(
                'ed.municipio_id',
                'dc.titulo as categoria_titulo',
                'dic.titulo as desastre_campo_titulo',
                'dic.tipo',
                DB::raw('SUM(ed.valor) as total_valor')
            )
            ->groupBy('ed.municipio_id', 'dc.titulo', 'dic.titulo', 'dic.tipo')
            ->get();

        $groupedByMunicipio = $allTotals->groupBy('municipio_id')->map(function ($municipioItems) {
            return $municipioItems->groupBy('categoria_titulo')->map(function ($categoriaItems) {
                return $categoriaItems->keyBy('desastre_campo_titulo')->map(function ($item) {
                    return $item->tipo === 'currency' ? (float) $item->total_valor : (int) $item->total_valor;
                });
            });
        });

        $danosHumanosByMunicipio = $this->calculateDanosHumanos($processoIds);

        return [
            'por_municipio' => $groupedByMunicipio->toArray(),
            'danos_humanos_por_municipio' => $danosHumanosByMunicipio->toArray()
        ];
    }

    private function calculateDanosHumanos($processoIds): Collection
    {
        $danosHumanos = DB::table('dec_entrada_desastres as ed')
            ->join('dec_entrada_categoria_desastres as ecd', 'ed.entrada_categoria_desastre_id', '=', 'ecd.id')
            ->join('dec_desastre_item_campos as dic', 'ed.item_campo_id', '=', 'dic.id')
            ->join('dec_desastre_items as di', 'dic.desastre_item_id', '=', 'di.id')
            ->join('dec_desastre_categorias as dc', 'di.categoria_id', '=', 'dc.id')
            ->whereIn('ecd.entrada_processo_id', $processoIds)
            ->where('dc.id', 1)
            ->whereNull('ed.deleted_at')
            ->select(
                'ed.municipio_id',
                'di.id as item_id',
                'di.titulo as item_titulo',
                DB::raw('CAST(COALESCE(ed.valor, 0) AS UNSIGNED) as valor_numerico')
            )
            ->get();

        return $danosHumanos->groupBy('municipio_id')->map(function ($municipioItems) {
            $result = [
                'obitos' => 0, 'feridos' => 0, 'desalojados' => 0,
                'desabrigados' => 0, 'desaparecidos' => 0, 'outros_afetados' => 0,
            ];

            foreach ($municipioItems as $item) {
                $itemId = (int) $item->item_id;
                $valor = (int) $item->valor_numerico;

                switch ($itemId) {
                    case 1: $result['obitos'] += $valor; break;
                    case 2: case 3: $result['feridos'] += $valor; break;
                    case 4: $result['desabrigados'] += $valor; break;
                    case 5: $result['desalojados'] += $valor; break;
                    case 6: $result['desaparecidos'] += $valor; break;
                    case 7: $result['outros_afetados'] += $valor; break;
                }
            }

            return $result;
        });
    }

    private function mapearTipoDecreto($situacaoAnormalidade): ?string
    {
        return in_array($situacaoAnormalidade, ['SE', 'ECP'], true) ? $situacaoAnormalidade : null;
    }

    // ========== Auxiliary Methods ==========

    public function prepareProcessoForEdit(EntradaProcesso $processo): EntradaProcesso
    {
        $classificacaoDesastres = collect(include(app_path('Enums/classificacao_desastres.php')))
            ->sortBy('a_definicao')
            ->values();

        if ($processo->tipo_desastre_id) {
            $processo->desastre = $classificacaoDesastres->firstWhere('id', $processo->tipo_desastre_id);
        }

        return $processo;
    }

    public function loadMunicipiosWithDesastreData(EntradaProcesso $processo): Collection
    {
        $processoId = $processo->id;

        return $processo->municipios->transform(function ($municipio) use ($processoId) {
            $municipioId = $municipio->id;

            $decretoMunicipio = DecretoMunicipio::where('entrada_processos_id', $processoId)
                ->where('municipio_id', $municipioId)
                ->first();

            $municipio->n_protocolo_fide = $decretoMunicipio?->n_protocolo_fide;
            $municipio->updated_at = $decretoMunicipio?->updated_at;

            $municipio->categorias = DesastreGrupo::with(['desastres' => function ($query) use ($processoId, $municipioId) {
                $query->with(['items' => function ($query) use ($processoId, $municipioId) {
                    $query->with(['campos' => function ($query) use ($processoId, $municipioId) {
                        $query->leftjoin('dec_entrada_desastres', function ($join) use ($processoId, $municipioId) {
                            $join->on('dec_desastre_item_campos.id', '=', 'dec_entrada_desastres.item_campo_id')
                                ->where('dec_entrada_desastres.entrada_processo_id', $processoId)
                                ->where('dec_entrada_desastres.municipio_id', $municipioId)
                                ->whereNull('dec_entrada_desastres.deleted_at');
                        })
                        ->select('dec_desastre_item_campos.*', 'dec_entrada_desastres.valor', 'dec_entrada_desastres.id as entrada_desastre_id');
                    }]);
                }])
                ->leftjoin('dec_entrada_categoria_desastres', function ($join) use ($processoId, $municipioId) {
                    $join->on('dec_desastre_categorias.id', '=', 'dec_entrada_categoria_desastres.categoria_id')
                        ->where('dec_entrada_categoria_desastres.entrada_processo_id', $processoId)
                        ->where('dec_entrada_categoria_desastres.municipio_id', $municipioId);
                })
                ->select('dec_desastre_categorias.*', 'dec_entrada_categoria_desastres.descricao', 'dec_entrada_categoria_desastres.id as entrada_categoria_desastre_id');
            }])->get();

            return $municipio;
        });
    }

    public function loadInformacoesDecreto(int $processoId): array
    {
        $entradaDecretos = EntradaDecreto::where('entrada_processos_id', $processoId)->get();
        $informacoesDecreto = [];

        foreach ($entradaDecretos as $entradaDecreto) {
            $informacoesDecreto[] = [
                'id' => $entradaDecreto->decreto_categoria_id,
                'tipo' => '',
                'observacao' => $entradaDecreto->observacao ?? ''
            ];
        }

        return $informacoesDecreto;
    }

    public function getTotalDesastresCountFromEntradas(Collection $entradas): Collection
    {
        $processoIds = $entradas->pluck('id');

        if ($processoIds->isEmpty()) {
            return $entradas;
        }

        $allTotals = DB::table('dec_entrada_categoria_desastres as ecd')
            ->join('dec_entrada_desastres as ed', 'ecd.id', '=', 'ed.entrada_categoria_desastre_id')
            ->join('dec_desastre_item_campos as dic', 'ed.item_campo_id', '=', 'dic.id')
            ->join('dec_desastre_categorias as dc', 'ecd.categoria_id', '=', 'dc.id')
            ->join('cedec_municipio as m', 'ed.municipio_id', '=', 'm.id')
            ->whereIn('ecd.entrada_processo_id', $processoIds)
            ->whereIn('dic.tipo', ['number', 'currency'])
            ->select(
                'ecd.entrada_processo_id',
                'm.p_nome as municipio',
                'dc.titulo as categoria_titulo',
                'dic.titulo as desastre_campo_titulo',
                'dic.tipo',
                DB::raw('SUM(ed.valor) as total_valor')
            )
            ->groupBy('ecd.entrada_processo_id', 'm.p_nome', 'dc.titulo', 'dic.titulo', 'dic.tipo')
            ->get();

        $groupedTotalsPorMunicipio = $allTotals->groupBy('entrada_processo_id')->map(function ($processoItems) {
            return $processoItems->groupBy('municipio')->map(function ($municipioItems) {
                return $municipioItems->groupBy('categoria_titulo')->map(function ($categoriaItems) {
                    return $categoriaItems->keyBy('desastre_campo_titulo')->map(function ($item) {
                        return $item->tipo === 'currency' ? number_format($item->total_valor, 2, ',', '.') : $item->total_valor;
                    });
                });
            });
        });

        $groupedTotals = $allTotals->groupBy('entrada_processo_id')->map(function ($processoItems) {
            return $processoItems->groupBy('categoria_titulo')->map(function ($categoriaItems) {
                return $categoriaItems->groupBy('desastre_campo_titulo')->map(function ($items) {
                    $total = $items->sum('total_valor');
                    $tipo = $items->first()->tipo;
                    return $tipo === 'currency' ? number_format($total, 2, ',', '.') : $total;
                });
            });
        });

        $entradas->each(function ($entrada) use ($groupedTotals, $groupedTotalsPorMunicipio) {
            $entrada->desastre_totals = $groupedTotals->get($entrada->id, collect());
            $entrada->desastre_totals_por_municipio = $groupedTotalsPorMunicipio->get($entrada->id, collect());
        });

        return $entradas;
    }

    public function getPedidoAhData(?string $numeroDecreto): Collection
    {
        if (empty($numeroDecreto)) {
            return collect();
        }

        $tpItemCase = "CASE WHEN LOWER(aju_h_pedido_pedid.tramit) = 'atendido' THEN 'RECEBIDO' ELSE aju_h_pedido_itens.tp_item END";

        $registros = DB::connection('sdc')->table('aju_h_pedido_pedid')
            ->join('aju_h_pedido_itens', 'aju_h_pedido_pedid.id', '=', 'aju_h_pedido_itens.id_pedido')
            ->select(
                'aju_h_pedido_itens.codigo',
                'aju_h_pedido_pedid.tramit as status',
                'aju_h_pedido_itens.descricao_item',
                DB::raw("$tpItemCase AS tp_item"),
                DB::raw('SUM(aju_h_pedido_itens.qtd) AS total_qtd')
            )
            ->where('aju_h_pedido_pedid.num_decreto', $numeroDecreto)
            ->groupBy('aju_h_pedido_itens.codigo', 'aju_h_pedido_itens.descricao_item', DB::raw($tpItemCase), 'aju_h_pedido_pedid.tramit')
            ->orderBy('aju_h_pedido_itens.descricao_item')
            ->get();

        return $registros->groupBy('codigo')->map(function ($items) {
            return $items->map(fn($item) => [
                'codigo' => $item->codigo,
                'status' => $item->status,
                'descricao_item' => $item->descricao_item,
                'tp_item' => $item->tp_item,
                'total_qtd' => $item->total_qtd,
            ]);
        });
    }
}
```

**Step 3: Verify syntax**

Run: `php -l app/Modules/Decretacoes/Services/ProcessoService.php`
Expected: `No syntax errors detected`

**Step 4: Commit**

```bash
git add app/Modules/Decretacoes/Services/ProcessoService.php
git commit -m "feat(decretacoes): consolidate ProcessoService with Filter, Statistics, Sync, Export"
```

---

## Phase 4: Create Unified Controller

### Task 4: Create DecretacoesController.php

**Files:**
- Create: `app/Modules/Decretacoes/Controllers/DecretacoesController.php`
- Reference: `app/Modules/Decretacoes/Controllers/ProcessoController.php`
- Reference: `app/Modules/Decretacoes/Controllers/EntradaProcessoController.php`

**Step 1: Create unified controller**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Decretacoes\DTOs\ProcessoDTO;
use App\Modules\Decretacoes\Requests\StoreProcessoRequest;
use App\Modules\Decretacoes\Requests\UpdateProcessoRequest;
use App\Modules\Decretacoes\Services\DesastreService;
use App\Modules\Decretacoes\Services\ProcessoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DecretacoesController extends Controller
{
    public function __construct(
        private readonly ProcessoService $processoService,
        private readonly DesastreService $desastreService
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'tipo_decreto']);
        $processos = $this->processoService->list($filters, 15);
        $statistics = $this->processoService->getStatistics();

        return Inertia::render('Decretacoes/ProcessoIndex', [
            'processos' => $processos,
            'statistics' => $statistics,
            'filters' => $filters,
        ]);
    }

    public function show(int $id): Response
    {
        $processo = $this->processoService->findById($id);

        if (!$processo) {
            abort(404, 'Processo nao encontrado');
        }

        return Inertia::render('Decretacoes/ProcessoShow', [
            'processo' => $processo,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Decretacoes/ProcessoCreate');
    }

    public function edit(int $id): Response
    {
        $processo = $this->processoService->findById($id);

        if (!$processo) {
            abort(404, 'Processo nao encontrado');
        }

        return Inertia::render('Decretacoes/ProcessoEdit', [
            'processo' => $processo,
        ]);
    }

    public function store(StoreProcessoRequest $request): RedirectResponse
    {
        $dto = ProcessoDTO::fromRequest($request);
        $processo = $this->processoService->createProcesso($dto);

        return redirect()->route('decretacoes.show', $processo->id)
            ->with('success', 'Processo cadastrado com sucesso!');
    }

    public function update(UpdateProcessoRequest $request, int $id): RedirectResponse
    {
        $dto = ProcessoDTO::fromRequest($request);
        $this->processoService->updateProcesso($dto, $id);

        return redirect()->back()->with('success', 'Processo atualizado com sucesso!');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->processoService->delete($id);

        return redirect()->route('decretacoes.index')
            ->with('success', 'Processo removido com sucesso!');
    }

    public function storeDesastres(Request $request, int $processoId): JsonResponse
    {
        $processo = \App\Models\Decreto\EntradaProcesso::findOrFail($processoId);
        $result = $this->desastreService->processDesastresData($request->all(), $processo);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function exportPowerBI(Request $request): JsonResponse
    {
        $data = $this->processoService->getNormalizedDataForPowerBI($request);
        return response()->json($data);
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $data = $this->processoService->getFilteredProcessos($request);
        return response()->json($data);
    }

    public function apiShow(int $id): JsonResponse
    {
        $processo = $this->processoService->findById($id);

        if (!$processo) {
            return response()->json(['error' => 'Processo nao encontrado'], 404);
        }

        return response()->json($processo);
    }
}
```

**Step 2: Verify syntax**

Run: `php -l app/Modules/Decretacoes/Controllers/DecretacoesController.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add app/Modules/Decretacoes/Controllers/DecretacoesController.php
git commit -m "feat(decretacoes): add unified DecretacoesController"
```

---

## Phase 5: Update Requests

### Task 5: Consolidate Requests

**Files:**
- Modify: `app/Modules/Decretacoes/Requests/StoreProcessoRequest.php`
- Modify: `app/Modules/Decretacoes/Requests/UpdateProcessoRequest.php`
- Delete: `app/Modules/Decretacoes/Requests/StoreEntradaProcessoRequest.php`
- Delete: `app/Modules/Decretacoes/Requests/UpdateEntradaProcessoRequest.php`

**Step 1: Read existing requests to merge rules**

Run: `cat app/Modules/Decretacoes/Requests/StoreEntradaProcessoRequest.php`

**Step 2: Update StoreProcessoRequest with merged rules**

Merge validation rules from both request classes into StoreProcessoRequest.

**Step 3: Update UpdateProcessoRequest with merged rules**

Merge validation rules from both request classes into UpdateProcessoRequest.

**Step 4: Commit**

```bash
git add app/Modules/Decretacoes/Requests/
git commit -m "feat(decretacoes): consolidate Request classes"
```

---

## Phase 6: Update ServiceProvider

### Task 6: Update DecretacoesServiceProvider

**Files:**
- Modify: `app/Modules/Decretacoes/DecretacoesServiceProvider.php`

**Step 1: Read current ServiceProvider**

Run: `cat app/Modules/Decretacoes/DecretacoesServiceProvider.php`

**Step 2: Update bindings to use new consolidated services**

Remove bindings for deleted services, ensure new services are properly registered.

**Step 3: Commit**

```bash
git add app/Modules/Decretacoes/DecretacoesServiceProvider.php
git commit -m "refactor(decretacoes): update ServiceProvider for consolidated services"
```

---

## Phase 7: Update Routes

### Task 7: Update routes for unified controller

**Files:**
- Modify: Routes file (check `routes/web.php` or module routes)

**Step 1: Find routes file**

Run: `grep -r "DecretacoesController\|ProcessoController" routes/ app/Modules/Decretacoes/`

**Step 2: Update routes to use DecretacoesController**

Replace references to old controllers with DecretacoesController.

**Step 3: Commit**

```bash
git add routes/
git commit -m "refactor(decretacoes): update routes for unified controller"
```

---

## Phase 8: Cleanup Old Files

### Task 8: Remove obsolete files

**Files to delete:**
- `app/Modules/Decretacoes/Actions/` (entire folder)
- `app/Modules/Decretacoes/Support/` (entire folder)
- `app/Modules/Decretacoes/Services/DesastreDataService.php`
- `app/Modules/Decretacoes/Services/EntradaProcessoService.php`
- `app/Modules/Decretacoes/Services/ProcessoFilterService.php`
- `app/Modules/Decretacoes/Services/ProcessoStatisticsService.php`
- `app/Modules/Decretacoes/Services/ProcessoSyncService.php`
- `app/Modules/Decretacoes/Services/ProcessoExportService.php`
- `app/Modules/Decretacoes/DTOs/CampoDTO.php`
- `app/Modules/Decretacoes/DTOs/ItemDTO.php`
- `app/Modules/Decretacoes/DTOs/DesastreDTO.php`
- `app/Modules/Decretacoes/DTOs/CategoriaDTO.php`
- `app/Modules/Decretacoes/DTOs/MunicipioDesastreDTO.php`
- `app/Modules/Decretacoes/DTOs/DisasterSubmissionDTO.php`
- `app/Modules/Decretacoes/DTOs/ProcessoDataDTO.php`
- `app/Modules/Decretacoes/Controllers/ProcessoController.php`
- `app/Modules/Decretacoes/Controllers/EntradaProcessoController.php`
- `app/Modules/Decretacoes/Requests/StoreEntradaProcessoRequest.php`
- `app/Modules/Decretacoes/Requests/UpdateEntradaProcessoRequest.php`

**Step 1: Remove Actions folder**

Run: `rm -rf app/Modules/Decretacoes/Actions/`

**Step 2: Remove Support folder**

Run: `rm -rf app/Modules/Decretacoes/Support/`

**Step 3: Remove old Services**

Run: `rm app/Modules/Decretacoes/Services/DesastreDataService.php app/Modules/Decretacoes/Services/EntradaProcessoService.php app/Modules/Decretacoes/Services/ProcessoFilterService.php app/Modules/Decretacoes/Services/ProcessoStatisticsService.php app/Modules/Decretacoes/Services/ProcessoSyncService.php app/Modules/Decretacoes/Services/ProcessoExportService.php`

**Step 4: Remove old DTOs**

Run: `rm app/Modules/Decretacoes/DTOs/CampoDTO.php app/Modules/Decretacoes/DTOs/ItemDTO.php app/Modules/Decretacoes/DTOs/DesastreDTO.php app/Modules/Decretacoes/DTOs/CategoriaDTO.php app/Modules/Decretacoes/DTOs/MunicipioDesastreDTO.php app/Modules/Decretacoes/DTOs/DisasterSubmissionDTO.php app/Modules/Decretacoes/DTOs/ProcessoDataDTO.php`

**Step 5: Remove old Controllers**

Run: `rm app/Modules/Decretacoes/Controllers/ProcessoController.php app/Modules/Decretacoes/Controllers/EntradaProcessoController.php`

**Step 6: Remove old Requests**

Run: `rm app/Modules/Decretacoes/Requests/StoreEntradaProcessoRequest.php app/Modules/Decretacoes/Requests/UpdateEntradaProcessoRequest.php`

**Step 7: Remove backup file**

Run: `rm app/Modules/Decretacoes/Services/ProcessoService.php.bak`

**Step 8: Commit cleanup**

```bash
git add -A
git commit -m "refactor(decretacoes): remove obsolete files after consolidation"
```

---

## Phase 9: Verification

### Task 9: Final verification

**Step 1: Verify final structure**

Run: `find app/Modules/Decretacoes -type f -name "*.php" | wc -l`
Expected: `17`

**Step 2: List final structure**

Run: `find app/Modules/Decretacoes -type f -name "*.php" | sort`

Expected output:
```
app/Modules/Decretacoes/Controllers/DecretacoesController.php
app/Modules/Decretacoes/DecretacoesServiceProvider.php
app/Modules/Decretacoes/DTOs/DecretacoesDTO.php
app/Modules/Decretacoes/Enums/StatusProcesso.php
app/Modules/Decretacoes/Enums/TipoDecreto.php
app/Modules/Decretacoes/Enums/TipoProcesso.php
app/Modules/Decretacoes/Models/Processo.php
app/Modules/Decretacoes/Models/ProcessoAnexo.php
app/Modules/Decretacoes/Models/ProcessoDanosHumanos.php
app/Modules/Decretacoes/Models/ProcessoDanosMateriais.php
app/Modules/Decretacoes/Models/ProcessoLog.php
app/Modules/Decretacoes/Models/ProcessoPrejuizo.php
app/Modules/Decretacoes/Requests/StoreProcessoRequest.php
app/Modules/Decretacoes/Requests/UpdateProcessoRequest.php
app/Modules/Decretacoes/Services/DesastreService.php
app/Modules/Decretacoes/Services/HexagonIntegrationService.php
app/Modules/Decretacoes/Services/ProcessoService.php
```

**Step 3: Run PHP syntax check on all files**

Run: `find app/Modules/Decretacoes -name "*.php" -exec php -l {} \;`
Expected: All files pass syntax check

**Step 4: Final commit**

```bash
git add -A
git commit -m "refactor(decretacoes): complete module consolidation - 32 to 17 files"
```

---

## Summary

| Phase | Tasks | Description |
|-------|-------|-------------|
| 1 | 1 | Create consolidated DTOs |
| 2 | 1 | Create DesastreService |
| 3 | 1 | Consolidate ProcessoService |
| 4 | 1 | Create unified Controller |
| 5 | 1 | Update Requests |
| 6 | 1 | Update ServiceProvider |
| 7 | 1 | Update Routes |
| 8 | 1 | Remove obsolete files |
| 9 | 1 | Final verification |

**Total: 9 tasks**
**Estimated time: 45-60 minutes**
**Result: 32 files -> 17 files (47% reduction)**
