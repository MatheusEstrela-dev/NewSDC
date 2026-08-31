<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Cidadao;
use App\Modules\Treinamento\Requests\Portal\RegisterCidadaoRequest;
use App\Modules\Treinamento\Services\CidadaoVerificacaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function __construct(
        private readonly CidadaoVerificacaoService $verificacao,
    ) {
    }

    public function create(): Response
    {
        return Inertia::render('Treinamento/Portal/Registrar');
    }

    /**
     * Cadastro do cidadao em duas etapas (double opt-in).
     *
     * NAO autentica aqui. Antes, quem preenchia o formulario entrava no portal
     * na mesma requisicao, sem nunca provar que era dono do e-mail nem do CPF
     * informado - qualquer um criava conta em nome de terceiro e recebia no
     * proprio e-mail os certificados e avisos daquela pessoa. Agora a conta
     * nasce com email_verified_at null e so autentica depois do codigo
     * confirmado (Portal\VerificarEmailController).
     */
    public function store(RegisterCidadaoRequest $request): RedirectResponse
    {
        // emitir() dentro da MESMA transacao: se a emissao do codigo falhar, o
        // cadastro nao pode ficar de pe sem pedido de verificacao nenhum - a
        // pessoa cairia na tela de confirmacao sem nada para confirmar. O
        // Mail::queue(...->afterCommit()) de dentro do service so dispara no
        // commit desta transacao externa, entao nao ha e-mail de cadastro que
        // acabou revertido.
        $cidadao = DB::transaction(function () use ($request) {
            $this->descartarCadastrosPendentes(
                $request->validated('cpf'),
                $request->validated('email'),
            );

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

            $this->verificacao->emitir($cidadao, $request);

            return $cidadao;
        });

        $request->session()->put(VerificarEmailController::SESSION_KEY, $cidadao->id);

        return redirect()->route('portal.treinamento.verificar-email')
            ->with('success', 'Enviamos um codigo de 6 numeros para o seu e-mail. Digite-o abaixo para concluir o cadastro.');
    }

    /**
     * Apaga cadastros que nunca confirmaram o e-mail e que ocupam o CPF ou o
     * e-mail informados agora.
     *
     * Sem isso o gate de verificacao criaria uma trava permanente: bastaria
     * cadastrar o CPF de alguem com um e-mail qualquer para que a pessoa nunca
     * mais conseguisse se cadastrar, e ela nao teria como provar nada, porque
     * ninguem confirmou aquele e-mail. Um registro que nao provou posse do
     * e-mail nao tem direito adquirido sobre o CPF.
     *
     * forceDelete e withTrashed porque o unique e no indice do banco, que ignora
     * deleted_at: soft delete nao libera a coluna. Seguro porque cadastro nao
     * confirmado nunca autenticou e portanto nao tem inscricao, certificado nem
     * presenca vinculados; os pedidos de verificacao pendentes caem por cascade.
     *
     * As regras de unique() do RegisterCidadaoRequest so contam registros com
     * email_verified_at preenchido, entao um cadastro confirmado nunca chega
     * aqui - ele e recusado na validacao, com a mensagem que orienta a entrar.
     */
    private function descartarCadastrosPendentes(string $cpf, string $email): void
    {
        Cidadao::withTrashed()
            ->whereNull('email_verified_at')
            ->where(fn ($q) => $q->where('cpf', $cpf)->orWhere('email', $email))
            ->get()
            ->each
            ->forceDelete();
    }
}
