<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Resources;

use App\Modules\Decretacoes\Models\DecretoMunicipio;
use App\Modules\Decretacoes\Models\Processo;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProcessosIndexResource extends ResourceCollection
{
    protected $r;
    protected array $cobradeMap = [];

    public function __construct($items)
    {
        parent::__construct($items);
        $this->r = $items;
        $this->buildCobradeMap();
    }

    public function toArray($request = null)
    {
        $processosIds = $this->r->pluck('id')->toArray();

        $baseStats = $this->getAggregatedStats($processosIds);

        $decretoItems = $this->getDecretoItems();

        $stats = [
            'fixed_data_block' => [
                'total' => [
                    'titulo' => 'Total de Eventos',
                    'total' => $baseStats['total'],
                    'ecp' => $baseStats['total_ecp'],
                    'se' => $baseStats['total_se'],
                ],
                'registros' => [
                    'titulo' => 'Registros',
                    'total' => $baseStats['registros'],
                    'ecp' => $baseStats['registros_ecp'],
                    'se' => $baseStats['registros_se'],
                ],
                'decretos' => [
                    'titulo' => 'Decretacoes',
                    'total' => $baseStats['decretos'],
                    'ecp' => $baseStats['decretos_ecp'],
                    'se' => $baseStats['decretos_se'],
                ],
                'municipios' => [
                    'titulo' => 'Municipios Atingidos',
                    'total' => $baseStats['municipios'],
                    'ecp' => $baseStats['municipios_ecp'],
                    'se' => $baseStats['municipios_se'],
                ],
                'prazo_vigentes' => [
                    'titulo' => 'Decretacoes Vigentes',
                    'total' => $baseStats['vigentes'],
                    'ecp' => $baseStats['vigentes_ecp'],
                    'se' => $baseStats['vigentes_se'],
                ],
            ],
            'decretos_items' => [
                'ESTADUAL' => ($decretoItems['ESTADUAL'] ?? 0) + ($decretoItems['F_ESTADUAL'] ?? 0),
                'MUNICIPAL' => ($decretoItems['MUNICIPAL'] ?? 0) + ($decretoItems['F_MUNICIPAL'] ?? 0)
            ],

            'municipios_items' => $this->getMunicipiosItems(),

            'vigentes_items' => $this->getVigentesItems(),

            'tipos_desastre_items_municipal' => $this->getTiposDesastreStats(false, 'MUNICIPAL'),
            'tipos_desastre_items_estadual' => $this->getTiposDesastreStats(false, 'ESTADUAL'),
            'tipos_desastre_items_municipios' => $this->getTiposDesastreStats(true),
        ];

        return [
            'entradas' => $this->r,
            'stats' => $stats,
        ];
    }

    private function getAggregatedStats(array $processosIds): array
    {
        $cacheKey = 'decretacoes.stats.' . md5(json_encode($processosIds));

        return Cache::remember($cacheKey, 300, function () use ($processosIds) {
            $baseQuery = Processo::whereIn('id', $processosIds);

            $mainStats = (clone $baseQuery)->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN reconhecimento = 'Registro' THEN 1 ELSE 0 END) as registros,
                SUM(CASE WHEN reconhecimento != 'Registro' THEN 1 ELSE 0 END) as decretos,
                SUM(CASE WHEN tipo_desastre = 'ECP' THEN 1 ELSE 0 END) as total_ecp,
                SUM(CASE WHEN tipo_desastre = 'SE' THEN 1 ELSE 0 END) as total_se,
                SUM(CASE WHEN tipo_desastre = 'N1' THEN 1 ELSE 0 END) as total_n1,
                SUM(CASE WHEN reconhecimento = 'Registro' AND tipo_desastre = 'ECP' THEN 1 ELSE 0 END) as registros_ecp,
                SUM(CASE WHEN reconhecimento = 'Registro' AND tipo_desastre = 'SE' THEN 1 ELSE 0 END) as registros_se,
                SUM(CASE WHEN reconhecimento = 'Registro' AND tipo_desastre = 'N1' THEN 1 ELSE 0 END) as registros_n1,
                SUM(CASE WHEN reconhecimento != 'Registro' AND tipo_desastre = 'ECP' THEN 1 ELSE 0 END) as decretos_ecp,
                SUM(CASE WHEN reconhecimento != 'Registro' AND tipo_desastre = 'SE' THEN 1 ELSE 0 END) as decretos_se,
                SUM(CASE WHEN reconhecimento != 'Registro' AND tipo_desastre = 'N1' THEN 1 ELSE 0 END) as decretos_n1
            ")->first();

            $municipiosStats = DecretoMunicipio::whereIn('entrada_processos_id', $processosIds)
                ->join('dec_entrada_processos', 'dec_entrada_processos.id', '=', 'dec_decreto_municipios.entrada_processos_id')
                ->selectRaw("
                    COUNT(DISTINCT municipio_id) as total,
                    COUNT(DISTINCT CASE WHEN tipo_desastre = 'ECP' THEN municipio_id END) as ecp,
                    COUNT(DISTINCT CASE WHEN tipo_desastre = 'SE' THEN municipio_id END) as se,
                    COUNT(DISTINCT CASE WHEN tipo_desastre = 'N1' THEN municipio_id END) as n1
                ")
                ->first();

            $vigentesStats = $this->vigentesQuery()
                ->whereIn('id', $processosIds)
                ->where('reconhecimento', 'LIKE', 'Reconhecido pelo Estado%')
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN tipo_desastre = 'ECP' THEN 1 ELSE 0 END) as ecp,
                    SUM(CASE WHEN tipo_desastre = 'SE' THEN 1 ELSE 0 END) as se,
                    SUM(CASE WHEN tipo_desastre = 'N1' THEN 1 ELSE 0 END) as n1
                ")
                ->first();

            return [
                'total' => (int) ($mainStats->total ?? 0),
                'registros' => (int) ($mainStats->registros ?? 0),
                'decretos' => (int) ($mainStats->decretos ?? 0),
                'total_ecp' => (int) ($mainStats->total_ecp ?? 0),
                'total_se' => (int) ($mainStats->total_se ?? 0),
                'total_n1' => (int) ($mainStats->total_n1 ?? 0),
                'registros_ecp' => (int) ($mainStats->registros_ecp ?? 0),
                'registros_se' => (int) ($mainStats->registros_se ?? 0),
                'registros_n1' => (int) ($mainStats->registros_n1 ?? 0),
                'decretos_ecp' => (int) ($mainStats->decretos_ecp ?? 0),
                'decretos_se' => (int) ($mainStats->decretos_se ?? 0),
                'decretos_n1' => (int) ($mainStats->decretos_n1 ?? 0),
                'municipios' => (int) ($municipiosStats->total ?? 0),
                'municipios_ecp' => (int) ($municipiosStats->ecp ?? 0),
                'municipios_se' => (int) ($municipiosStats->se ?? 0),
                'municipios_n1' => (int) ($municipiosStats->n1 ?? 0),
                'vigentes' => (int) ($vigentesStats->total ?? 0),
                'vigentes_ecp' => (int) ($vigentesStats->ecp ?? 0),
                'vigentes_se' => (int) ($vigentesStats->se ?? 0),
                'vigentes_n1' => (int) ($vigentesStats->n1 ?? 0),
            ];
        });
    }

    private function getDecretoItems(): array
    {
        return Cache::remember('decretacoes.decreto_items', 300, function () {
            return Processo::groupBy('processo')
                ->where('reconhecimento', '!=', 'Registro')
                ->selectRaw("
                    CASE
                        WHEN processo = 'INDIVIDUAL' THEN 'F_MUNICIPAL'
                        WHEN processo LIKE 'DECRETO%' THEN 'F_ESTADUAL'
                        ELSE processo
                    END AS processo,
                    COUNT(*) AS total
                ")
                ->pluck('total', 'processo')->toArray();
        });
    }

    private function getMunicipiosItems(): array
    {
        return Cache::remember('decretacoes.municipios_items', 300, function () {
            return DecretoMunicipio::with('municipio')
                ->whereHas('processo', function ($cb) {
                    $cb->where('reconhecimento', '!=', 'Registro');
                })
                ->selectRaw('municipio_id, COUNT(*) AS total')
                ->groupBy('municipio_id')
                ->orderBy('total', 'desc')
                ->get()
                ->pluck('total', 'municipio.p_nome')->toArray();
        });
    }

    private function getVigentesItems(): array
    {
        return Cache::remember('decretacoes.vigentes_items', 300, function () {
            return $this->vigentesQuery()
                ->where('reconhecimento', 'LIKE', 'Reconhecido pelo Estado%')
                ->selectRaw('processo, COUNT(*) AS total')
                ->groupBy('processo')
                ->pluck('total', 'processo')
                ->toArray();
        });
    }

    private function vigentesQuery($entrada = null)
    {
        if (!$entrada) {
            $entrada = new Processo();
        }

        return $entrada->where(function ($q) {
            $q->whereNull('data_publicacao_mg')
                ->orWhereRaw("(data_publicacao_mg + (prazo_vigencia || ' days')::interval) >= CURRENT_DATE");
        })->where('reconhecimento', '!=', 'Registro');
    }

    private function buildCobradeMap(): void
    {
        $this->cobradeMap = Cache::remember('cobrade_map', 3600, function () {
            $cobrade = include app_path('Enums/classificacao_desastres.php');
            $map = [];
            foreach ($cobrade as $item) {
                $map[$item['id']] = $item['subtipo'] ?? $item['tipo'] ?? $item['subgrupo'] ?? $item['grupo'] ?? 'N/A';
            }
            return $map;
        });
    }

    private function getTiposDesastreStats(bool $municipiosPer = false, ?string $processoTipo = null): array
    {
        $cacheKey = "decretacoes.tipos_desastre.{$municipiosPer}.{$processoTipo}";

        return Cache::remember($cacheKey, 300, function () use ($municipiosPer, $processoTipo) {
            if ($municipiosPer) {
                $entradas = DecretoMunicipio::whereHas('processo', function ($cb) {
                    $cb->where('reconhecimento', '!=', 'Registro');
                })
                    ->select('tipo_desastre_id', 'tipo_desastre', 'p_nome')
                    ->distinct('dec_entrada_processos.tipo_desastre_id')
                    ->join('cedec_municipio', 'cedec_municipio.id', '=', 'dec_decreto_municipios.municipio_id')
                    ->join('dec_entrada_processos', 'dec_entrada_processos.id', '=', 'dec_decreto_municipios.entrada_processos_id')
                    ->get();
            } else {
                $query = Processo::select('tipo_desastre_id', 'tipo_desastre')
                    ->where('reconhecimento', '!=', 'Registro')
                    ->whereNotNull('tipo_desastre_id');

                if ($processoTipo) {
                    $query->where('processo', $processoTipo);
                }

                $entradas = $query->get();
            }

            $stats = [];

            foreach ($entradas as $entrada) {
                if ($municipiosPer) {
                    $name = $entrada->p_nome ?? 'N/A';
                } elseif ($entrada->tipo_desastre_id) {
                    $name = $this->cobradeMap[$entrada->tipo_desastre_id] ?? 'N/A';
                } else {
                    $name = $entrada->tipo_desastres ?? 'N/A';
                }

                if (!isset($stats[$name])) {
                    $stats[$name] = 0;
                }
                $stats[$name]++;
            }

            arsort($stats);

            return $stats;
        });
    }
}
