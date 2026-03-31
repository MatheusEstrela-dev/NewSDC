<?php

declare(strict_types=1);

namespace App\Modules\Pae\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Pae\DTOs\PaeFormInfoGeraisDTO;
use App\Modules\Pae\DTOs\PaeFormObjetivoDTO;
use App\Modules\Pae\Models\PaeForm;
use App\Modules\Pae\Services\PaeFormularioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaeFormularioController extends Controller
{
    public function __construct(
        private readonly PaeFormularioService $service
    ) {}

    public function show(Request $request): Response
    {
        $formulario = null;

        if ($request->filled('formulario_id')) {
            $form = PaeForm::with(['apontamentos', 'conclusao'])
                ->findOrFail($request->integer('formulario_id'));
            $formulario = $this->service->formatForView($form);
        }

        return Inertia::render('Pae', [
            'municipios' => Municipio::orderBy('nome')->pluck('nome', 'id'),
            'formulario' => $formulario,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dto = PaeFormInfoGeraisDTO::fromArray(
            $this->validateInfoGerais($request),
            $request->user()->id
        );

        $form = $this->service->create($dto);

        return redirect()->route('pae.index', ['formulario_id' => $form->id])
            ->with('success', 'Informações gerais salvas.');
    }

    public function updateInfoGerais(Request $request, PaeForm $paeForm): RedirectResponse
    {
        $dto = PaeFormInfoGeraisDTO::fromArray(
            $this->validateInfoGerais($request),
            $request->user()->id
        );

        $this->service->updateInfoGerais($paeForm, $dto);

        return back()->with('success', 'Informações gerais atualizadas.');
    }

    public function updateObjetivoContexto(Request $request, PaeForm $paeForm): RedirectResponse
    {
        $dto = PaeFormObjetivoDTO::fromArray(
            $request->validate([
                'objetivo'         => ['nullable', 'string'],
                'contextualizacao' => ['nullable', 'string'],
            ]),
            $request->user()->id
        );

        $this->service->updateObjetivoContexto($paeForm, $dto);

        return back()->with('success', 'Objetivo e contextualização atualizados.');
    }

    public function updateApontamentos(Request $request, PaeForm $paeForm): RedirectResponse
    {
        $request->validate(['apontamentos' => ['nullable', 'array']]);

        $this->service->updateApontamentos($paeForm, $request->input('apontamentos', []), $request->user());

        return back()->with('success', 'Apontamentos salvos.');
    }

    public function updateConclusao(Request $request, PaeForm $paeForm): RedirectResponse
    {
        $request->validate(['conclusao' => ['nullable', 'array']]);

        $this->service->updateConclusao($paeForm, $request->input('conclusao', []), $request->user());

        return back()->with('success', 'Conclusão salva.');
    }

    public function finalizar(Request $request, PaeForm $paeForm): RedirectResponse
    {
        $this->service->finalizar($paeForm, $request->user());

        return back()->with('success', 'Relatório finalizado.');
    }

    private function validateInfoGerais(Request $request): array
    {
        return $request->validate([
            'barragem'                => ['nullable', 'string', 'max:255'],
            'empreendedor_res'        => ['nullable', 'string', 'max:255'],
            'coordenador_pae'         => ['nullable', 'string', 'max:255'],
            'email'                   => ['nullable', 'email', 'max:255'],
            'coordenador_mun_def_civ' => ['nullable', 'string', 'max:255'],
            'coordenador_mun_compdec' => ['nullable', 'string', 'max:255'],
            'metodo_construtivo'      => ['nullable', 'string', 'max:100'],
            'numero_zas'              => ['nullable', 'integer'],
            'nivel_emergencia'        => ['nullable', 'integer'],
            'municipio_id'            => ['nullable', 'integer', 'exists:municipios,id'],
            'pae_empnto_id'           => ['nullable', 'integer', 'exists:pae_empntos,id'],
        ]);
    }
}
