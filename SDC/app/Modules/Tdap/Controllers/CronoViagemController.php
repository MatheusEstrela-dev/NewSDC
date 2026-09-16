<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Tdap\DTOs\CronoViagemDTO;
use App\Modules\Tdap\Models\CronoViagem;
use App\Modules\Tdap\Requests\StoreCronoViagemRequest;
use App\Modules\Tdap\Requests\ValidarCronoViagemRequest;
use App\Modules\Tdap\Resources\CronoViagemResource;
use App\Modules\Tdap\Services\CronoViagemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CronoViagemController extends Controller
{
    public function __construct(
        private readonly CronoViagemService $service,
    ) {}

    public function pendentes(Request $request): Response
    {
        $perPage = (int) $request->integer('per_page', 25);

        // Allowlist explicita, como em CronogramaController@index: filtro que
        // nao estiver nesta lista e ignorado em silencio.
        $filtros = $request->only(['search', 'municipio_id', 'prestador_id', 'cronograma_id']);

        $pendentes = $this->service->listarPendentesValidacao($perPage, $filtros);

        return Inertia::render('Tdap/Viagens/Pendentes', [
            'viagens' => CronoViagemResource::collection($pendentes),
            'filtros' => $filtros,

            // Closures = lazy props do Inertia: nao recalculam num reload
            // parcial, que e como a tela se atualiza depois de validar.
            'estatisticas' => fn (): array => $this->service->obterEstatisticas(),
            'municipios'   => fn () => Municipio::query()
                ->whereIn('id', function ($sub): void {
                    $sub->select('c.municipio_id')
                        ->from('tdap_cronogramas as c')
                        ->join('tdap_crono_caminhoes as cc', 'cc.cronograma_id', '=', 'c.id')
                        ->join('tdap_crono_viagens as v', 'v.crono_caminhao_id', '=', 'cc.id')
                        ->whereNull('v.validado')
                        ->whereNull('v.deleted_at');
                })
                ->orderBy('nome')
                ->get(['id', 'nome']),

            'canValidar' => $request->user()?->can('tdap.viagens.validar') ?? false,
        ]);
    }

    public function store(StoreCronoViagemRequest $request): RedirectResponse
    {
        try {
            $viagem = $this->service->registrar(CronoViagemDTO::fromRequest($request->validated()));
            $cronogramaId = $viagem->cronoCaminhao?->cronograma_id;

            $redirect = $cronogramaId
                ? redirect()->route('tdap.cronogramas.show', $cronogramaId)
                : back();

            return $redirect->with('success', 'Viagem registrada. Aguardando validacao.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function validar(ValidarCronoViagemRequest $request, CronoViagem $viagem): RedirectResponse
    {
        try {
            $aprovada = (bool) $request->boolean('aprovada');
            $this->service->validar($viagem->id, $aprovada, $request->input('obs_aprovacao'));

            $cronogramaId = $viagem->cronoCaminhao?->cronograma_id;
            $msg = $aprovada ? 'Viagem aprovada.' : 'Viagem rejeitada.';

            return ($cronogramaId
                ? redirect()->route('tdap.cronogramas.show', $cronogramaId)
                : back()
            )->with('success', $msg);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(CronoViagem $viagem, Request $request): RedirectResponse
    {
        if (! $request->user()?->can('tdap.viagens.create')) {
            abort(403);
        }

        try {
            $cronogramaId = $viagem->cronoCaminhao?->cronograma_id;
            $this->service->remover($viagem->id);

            return ($cronogramaId
                ? redirect()->route('tdap.cronogramas.show', $cronogramaId)
                : back()
            )->with('success', 'Viagem removida.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
