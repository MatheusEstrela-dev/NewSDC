<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Acessos\Models\CadastroAcesso;
use App\Modules\Acessos\Requests\CadastroAcessoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CadastroAcessoController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'nome' => ['nullable', 'string', 'max:150'],
            'cpf' => ['nullable', 'digits:11'],
            'setor' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:pendente,aprovado,ativo,inativo,rejeitado'],
        ]);
        $query = CadastroAcesso::query();
        foreach (['nome', 'setor'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, 'ilike', '%'.$filters[$field].'%');
            }
        }
        if (! empty($filters['cpf'])) {
            $query->where('cpf', $filters['cpf']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $paginator = $query->latest('id')->paginate(15)->withQueryString();
        $paginator->through(static fn (CadastroAcesso $cadastro): array => [
            'id' => $cadastro->id,
            'nome' => $cadastro->nome,
            'login_ad' => $cadastro->login_ad,
            'setor' => $cadastro->setor,
            'cargo' => $cadastro->cargo,
            'status' => $cadastro->status,
            'status_ad' => $cadastro->status_ad,
            'cpf_final' => substr($cadastro->cpf, -4),
        ]);

        return Inertia::render('Acessos/Index', [
            'cadastros' => $paginator,
            'filters' => $filters,
            'ativos' => CadastroAcesso::query()->where('status', 'ativo')->count(),
        ]);
    }

    public function show(CadastroAcesso $cadastro): Response
    {
        $details = $cadastro->only([
            'id', 'nome', 'tipo_documento', 'documento', 'cpf', 'login_ad',
            'email_corporativo', 'email_pessoal', 'telefone_mesa', 'telefone_whatsapp',
            'setor', 'posto', 'cargo', 'observacoes_ti', 'status', 'status_ad',
            'data_solicitacao', 'data_aprovacao',
        ]);
        if (! request()->user()->can('acessos.cadastros.edit')) {
            $details['cpf'] = '***'.substr($cadastro->cpf, -4);
            $details['documento'] = '***'.substr($cadastro->documento, -4);
            $details['email_pessoal'] = null;
            $details['telefone_whatsapp'] = null;
            $details['observacoes_ti'] = null;
        }

        return Inertia::render('Acessos/Show', [
            'cadastro' => $details,
            'auditoria' => $cadastro->auditoria()->latest('id')->limit(50)->get(['id', 'acao', 'actor_id', 'created_at']),
        ]);
    }

    public function store(CadastroAcessoRequest $request): RedirectResponse
    {
        $cadastro = DB::transaction(function () use ($request): CadastroAcesso {
            $cadastro = CadastroAcesso::create([
                ...$request->validated(),
                'solicitado_por_id' => $request->user()->id,
                'data_solicitacao' => now(),
            ]);
            $cadastro->auditoria()->create(['actor_id' => $request->user()->id, 'acao' => 'criado', 'created_at' => now()]);
            return $cadastro;
        });
        return redirect()->route('acessos.show', $cadastro)->with('success', 'Cadastro criado.');
    }

    public function update(CadastroAcessoRequest $request, CadastroAcesso $cadastro): RedirectResponse
    {
        DB::transaction(function () use ($request, $cadastro): void {
            $cadastro->update($request->validated());
            $cadastro->auditoria()->create(['actor_id' => $request->user()->id, 'acao' => 'editado', 'created_at' => now()]);
        });
        return redirect()->back()->with('success', 'Cadastro atualizado.');
    }

    public function aprovar(Request $request, CadastroAcesso $cadastro): RedirectResponse
    {
        DB::transaction(function () use ($request, $cadastro): void {
            $locked = CadastroAcesso::query()->lockForUpdate()->findOrFail($cadastro->id);
            abort_unless($locked->status === 'pendente', 409, 'Cadastro não está pendente.');
            abort_if($locked->solicitado_por_id === $request->user()->id, 403, 'O solicitante não pode aprovar o próprio cadastro.');
            $locked->update([
                'status' => 'aprovado',
                'aprovado_por_id' => $request->user()->id,
                'data_aprovacao' => now(),
            ]);
            $locked->auditoria()->create(['actor_id' => $request->user()->id, 'acao' => 'aprovado', 'created_at' => now()]);
        });
        return redirect()->back()->with('success', 'Cadastro aprovado.');
    }

    public function alterarStatus(Request $request, CadastroAcesso $cadastro): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['ativo', 'inativo', 'rejeitado'])]]);
        DB::transaction(function () use ($request, $cadastro, $data): void {
            $locked = CadastroAcesso::query()->lockForUpdate()->findOrFail($cadastro->id);
            $allowed = match ($locked->status) {
                'pendente' => ['rejeitado'],
                'aprovado' => ['ativo', 'inativo'],
                'ativo' => ['inativo'],
                'inativo' => ['ativo'],
                default => [],
            };
            abort_unless(in_array($data['status'], $allowed, true), 409, 'Transição de status não permitida.');
            $previous = $locked->status;
            $locked->update(['status' => $data['status']]);
            $locked->auditoria()->create([
                'actor_id' => $request->user()->id,
                'acao' => 'status_alterado',
                'dados' => ['anterior' => $previous, 'novo' => $data['status']],
                'created_at' => now(),
            ]);
        });
        return redirect()->back()->with('success', 'Status atualizado.');
    }
}
