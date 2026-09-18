<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\DTOs\VistoriaDTO;
use App\Modules\Tdap\Enums\ParecerVistoria;
use App\Modules\Tdap\Models\Caminhao;
use App\Modules\Tdap\Models\Vistoria;
use App\Modules\Tdap\Requests\StoreVistoriaRequest;
use App\Modules\Tdap\Requests\UpdateVistoriaRequest;
use App\Modules\Tdap\Resources\CaminhaoResource;
use App\Modules\Tdap\Resources\VistoriaIndexResource;
use App\Modules\Tdap\Resources\VistoriaResource;
use App\Modules\Tdap\Services\VistoriaFotoService;
use App\Modules\Tdap\Services\VistoriaService;
use App\Modules\Tdap\Support\ExportadorCsv;
use App\Modules\Tdap\Support\VigenciaVistoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FrotaVistoriaController extends Controller
{
    /**
     * Allowlist de filtros. Constante pelo mesmo motivo de FrotaController:
     * grade e CSV tem de filtrar identico, e duas copias divergem.
     *
     * @var array<int, string>
     */
    private const FILTROS = ['parecer', 'placa_id', 'vigente', 'search'];

    public function __construct(
        private readonly VistoriaService $service,
        private readonly VistoriaFotoService $fotoService,
    ) {}

    public function index(Request $request): Response
    {
        $perPage = (int) $request->integer('per_page', 15);
        $filtros = $request->only(self::FILTROS);

        $vistorias = $this->service->listar($perPage, $filtros);

        return Inertia::render('Tdap/Frota/Vistorias/Index', [
            'vistorias'     => VistoriaIndexResource::collection($vistorias),
            // Mesmos filtros da listagem: o card "Total" conta o que a grade
            // mostra (parecer/vigente ficam de fora — ver o service).
            'estatisticas'  => fn () => $this->service->obterEstatisticas($filtros),
            'caminhoes'     => fn () => Caminhao::ativo()->with('prestador:id,nome')->orderBy('placa')->get(['id', 'placa', 'marca', 'modelo', 'prestador_id']),
            'pareceres'     => ParecerVistoria::options(),
            'filtros'       => $filtros,
            'canCreate'     => $request->user()?->can('tdap.vistorias.create') ?? false,
            'canEdit'       => $request->user()?->can('tdap.vistorias.edit') ?? false,
            'canDelete'     => $request->user()?->can('tdap.vistorias.delete') ?? false,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        return ExportadorCsv::baixar(
            $this->service->exportar($request->only(self::FILTROS)),
            'vistorias',
        );
    }

    /**
     * Serie historica de vistorias de um caminhao, em JSON.
     *
     * O modal da listagem de frota consome isto. A vistoria vigente responde
     * "pode rodar HOJE"; a serie responde "este veiculo e confiavel?" --
     * reprovado tres vezes seguidas e um caminhao com problema, e isso so
     * aparece quando as inspecoes ficam lado a lado.
     *
     * Vivia em CaminhaoController, atras de `can:tdap.caminhoes.view`: quem
     * podia ver a frota lia o historico de vistoria inteiro sem ter a permissao
     * de vistoria. Agora esta no controller de vistoria, sob a permissao certa.
     */
    public function vistoriasDoCaminhao(Caminhao $caminhao): JsonResponse
    {
        $vistorias = $caminhao->vistorias()
            ->get(['id', 'placa_id', 'data', 'parecer', 'nome', 'ficha', 'lacre', 'edital', 'observacoes'])
            ->map(fn (Vistoria $v) => [
                'id'             => $v->id,
                'data'           => $v->data?->toDateString(),
                'parecer'        => $v->parecer?->value,
                'parecer_label'  => $v->parecer?->label(),
                'vistoriador'    => $v->nome,
                'ficha'          => $v->ficha,
                'lacre'          => $v->lacre,
                'edital'         => $v->edital,
                'observacoes'    => $v->observacoes,
                'vigente'        => (bool) $v->esta_vigente,
                'valido_ate'     => $v->valido_ate?->toDateString(),
                // Vigencia assinada: negativo = venceu. Sai de VigenciaVistoria,
                // a mesma fonte do accessor -- antes reusava VigenciaAta, que e
                // o prazo de OUTRO agregado.
                'dias_restantes' => VigenciaVistoria::diasRestantes($v->data),
            ])
            ->values();

        return response()->json([
            'caminhao' => [
                'id'     => $caminhao->id,
                'placa'  => $caminhao->placa,
                'marca'  => $caminhao->marca,
                'modelo' => $caminhao->modelo,
            ],
            'vistorias' => $vistorias,
        ]);
    }

    /**
     * Nova vistoria, sempre a partir de um caminhao.
     *
     * O caminhao vem do route model binding (`tdap/frota/{caminhao}/vistorias/
     * nova`), e nao de `?placa_id=`. A tela de frota ja mandava
     * `route('tdap.vistorias.create', { placa_id: caminhao.id })`, mas o create
     * nunca leu a query string e o Create.vue inicializava `placa_id: null` --
     * o pre-preenchimento simplesmente nao acontecia, e quem clicava em "Nova
     * vistoria" na linha do caminhao tinha de reencontra-lo num select com a
     * frota inteira.
     */
    public function create(Caminhao $caminhao): Response
    {
        return Inertia::render('Tdap/Frota/Vistorias/Create', [
            'caminhao'         => CaminhaoResource::make($caminhao->load('prestador')),
            'pareceres'        => ParecerVistoria::options(),
            'itensEstruturais' => Vistoria::ITENS_ESTRUTURAIS,
            'itensTanque'      => Vistoria::ITENS_TANQUE,
        ]);
    }

    public function store(StoreVistoriaRequest $request, Caminhao $caminhao): RedirectResponse
    {
        // Ficha e fotos numa transacao so. Sem isso, uma falha de disco na
        // terceira foto deixaria a vistoria gravada com duas -- e um 500 no
        // lugar do redirect, sem que ninguem soubesse que o cadastro passou.
        //
        // `except('fotos')`: o DTO descreve a ficha, nao os anexos. Deixar os
        // arquivos entrarem ali faria o payload carregar UploadedFile ate o
        // Eloquent.
        $vistoria = DB::transaction(function () use ($request): Vistoria {
            $vistoria = $this->service->criar(
                VistoriaDTO::fromRequest($request->safe()->except('fotos')),
            );

            foreach ($request->file('fotos') ?? [] as $arquivo) {
                $this->fotoService->store($vistoria, $arquivo);
            }

            return $vistoria;
        });

        return redirect()
            ->route('tdap.frota.vistorias.show', $vistoria->id)
            ->with('success', "Vistoria do caminhão {$caminhao->placa} registrada.");
    }

    public function show(Vistoria $vistoria, Request $request): Response
    {
        $vistoria = $this->service->obter($vistoria->id);

        return Inertia::render('Tdap/Frota/Vistorias/Show', [
            'vistoria'         => VistoriaResource::make($vistoria),
            // Prop propria, e nao dentro de `vistoria`: e ela que o
            // router.reload({ only: ['fotos'] }) repoe depois de anexar ou
            // remover, sem recarregar o resto da tela.
            'fotos'            => $vistoria->fotos()->get(),
            'itensEstruturais' => Vistoria::ITENS_ESTRUTURAIS,
            'itensTanque'      => Vistoria::ITENS_TANQUE,
            'canEdit'          => $request->user()?->can('tdap.vistorias.edit') ?? false,
            'canDelete'        => $request->user()?->can('tdap.vistorias.delete') ?? false,
        ]);
    }

    public function edit(Vistoria $vistoria): Response
    {
        return Inertia::render('Tdap/Frota/Vistorias/Edit', [
            'vistoria'         => VistoriaResource::make($vistoria->load(['caminhao.prestador'])),
            // Mesma prop avulsa do show: e ela que o
            // router.reload({ only: ['fotos'] }) repoe sem recarregar o
            // formulario inteiro -- recarregar aqui perderia o que ja foi
            // digitado no checklist.
            'fotos'            => $vistoria->fotos()->get(),
            'caminhoes'        => Caminhao::ativo()->with('prestador:id,nome')->orderBy('placa')->get(['id', 'placa', 'marca', 'modelo', 'cor', 'ano', 'capacidade_m3', 'prestador_id']),
            'pareceres'        => ParecerVistoria::options(),
            'itensEstruturais' => Vistoria::ITENS_ESTRUTURAIS,
            'itensTanque'      => Vistoria::ITENS_TANQUE,
        ]);
    }

    public function update(UpdateVistoriaRequest $request, Vistoria $vistoria): RedirectResponse
    {
        $atualizada = $this->service->atualizar($vistoria->id, VistoriaDTO::fromRequest($request->validated()));

        return redirect()
            ->route('tdap.frota.vistorias.show', $atualizada->id)
            ->with('success', 'Vistoria atualizada.');
    }

    public function destroy(Vistoria $vistoria, Request $request): RedirectResponse
    {
        if (! $request->user()?->can('tdap.vistorias.delete')) {
            abort(403);
        }

        $this->service->deletar($vistoria->id);

        return redirect()
            ->route('tdap.frota.vistorias.index')
            ->with('success', 'Vistoria excluida.');
    }
}
