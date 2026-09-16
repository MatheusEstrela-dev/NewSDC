<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Tdap\DTOs\CronoViagemDTO;
use App\Modules\Tdap\Models\CronoViagem;
use App\Modules\Tdap\Requests\ConfirmarViagensRequest;
use App\Modules\Tdap\Requests\StoreCronoViagemRequest;
use App\Modules\Tdap\Requests\ValidarCronoViagemRequest;
use App\Modules\Tdap\Resources\CronoViagemResource;
use App\Modules\Tdap\Services\CronoViagemService;
use App\Support\Perfil\OrgaoDeLotacao;
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

        // O usuario vai ao service porque o recorte por municipio e dele, nao
        // do request: quem nao e estadual so enxerga a propria fila.
        $pendentes = $this->service->listarPendentesValidacao($perPage, $filtros, $request->user());

        return Inertia::render('Tdap/Viagens/Pendentes', [
            'viagens' => CronoViagemResource::collection($pendentes),
            'filtros' => $filtros,

            // Closures = lazy props do Inertia: nao recalculam num reload
            // parcial, que e como a tela se atualiza depois de validar.
            'estatisticas' => fn (): array => $this->service->obterEstatisticas($request->user()),
            // O filtro so oferece o que a pessoa pode ver: para quem nao e
            // estadual, sobra o proprio municipio. Listar os 853 num select
            // cujo resultado sempre volta vazio confunde mais que ajuda -- e
            // ainda revela onde ha operacao acontecendo.
            'municipios'   => fn () => Municipio::query()
                ->whereIn('id', function ($sub) use ($request): void {
                    $sub->select('c.municipio_id')
                        ->from('tdap_cronogramas as c')
                        ->join('tdap_crono_caminhoes as cc', 'cc.cronograma_id', '=', 'c.id')
                        ->join('tdap_crono_viagens as v', 'v.crono_caminhao_id', '=', 'cc.id')
                        ->whereNull('v.validado')
                        ->whereNull('v.deleted_at');

                    $municipioDoUsuario = OrgaoDeLotacao::municipioId($request->user());

                    if ($municipioDoUsuario !== null) {
                        $sub->where('c.municipio_id', $municipioDoUsuario);
                    }
                })
                ->orderBy('nome')
                ->get(['id', 'nome']),

            'canValidar' => $request->user()?->can('tdap.viagens.validar') ?? false,
        ]);
    }

    /**
     * Fila do COMPDEC: o que o municipio ainda nao confirmou ter recebido.
     */
    public function confirmacao(Request $request): Response
    {
        $usuario = $request->user();
        $perPage = (int) $request->integer('per_page', 25);
        $filtros = $request->only(['cronograma_id']);

        $viagens = $this->service->listarParaConfirmacao($usuario, $perPage, $filtros);

        return Inertia::render('Tdap/Viagens/Confirmacao', [
            'viagens'      => CronoViagemResource::collection($viagens),
            'filtros'      => $filtros,
            'municipio'    => OrgaoDeLotacao::resolver($usuario)?->municipio?->nome,
            'semMunicipio' => OrgaoDeLotacao::municipioId($usuario) === null,
            'canConfirmar' => $usuario?->can('tdap.viagens.confirmar') ?? false,
        ]);
    }

    public function confirmarLote(ConfirmarViagensRequest $request): RedirectResponse
    {
        $resultado = $this->service->confirmarEmLote(
            $request->user(),
            $request->validated('ids'),
            $request->validated('obs_confirmacao'),
        );

        $mensagem = $resultado['confirmadas'] === 1
            ? '1 viagem confirmada.'
            : "{$resultado['confirmadas']} viagens confirmadas.";

        if ($resultado['recusadas'] !== []) {
            $mensagem .= ' '.count($resultado['recusadas']).' recusada(s) por estarem fora do limite do cronograma.';

            return back()->with('warning', $mensagem);
        }

        return back()->with('success', $mensagem);
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
