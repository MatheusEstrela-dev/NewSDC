<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Cidadao;
use App\Modules\Treinamento\Requests\Portal\RegisterCidadaoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Treinamento/Portal/Registrar');
    }

    public function store(RegisterCidadaoRequest $request): RedirectResponse
    {
        $cidadao = Cidadao::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'cpf' => $request->validated('cpf'),
            'telefone' => $request->validated('telefone'),
            // Nao usar Hash::make aqui: o cast 'password' => 'hashed' do model
            // Cidadao ja hasheia sozinho ao salvar. Fazer os dois hashearia
            // duas vezes e nenhum login futuro bateria com a senha em texto puro.
            'password' => $request->validated('password'),
            'aceite_lgpd_em' => now(),
        ]);

        Auth::guard('cidadao')->login($cidadao);

        $request->session()->regenerate();

        return redirect()->route('portal.treinamento.catalogo')
            ->with('success', 'Cadastro realizado com sucesso! Bem-vindo(a) ao Portal de Treinamentos.');
    }
}
