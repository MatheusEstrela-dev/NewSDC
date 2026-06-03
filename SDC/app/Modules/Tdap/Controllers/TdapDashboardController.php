<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\CronoCaminhao;
use App\Modules\Tdap\Models\CronoViagem;
use App\Modules\Tdap\Models\Historico;
use App\Modules\Tdap\Models\Prestador;
use Inertia\Inertia;
use Inertia\Response;

class TdapDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Tdap/Dashboard', [
            'kpis'             => fn () => $this->calcularKpis(),
            'eventosRecentes'  => fn () => $this->listarEventosRecentes(),
            'cronogramasAtivos' => fn () => $this->listarCronogramasAtivos(),
        ]);
    }

    /**
     * @return array<string, int|float>
     */
    private function calcularKpis(): array
    {
        $inicioMes = now()->startOfMonth();

        $cronogramaStats = Cronograma::query()
            ->selectRaw('
                COUNT(*) FILTER (WHERE ativo = TRUE AND encerrado_em IS NULL) AS ativos,
                COUNT(*) FILTER (WHERE encerrado_em IS NOT NULL) AS encerrados,
                COUNT(*) FILTER (WHERE ativo = FALSE AND encerrado_em IS NULL) AS rascunhos
            ')
            ->first();

        $aguaEntreguesMes = (float) CronoCaminhao::query()
            ->join('tdap_crono_viagens', 'tdap_crono_caminhoes.id', '=', 'tdap_crono_viagens.crono_caminhao_id')
            ->join('tdap_caminhoes', 'tdap_crono_caminhoes.caminhao_id', '=', 'tdap_caminhoes.id')
            ->where('tdap_crono_viagens.validado', 1)
            ->where('tdap_crono_viagens.data_aprovacao', '>=', $inicioMes)
            ->sum('tdap_caminhoes.capacidade_m3');

        $prestadoresAtivos = (int) Prestador::query()
            ->ativo()
            ->whereExists(function ($q) {
                $q->selectRaw('1')
                    ->from('tdap_cronogramas')
                    ->whereColumn('tdap_cronogramas.prestador_id', 'tdap_prestadores.id')
                    ->where('tdap_cronogramas.ativo', true)
                    ->whereNull('tdap_cronogramas.encerrado_em')
                    ->whereNull('tdap_cronogramas.deleted_at');
            })
            ->count();

        $viagensPendentes = (int) CronoViagem::query()->pendente()->count();

        return [
            'cronogramas_ativos'        => (int) ($cronogramaStats->ativos ?? 0),
            'cronogramas_encerrados'    => (int) ($cronogramaStats->encerrados ?? 0),
            'cronogramas_rascunhos'     => (int) ($cronogramaStats->rascunhos ?? 0),
            'm3_entregues_mes'          => $aguaEntreguesMes,
            'prestadores_ativos'        => $prestadoresAtivos,
            'viagens_pendentes_validar' => $viagensPendentes,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listarEventosRecentes(int $limite = 10): array
    {
        return Historico::query()
            ->with('user:id,name')
            ->orderByDesc('data_evento')
            ->limit($limite)
            ->get()
            ->map(fn ($h) => [
                'id'           => $h->id,
                'data_evento'  => $h->data_evento?->toIso8601String(),
                'tipo_evento'  => $h->tipo_evento,
                'entity_type'  => $h->entity_type,
                'entity_id'    => $h->entity_id,
                'obs'          => $h->obs,
                'user_name'    => $h->user?->name,
            ])
            ->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listarCronogramasAtivos(int $limite = 5): array
    {
        return Cronograma::query()
            ->ativo()
            ->with(['municipio:id,nome,uf', 'prestador:id,nome'])
            ->withCount(['caminhoes'])
            ->orderByDesc('dt_inicio')
            ->limit($limite)
            ->get()
            ->map(fn ($c) => [
                'id'              => $c->id,
                'numero'          => $c->numero,
                'dt_inicio'       => $c->dt_inicio?->toDateString(),
                'dt_final'        => $c->dt_final?->toDateString(),
                'municipio_nome'  => $c->municipio?->nome,
                'municipio_uf'    => $c->municipio?->uf,
                'prestador_nome'  => $c->prestador?->nome,
                'caminhoes_count' => (int) $c->caminhoes_count,
            ])
            ->toArray();
    }
}
