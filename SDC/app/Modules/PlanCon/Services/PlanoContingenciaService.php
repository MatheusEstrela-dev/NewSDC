<?php

declare(strict_types=1);

namespace App\Modules\PlanCon\Services;

use App\Modules\PlanCon\DTOs\MunicipioDTO;
use App\Modules\PlanCon\DTOs\PlanConStatsDTO;
use App\Modules\PlanCon\Enums\SituacaoPlano;
use App\Modules\PlanCon\Models\PlanoContingencia;
use App\Modules\Shared\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlanoContingenciaService extends BaseService
{
    private const TOTAL_MUNICIPIOS_MG = 853;

    public function find(int $id): ?PlanoContingencia
    {
        if (!$this->tableExists()) {
            return null;
        }

        return PlanoContingencia::find($id);
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->findAll($filters, $perPage);
    }

    public function getStatistics(): array
    {
        return $this->calculateStatistics();
    }

    public function findAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        if (!$this->tableExists()) {
            return $this->emptyPaginator($perPage);
        }

        $query = PlanoContingencia::query()->with('municipio');

        if (!empty($filters['situacao'])) {
            $query->where('situacao', $filters['situacao']);
        }

        if (!empty($filters['municipio'])) {
            $query->whereHas('municipio', function ($q) use ($filters) {
                $q->where('nome', 'like', "%{$filters['municipio']}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getStats(): PlanConStatsDTO
    {
        $stats = $this->calculateStatistics();

        return new PlanConStatsDTO(
            totalMunicipios: $stats['totalMunicipios'],
            municipiosComPlano: $stats['municipiosComPlano'],
            municipiosSemPlano: $stats['municipiosSemPlano'],
            percentualComPlano: $stats['percentualComPlano'],
            totalPlanos: $stats['totalPlanos'],
            planosRegulares: $stats['planosRegulares'],
            planosIrregulares: $stats['planosIrregulares'],
            percentualRegulares: $stats['percentualRegulares'],
        );
    }

    public function listMunicipiosComPlano(int $perPage = 15): array
    {
        $paginator = $this->getMunicipiosComPlano($perPage);

        $dtos = collect($paginator->items())->map(function ($item) {
            return MunicipioDTO::fromArray([
                'id' => $item->id,
                'nome' => $item->nome,
                'codigo_ibge' => $item->codigo_ibge,
                'tem_plano' => true,
                'situacao_plano' => $item->situacao_plano,
                'data_ultima_atualizacao' => $item->data_ultima_atualizacao,
            ])->toArray();
        })->all();

        return [
            'data' => $dtos,
            'pagination' => [
                'total' => $paginator->total(),
                'perPage' => $paginator->perPage(),
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
            ],
        ];
    }

    public function listMunicipiosSemPlano(int $perPage = 15): array
    {
        $paginator = $this->getMunicipiosSemPlano($perPage);

        $dtos = collect($paginator->items())->map(function ($item) {
            return MunicipioDTO::fromArray([
                'id' => $item->id,
                'nome' => $item->nome,
                'codigo_ibge' => $item->codigo_ibge,
                'tem_plano' => false,
                'situacao_plano' => null,
                'data_ultima_atualizacao' => null,
            ])->toArray();
        })->all();

        return [
            'data' => $dtos,
            'pagination' => [
                'total' => $paginator->total(),
                'perPage' => $paginator->perPage(),
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
            ],
        ];
    }

    public function create(array $data): PlanoContingencia
    {
        return PlanoContingencia::create($data);
    }

    public function update(int $id, array $data): PlanoContingencia
    {
        $plano = $this->find($id);
        $plano->update($data);

        return $plano->fresh();
    }

    public function delete(int $id): bool
    {
        return PlanoContingencia::destroy($id) > 0;
    }

    private function calculateStatistics(): array
    {
        $totalMunicipios = self::TOTAL_MUNICIPIOS_MG;

        if (!$this->tableExists()) {
            return $this->getMockStatistics();
        }

        try {
            $municipiosComPlano = DB::table('planos_contingencia')
                ->distinct('municipio_id')
                ->count('municipio_id');

            $municipiosSemPlano = $totalMunicipios - $municipiosComPlano;

            $percentualComPlano = $totalMunicipios > 0
                ? round(($municipiosComPlano / $totalMunicipios) * 100, 1)
                : 0;

            $totalPlanos = PlanoContingencia::count();

            $planosRegulares = PlanoContingencia::where('situacao', SituacaoPlano::REGULAR)->count();

            $planosIrregulares = $totalPlanos - $planosRegulares;

            $percentualRegulares = $totalPlanos > 0
                ? round(($planosRegulares / $totalPlanos) * 100, 1)
                : 0;

            return [
                'totalMunicipios' => $totalMunicipios,
                'municipiosComPlano' => $municipiosComPlano,
                'municipiosSemPlano' => $municipiosSemPlano,
                'percentualComPlano' => $percentualComPlano,
                'totalPlanos' => $totalPlanos,
                'planosRegulares' => $planosRegulares,
                'planosIrregulares' => $planosIrregulares,
                'percentualRegulares' => $percentualRegulares,
            ];
        } catch (\Exception $e) {
            return $this->getMockStatistics();
        }
    }

    private function getMunicipiosComPlano(int $perPage = 15): LengthAwarePaginator
    {
        if (!$this->tableExists()) {
            return $this->emptyPaginator($perPage);
        }

        try {
            return DB::table('municipios')
                ->join('planos_contingencia', 'municipios.id', '=', 'planos_contingencia.municipio_id')
                ->select(
                    'municipios.id',
                    'municipios.nome',
                    'municipios.codigo_ibge',
                    'planos_contingencia.situacao as situacao_plano',
                    'planos_contingencia.updated_at as data_ultima_atualizacao'
                )
                ->distinct()
                ->orderBy('municipios.nome')
                ->paginate($perPage);
        } catch (\Exception $e) {
            return $this->emptyPaginator($perPage);
        }
    }

    private function getMunicipiosSemPlano(int $perPage = 15): LengthAwarePaginator
    {
        if (!$this->tableExists()) {
            return $this->emptyPaginator($perPage);
        }

        try {
            return DB::table('municipios')
                ->leftJoin('planos_contingencia', 'municipios.id', '=', 'planos_contingencia.municipio_id')
                ->whereNull('planos_contingencia.id')
                ->where('municipios.uf', 'MG')
                ->select(
                    'municipios.id',
                    'municipios.nome',
                    'municipios.codigo_ibge'
                )
                ->orderBy('municipios.nome')
                ->paginate($perPage);
        } catch (\Exception $e) {
            return $this->emptyPaginator($perPage);
        }
    }

    private function tableExists(): bool
    {
        try {
            return Schema::hasTable('planos_contingencia');
        } catch (\Exception $e) {
            return false;
        }
    }

    private function emptyPaginator(int $perPage): LengthAwarePaginator
    {
        return new Paginator([], 0, $perPage, 1);
    }

    private function getMockStatistics(): array
    {
        return [
            'totalMunicipios' => self::TOTAL_MUNICIPIOS_MG,
            'municipiosComPlano' => 729,
            'municipiosSemPlano' => 124,
            'percentualComPlano' => 85.5,
            'totalPlanos' => 729,
            'planosRegulares' => 714,
            'planosIrregulares' => 15,
            'percentualRegulares' => 97.9,
        ];
    }
}
