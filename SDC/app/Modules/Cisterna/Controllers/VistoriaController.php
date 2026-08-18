<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cisterna\DTOs\VistoriaDTO;
use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Requests\StoreVistoriaRequest;
use App\Modules\Cisterna\Requests\UpdateVistoriaRequest;
use App\Modules\Cisterna\Resources\BeneficiarioResource;
use App\Modules\Cisterna\Resources\VistoriaResource;
use App\Modules\Cisterna\Services\VistoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VistoriaController extends Controller
{
    public function __construct(
        private readonly VistoriaService $service,
    ) {}

    public function index(CisternaBeneficiario $beneficiario, Request $request): Response
    {
        $this->authorize('view', $beneficiario);

        $beneficiario->load(['vistorias.itensConferidos', 'vistorias.media', 'municipio:id,nome,uf']);

        return Inertia::render('Cisterna/Vistorias/Index', [
            'beneficiario' => BeneficiarioResource::make($beneficiario)->resolve(),
            'vistorias' => VistoriaResource::collection($beneficiario->vistorias),
            'etapa_disponivel' => $this->service->etapaDisponivel($beneficiario)?->value,
            'itens' => ItemInstalacao::options(),
            'permissoes' => [
                'criar' => $request->user()?->can('create', CisternaVistoria::class) ?? false,
            ],
        ]);
    }

    public function store(StoreVistoriaRequest $request): RedirectResponse
    {
        $vistoria = $this->service->abrir(
            VistoriaDTO::deValidados($request->validated())
        );

        $this->anexarArquivos($request, $vistoria);

        return redirect()
            ->route('cisternas.vistorias.show', $vistoria->id)
            ->with('success', "{$vistoria->etapa->label()} registrada com sucesso.");
    }

    public function show(CisternaVistoria $vistoria, Request $request): Response
    {
        $this->authorize('view', $vistoria);

        $vistoria->load(['itensConferidos', 'beneficiario.municipio:id,nome,uf', 'notificacoes', 'media']);

        return Inertia::render('Cisterna/Vistorias/Show', [
            'vistoria' => VistoriaResource::make($vistoria)->resolve(),
            'beneficiario' => BeneficiarioResource::make($vistoria->beneficiario)->resolve(),
            'itens' => ItemInstalacao::options(),
            'permissoes' => [
                'editar' => $request->user()?->can('update', $vistoria) ?? false,
                'excluir' => $request->user()?->can('delete', $vistoria) ?? false,
            ],
        ]);
    }

    public function update(UpdateVistoriaRequest $request, CisternaVistoria $vistoria): RedirectResponse
    {
        $atualizada = $this->service->atualizar(
            $vistoria,
            VistoriaDTO::deValidados($request->validated())
        );

        $this->anexarArquivos($request, $atualizada);

        return redirect()
            ->route('cisternas.vistorias.show', $atualizada->id)
            ->with('success', 'Vistoria atualizada.');
    }

    public function concluir(CisternaVistoria $vistoria): RedirectResponse
    {
        $this->authorize('update', $vistoria);

        $this->service->concluir($vistoria);

        return back()->with('success', "{$vistoria->etapa->label()} concluida.");
    }

    public function destroy(CisternaVistoria $vistoria): RedirectResponse
    {
        $this->authorize('delete', $vistoria);

        $beneficiarioId = $vistoria->beneficiario_id;
        $vistoria->delete();

        return redirect()
            ->route('cisternas.vistorias.index', $beneficiarioId)
            ->with('success', 'Vistoria excluida.');
    }

    private function anexarArquivos(Request $request, CisternaVistoria $vistoria): void
    {
        if ($request->hasFile('assinatura_engenheiro')) {
            // singleFile: o MediaLibrary substitui a anterior sozinho.
            $vistoria->addMedia($request->file('assinatura_engenheiro'))
                ->toMediaCollection('assinatura_engenheiro');
        }

        foreach ((array) $request->input('fotos_vistoria', []) as $indice => $foto) {
            $arquivo = $request->file("fotos_vistoria.{$indice}.arquivo");

            if ($arquivo === null) {
                continue;
            }

            // custom_properties substitui as 18 colunas {item}_foto1/2 do
            // legado. Acrescentar um item deixa de exigir migration.
            $vistoria->addMedia($arquivo)
                ->withCustomProperties([
                    'item' => $foto['item'] ?? null,
                    'sequencia' => (int) ($foto['sequencia'] ?? 1),
                ])
                ->toMediaCollection('fotos_vistoria');
        }
    }
}
