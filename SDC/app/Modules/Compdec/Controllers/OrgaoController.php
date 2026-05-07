<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compdec\DTOs\OrgaoDTO;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Requests\StoreOrgaoRequest;
use App\Modules\Compdec\Requests\UpdateOrgaoRequest;
use App\Modules\Compdec\Resources\EquipeIndexResource;
use App\Modules\Compdec\Resources\OrgaoIndexResource;
use App\Modules\Compdec\Resources\OrgaoResource;
use App\Modules\Compdec\Services\EquipeService;
use App\Modules\Compdec\Services\OrgaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrgaoController extends Controller
{
    public function __construct(
        private readonly OrgaoService $service,
        private readonly EquipeService $equipeService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Orgao::class);

        $perPage = (int) $request->integer('per_page', 15);
        $filtros = $request->only(['tipo', 'status', 'municipio_id', 'search']);

        $orgaos = $this->service->listarOrgaos($perPage, $filtros);

        return Inertia::render('Compdec/OrgaosIndex', [
            'orgaos' => OrgaoIndexResource::collection($orgaos),
            'statistics' => fn () => $this->service->obterEstatisticas(),
            'filters' => $filtros,
            'filterOptions' => [
                'municipalities' => [],
            ],
            'canManage' => $request->user()?->can('compdec.orgaos.edit') ?? false,
        ]);
    }

    public function arvore(): Response
    {
        $this->authorize('viewAny', Orgao::class);

        return Inertia::render('Compdec/OrgaoArvore', [
            'arvore' => $this->service->obterArvoreHierarquica(),
        ]);
    }

    public function show(Orgao $orgao, Request $request): Response
    {
        $this->authorize('view', $orgao);

        $orgao = $this->service->obterOrgao($orgao->id);

        return Inertia::render('Compdec/OrgaoShow', [
            'orgao' => OrgaoResource::make($orgao->loadMissing(['orgaoSuperior', 'prefeitura'])),
            'usuarios' => $orgao->usuarios()->select(['users.id', 'users.name', 'users.email'])->get(),
            'canManage' => $request->user()?->can('update', $orgao) ?? false,
            'canVincularUsuarios' => $request->user()?->can('compdec.usuarios.manage') ?? false,

            // Tabs F2-F4: lazy via Inertia partial reload (router.reload({ only: ['equipe'] }))
            'equipe' => Inertia::lazy(fn () => EquipeIndexResource::collection(
                $this->equipeService->listarPorOrgao($orgao->id),
            )),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Orgao::class);

        return Inertia::render('Compdec/OrgaoCreate', [
            'cedecs' => $this->service->obterCedecs(),
            'redecs' => $this->service->obterRedecs(),
            'orgaosSuperior' => $this->service->obterCedecs()->concat($this->service->obterRedecs())->values(),
            'municipios' => [],
        ]);
    }

    public function store(StoreOrgaoRequest $request): RedirectResponse
    {
        $orgao = $this->service->criarOrgao(
            OrgaoDTO::fromRequest($request->validated()),
        );

        return redirect()
            ->route('compdec.show', $orgao->id)
            ->with('success', "Orgao {$orgao->nome} criado com sucesso.");
    }

    public function edit(Orgao $orgao): Response
    {
        $this->authorize('update', $orgao);

        return Inertia::render('Compdec/OrgaoEdit', [
            'orgao' => OrgaoResource::make($orgao->loadMissing(['orgaoSuperior'])),
            'cedecs' => $this->service->obterCedecs(),
            'redecs' => $this->service->obterRedecs(),
            'orgaosSuperior' => $this->service->obterCedecs()->concat($this->service->obterRedecs())->values(),
            'municipios' => [],
        ]);
    }

    public function update(UpdateOrgaoRequest $request, Orgao $orgao): RedirectResponse
    {
        $atualizado = $this->service->atualizarOrgao(
            $orgao->id,
            OrgaoDTO::fromRequest($request->validated()),
        );

        return redirect()
            ->route('compdec.show', $atualizado->id)
            ->with('success', "Orgao {$atualizado->nome} atualizado.");
    }

    public function destroy(Orgao $orgao): RedirectResponse
    {
        $this->authorize('delete', $orgao);

        $nome = $orgao->nome;
        $this->service->deletarOrgao($orgao->id);

        return redirect()
            ->route('compdec.index')
            ->with('success', "Orgao {$nome} excluido.");
    }

    public function uploadFotoCoordenador(Request $request, Orgao $orgao): RedirectResponse
    {
        $this->authorize('update', $orgao);

        $request->validate([
            'foto' => [
                'required',
                'file',
                'mimes:jpeg,png,webp',
                'max:' . (int) (config('compdec.upload_limits.foto_coordenador', 307200) / 1024),
            ],
        ]);

        $this->service->uploadFotoCoordenador($orgao->id, $request->file('foto'));

        return back()->with('success', 'Foto do coordenador atualizada.');
    }

    public function removerFotoCoordenador(Orgao $orgao): RedirectResponse
    {
        $this->authorize('update', $orgao);
        $this->service->removerFotoCoordenador($orgao->id);

        return back()->with('success', 'Foto do coordenador removida.');
    }

    public function vincularUsuario(Request $request, Orgao $orgao): RedirectResponse
    {
        $this->authorize('compdec.usuarios.manage');

        $data = $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:users,id'],
            'funcao' => ['nullable', 'in:coordenador,agente,tecnico,apoio'],
            'is_principal' => ['nullable', 'boolean'],
        ]);

        $this->service->vincularUsuarioAOrgao(
            $orgao->id,
            (int) $data['usuario_id'],
            [
                'funcao' => $data['funcao'] ?? 'agente',
                'is_principal' => (bool) ($data['is_principal'] ?? false),
            ],
        );

        return back()->with('success', 'Usuario vinculado ao orgao.');
    }

    public function desvincularUsuario(Orgao $orgao, int $usuario): RedirectResponse
    {
        $this->authorize('compdec.usuarios.manage');
        $this->service->desvincularUsuario($orgao->id, $usuario);

        return back()->with('success', 'Vinculo removido.');
    }
}
