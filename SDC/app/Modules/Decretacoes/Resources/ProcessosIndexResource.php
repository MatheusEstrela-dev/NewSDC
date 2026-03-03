<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Resources;

use App\Modules\Decretacoes\Models\DecretoMunicipio;
use App\Modules\Decretacoes\Models\Processo;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\DB;

class ProcessosIndexResource extends ResourceCollection
{
    protected $r;

    public function __construct($items)
    {
        parent::__construct($items);
        $this->r = $items;
    }

    public function toArray($request = null)
    {
        $decretoItems = Processo::groupBy('processo')
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

        $stats = [
            'fixed_data_block' => [
                'total' => [
                    'titulo' => 'Total de Eventos',
                    'total' => (clone $this->r)->count(),
                ],
                'registros' => [
                    'titulo' => 'Registros',
                    'total' => (clone $this->r)->where('reconhecimento', 'Registro')->count(),
                ],
                'decretos' => [
                    'titulo' => 'Decretações',
                    'total' => (clone $this->r)->where('reconhecimento', '!=', 'Registro')->count()
                ],
                'municipios' => [
                    'titulo' => 'Municípios Atingidos',
                    'total' => DecretoMunicipio::whereIn('entrada_processos_id', (clone $this->r)->pluck('id'))->distinct('municipio_id')->count('municipio_id')
                ],
                'prazo_vigentes' => [
                    'titulo' => 'Decretações Vigentes',
                    'total' => $this->vigentesQuery(clone $this->r)->where('reconhecimento', '!=', 'Registro')->where('reconhecimento', 'LIKE', 'Reconhecido pelo Estado%')->count(),
                ],
            ],
            'decretos_items' => [
                'ESTADUAL' => ($decretoItems['ESTADUAL'] ?? 0) + ($decretoItems['F_ESTADUAL'] ?? 0),
                'MUNICIPAL' => ($decretoItems['MUNICIPAL'] ?? 0) + ($decretoItems['F_MUNICIPAL'] ?? 0)
            ],

            'municipios_items' => DecretoMunicipio::with('municipio')
                ->whereHas('processo',
                    function ($cb) {
                        $cb->where('reconhecimento', '!=', 'Registro');
                })
                ->selectRaw('municipio_id, COUNT(*) AS total')
                ->groupBy('municipio_id')
                ->orderBy('total', 'desc')
                ->get()
                ->pluck('total', 'municipio.p_nome')->toArray(),

            'vigentes_items' => $this->vigentesQuery()
                ->where('reconhecimento', 'LIKE', 'Reconhecido pelo Estado%')
                ->selectRaw('processo, COUNT(*) AS total')
                ->groupBy('processo')
                ->pluck('total', 'processo')
                ->toArray(),

            'tipos_desastre_items_municipal' => $this->getTiposDesastreStats(false, 'MUNICIPAL'),
            'tipos_desastre_items_estadual' => $this->getTiposDesastreStats(false, 'ESTADUAL'),
            'tipos_desastre_items_municipios' => $this->getTiposDesastreStats(true),
        ];

        // Estatísticas ECP/SE para cada bloco
        foreach (['ECP', 'SE'] as $tipo) {
            $stats['fixed_data_block']['decretos'][strtolower($tipo)] = (clone $this->r)->where('reconhecimento', '!=', 'Registro')->where('tipo_desastre', $tipo)->count();
            $stats['fixed_data_block']['municipios'][strtolower($tipo)] = DecretoMunicipio::whereIn('entrada_processos_id', $this->r->pluck('id'))
                ->whereHas('processo',
                function ($cb) use ($tipo) {
                    $cb->where('tipo_desastre', $tipo)
                        ->where('reconhecimento', '!=', 'Registro');
                })
                ->distinct('municipio_id')
                ->count('municipio_id');
            $stats['fixed_data_block']['prazo_vigentes'][strtolower($tipo)] = $this->vigentesQuery()->where('reconhecimento', 'like', 'Reconhecido pelo Estado%')->where('tipo_desastre', $tipo)->count();
            $stats['fixed_data_block']['registros'][strtolower($tipo)] = (clone $this->r)->where('reconhecimento', 'Registro')->where('tipo_desastre', $tipo)->count();
            $stats['fixed_data_block']['total'][strtolower($tipo)] = (clone $this->r)->where('tipo_desastre', $tipo)->count();
        }

        return [
            'entradas' => $this->r,
            'stats' => $stats,
        ];
    }

    private function vigentesQuery($entrada = null)
    {
        if (!$entrada) {
            $entrada = new Processo();
        }

        return $entrada->where(function ($q) {
            $q->whereNull('data_publicacao_mg')
                ->orWhereRaw('DATE_ADD(data_publicacao_mg, INTERVAL prazo_vigencia DAY) >= CURDATE()');
        })->where('reconhecimento', '!=', 'Registro');
    }

    private function getTiposDesastreStats(bool $municipiosPer = false, ?string $processoTipo = null): array
    {
        if ($municipiosPer) {
            $entradas = DecretoMunicipio::whereHas('processo',
                function ($cb) {
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

        $cobrade = include app_path('Enums/classificacao_desastres.php');
        $stats = [];

        foreach ($entradas as $entrada) {
            $name = 'N/A';

            if ($entrada->tipo_desastre_id && !$municipiosPer) {
                foreach ($cobrade as $item) {
                    if ($item['id'] == $entrada->tipo_desastre_id) {
                        $name = $item['subtipo'] ?? $item['tipo'] ?? $item['subgrupo'] ?? $item['grupo'] ?? 'N/A';
                        break;
                    }
                }
            } else {
                $name = $entrada->tipo_desastres ?? 'N/A';
            }

            if ($municipiosPer) {
                $name = $entrada->p_nome;
            }

            if (!isset($stats[$name])) {
                $stats[$name] = 0;
            }
            $stats[$name]++;
        }

        arsort($stats);

        return $stats;
    }
}
