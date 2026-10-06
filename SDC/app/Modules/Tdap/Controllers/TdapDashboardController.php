<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Tdap\Enums\PeriodoEntregas;
use App\Modules\Tdap\Services\TdapDashboardService;
use App\Modules\Tdap\Support\EscopoDeLeitura;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TdapDashboardController extends Controller
{
    private const LIMITE_EVENTOS = 5;

    private const LIMITE_CRONOGRAMAS = 5;

    public function __construct(private readonly TdapDashboardService $dashboard) {}

    public function index(Request $request): Response
    {
        // Valor fora do enum cai no padrao em vez de 422: e so a aba do grafico.
        $periodo = PeriodoEntregas::tryFrom((string) $request->query('periodo')) ?? PeriodoEntregas::padrao();

        // Mesmo recorte da fila de validacao: ver EscopoDeLeitura.
        $municipioId = EscopoDeLeitura::municipioId($request->user());

        // O historico e trilha de auditoria: so vai para quem pode abrir a tela
        // de auditoria, e nao para todo mundo que ve o dashboard.
        $podeVerHistorico = $request->user()->can('tdap.historico.view');

        return Inertia::render('Tdap/Dashboard', [
            'kpis'              => fn () => $this->dashboard->kpis($municipioId),
            'eventosRecentes'   => fn () => $podeVerHistorico ? $this->dashboard->eventosRecentes(self::LIMITE_EVENTOS) : [],
            'cronogramasAtivos' => fn () => $this->dashboard->cronogramasAtivos($municipioId, self::LIMITE_CRONOGRAMAS),
            'entregas'          => fn () => $this->dashboard->entregas($municipioId, $periodo),
            'cobertura'         => fn () => $this->dashboard->cobertura($municipioId),
            // A tela diz de onde sao os numeros e leva o mesmo recorte para os
            // links (Cronogramas filtrado pelo municipio bate com o card).
            'escopo'            => $municipioId === null ? null : [
                'municipio_id'   => $municipioId,
                'municipio_nome' => Municipio::query()->whereKey($municipioId)->value('nome'),
            ],
        ]);
    }
}
