<?php

namespace App\Modules\Compdec\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Compdec\DTOs\OrgaoDTO;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Services\OrgaoService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrgaoController extends Controller
{
    public function __construct(
        private OrgaoService $orgaoService
    ) {}

    /**
     * Listar órgãos (GET /compdec/orgaos)
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Orgao::class);

        $orgaos = $this->orgaoService->listarOrgaos(15);

        return Inertia::render('Compdec/OrgaosIndex', [
            'orgaos' => $orgaos,
            'pode_criar' => $this->userCanCreate(),
        ]);
    }

    /**
     * Exibir formulário de criação (GET /compdec/orgaos/novo)
     */
    public function create(): Response
    {
        $this->authorize('create', Orgao::class);

        $cedecs = $this->orgaoService->obterCedecs();

        return Inertia::render('Compdec/OrgaoCreate', [
            'cedecs' => $cedecs,
        ]);
    }

    /**
     * Armazenar novo órgão (POST /compdec/orgaos)
     */
    public function store(): RedirectResponse
    {
        $this->authorize('create', Orgao::class);

        try {
            $dto = OrgaoDTO::fromRequest(request()->validate([
                'codigo' => 'required|string|max:50|unique:orgaos',
                'nome' => 'required|string|max:255',
                'tipo' => 'required|in:cedec,redec,compdec',
                'municipio_id' => 'nullable|integer|exists:municipios,id',
                'orgao_superior_id' => 'nullable|integer|exists:orgaos,id',
                'status' => 'required|in:ativo,inativo,em_implantacao,suspenso',
                'email' => 'nullable|email|max:255',
                'telefone' => 'nullable|string|max:20',
                'endereco' => 'nullable|string',
                'responsavel_nome' => 'nullable|string|max:255',
                'responsavel_cpf' => 'nullable|string|max:14',
                'responsavel_telefone' => 'nullable|string|max:20',
                'responsavel_email' => 'nullable|email|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'abrangencia' => 'nullable|array',
                'metadata' => 'nullable|array',
            ]));

            $orgao = $this->orgaoService->criarOrgao($dto);

            return redirect()
                ->route('compdec.show', $orgao->id)
                ->with('success', "Órgão '{$orgao->nome}' criado com sucesso!");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Exibir órgão específico (GET /compdec/orgaos/{id})
     */
    public function show(Orgao $orgao): Response | RedirectResponse
    {
        try {
            $this->authorize('view', $orgao);

            $entity = $this->orgaoService->obterOrgao($orgao->id);

            return Inertia::render('Compdec/OrgaoShow', [
                'orgao' => $entity->toArray(),
                'pode_editar' => $this->userCan('update', $orgao),
                'pode_deletar' => $this->userCan('delete', $orgao),
            ]);

        } catch (\Exception $e) {
            return redirect()
                ->route('compdec.index')
                ->with('error', 'Órgão não encontrado');
        }
    }

    /**
     * Exibir formulário de edição (GET /compdec/orgaos/{id}/editar)
     */
    public function edit(Orgao $orgao): Response | RedirectResponse
    {
        try {
            $this->authorize('update', $orgao);

            $entity = $this->orgaoService->obterOrgao($orgao->id);
            $cedecs = $this->orgaoService->obterCedecs();
            $redecs = $entity->orgaoSuperiorId
                ? $this->orgaoService->obterRedecs($entity->orgaoSuperiorId)
                : [];

            return Inertia::render('Compdec/OrgaoEdit', [
                'orgao' => $entity->toArray(),
                'cedecs' => $cedecs,
                'redecs' => $redecs,
            ]);

        } catch (\Exception $e) {
            return redirect()
                ->route('compdec.index')
                ->with('error', 'Órgão não encontrado');
        }
    }

    /**
     * Atualizar órgão (PUT /compdec/orgaos/{id})
     */
    public function update(Orgao $orgao): RedirectResponse
    {
        $this->authorize('update', $orgao);

        try {
            $dto = OrgaoDTO::fromRequest(request()->validate([
                'codigo' => 'required|string|max:50|unique:orgaos,codigo,'.$orgao->id,
                'nome' => 'required|string|max:255',
                'tipo' => 'required|in:cedec,redec,compdec',
                'municipio_id' => 'nullable|integer|exists:municipios,id',
                'orgao_superior_id' => 'nullable|integer|exists:orgaos,id',
                'status' => 'required|in:ativo,inativo,em_implantacao,suspenso',
                'email' => 'nullable|email|max:255',
                'telefone' => 'nullable|string|max:20',
                'endereco' => 'nullable|string',
                'responsavel_nome' => 'nullable|string|max:255',
                'responsavel_cpf' => 'nullable|string|max:14',
                'responsavel_telefone' => 'nullable|string|max:20',
                'responsavel_email' => 'nullable|email|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'abrangencia' => 'nullable|array',
                'metadata' => 'nullable|array',
            ]));

            $orgaoAtualizado = $this->orgaoService->atualizarOrgao($orgao->id, $dto);

            return redirect()
                ->route('compdec.show', $orgaoAtualizado->id)
                ->with('success', "Órgão '{$orgaoAtualizado->nome}' atualizado com sucesso!");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Deletar órgão (DELETE /compdec/orgaos/{id})
     */
    public function destroy(Orgao $orgao): RedirectResponse
    {
        $this->authorize('delete', $orgao);

        try {
            $nomOrgao = $orgao->nome;
            $this->orgaoService->deletarOrgao($orgao->id);

            return redirect()
                ->route('compdec.index')
                ->with('success', "Órgão '{$nomOrgao}' deletado com sucesso!");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Vincular usuário a órgão (POST /compdec/orgaos/{id}/usuarios)
     */
    public function vincularUsuario(Orgao $orgao): RedirectResponse
    {
        $this->authorize('update', $orgao);

        try {
            $this->orgaoService->vincularUsuarioAOrgao(
                $orgao->id,
                request()->validate([
                    'usuario_id' => 'required|integer|exists:users,id',
                ])['usuario_id']
            );

            return redirect()
                ->back()
                ->with('success', 'Usuário vinculado ao órgão com sucesso!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Helper methods for authorization checks
     */
    protected function userCan(string $ability, Orgao $orgao): bool
    {
        return request()->user()?->can($ability, $orgao) ?? false;
    }

    protected function userCanCreate(): bool
    {
        return request()->user()?->can('create', Orgao::class) ?? false;
    }
}
