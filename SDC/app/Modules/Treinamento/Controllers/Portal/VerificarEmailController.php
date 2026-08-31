<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Exceptions\Auth\EmailChange\CodeExpiredException;
use App\Exceptions\Auth\EmailChange\InvalidCodeException;
use App\Exceptions\Auth\EmailChange\MaxResendsReachedException;
use App\Exceptions\Auth\EmailChange\ResendCooldownException;
use App\Exceptions\Auth\EmailChange\TooManyAttemptsException;
use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Cidadao;
use App\Modules\Treinamento\Models\CidadaoEmailVerificacao;
use App\Modules\Treinamento\Services\CidadaoVerificacaoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tela de confirmacao do codigo enviado por e-mail no cadastro do portal.
 *
 * Fora do grupo 'auth:cidadao' de proposito: quem esta aqui ainda NAO esta
 * autenticado - e exatamente esse o ponto do gate. A conta em verificacao vem
 * da session (SESSION_KEY), nunca do request, entao nao ha como pedir o codigo
 * de outra pessoa trocando um id na URL.
 */
class VerificarEmailController extends Controller
{
    /**
     * Id do cidadao aguardando confirmacao. Escrita por
     * Portal\RegisterController (apos o cadastro) e por
     * Auth\AuthenticatedSessionController (quando alguem com cadastro pendente
     * tenta entrar).
     */
    public const SESSION_KEY = 'portal_treinamento_verificacao_cidadao_id';

    public function __construct(
        private readonly CidadaoVerificacaoService $verificacao,
    ) {
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $cidadao = $this->cidadaoPendente($request);

        if (!$cidadao) {
            return redirect()->route('portal.treinamento.registrar');
        }

        return Inertia::render('Treinamento/Portal/VerificarEmail', [
            'emailMascarado' => $this->mascararEmail($cidadao->email),
            'ttlMinutos' => CidadaoEmailVerificacao::TTL_MINUTES,
            'maxTentativas' => CidadaoEmailVerificacao::MAX_ATTEMPTS,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $cidadao = $this->cidadaoPendente($request);

        if (!$cidadao) {
            return redirect()->route('portal.treinamento.registrar');
        }

        $request->validate(
            ['codigo' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/']],
            ['codigo.regex' => 'O codigo tem 6 numeros.', 'codigo.size' => 'O codigo tem 6 numeros.'],
        );

        try {
            $this->verificacao->confirmar($cidadao, (string) $request->input('codigo'));
        } catch (ModelNotFoundException) {
            // Nao existe pedido ativo (expirou e foi cancelado, ou estourou as
            // tentativas). Manda pedir um novo em vez de dar erro tecnico.
            throw ValidationException::withMessages([
                'codigo' => 'Este codigo nao vale mais. Clique em "Reenviar codigo" para receber um novo.',
            ]);
        } catch (CodeExpiredException) {
            throw ValidationException::withMessages([
                'codigo' => 'O codigo expirou. Clique em "Reenviar codigo" para receber um novo.',
            ]);
        } catch (TooManyAttemptsException) {
            throw ValidationException::withMessages([
                'codigo' => 'Numero de tentativas excedido. Clique em "Reenviar codigo" para receber um novo.',
            ]);
        } catch (InvalidCodeException $e) {
            throw ValidationException::withMessages([
                'codigo' => $e->remaining > 0
                    ? "Codigo incorreto. Voce ainda tem {$e->remaining} tentativa(s)."
                    : 'Codigo incorreto. Clique em "Reenviar codigo" para receber um novo.',
            ]);
        }

        // So aqui a conta passa a existir de verdade para o sistema.
        $request->session()->forget(self::SESSION_KEY);

        auth('cidadao')->login($cidadao);

        // Mesmo registro que CidadaoAuthService::attempt() faz: confirmar o
        // codigo tambem e uma entrada no portal, e sem isso a conta ficaria com
        // last_login_at nulo mesmo estando logada.
        $cidadao->forceFill(['last_login_at' => now()])->saveQuietly();

        $request->session()->regenerate();

        return redirect()->route('portal.treinamento.catalogo')
            ->with('success', 'E-mail confirmado! Bem-vindo(a) ao Portal de Treinamentos.');
    }

    /**
     * @throws ValidationException
     */
    public function resend(Request $request): RedirectResponse
    {
        $cidadao = $this->cidadaoPendente($request);

        if (!$cidadao) {
            return redirect()->route('portal.treinamento.registrar');
        }

        try {
            $this->verificacao->reenviar($cidadao);
        } catch (ModelNotFoundException) {
            throw ValidationException::withMessages([
                'codigo' => 'Nao ha cadastro aguardando confirmacao. Faca o cadastro novamente.',
            ]);
        } catch (MaxResendsReachedException) {
            throw ValidationException::withMessages([
                'codigo' => 'Limite de reenvios atingido. Faca o cadastro novamente mais tarde.',
            ]);
        } catch (ResendCooldownException $e) {
            throw ValidationException::withMessages([
                'codigo' => "Aguarde {$e->secondsRemaining} segundos para pedir um novo codigo.",
                'retry_after' => (string) $e->secondsRemaining,
            ]);
        }

        return back()->with('success', 'Enviamos um novo codigo para o seu e-mail.');
    }

    /**
     * Conta em verificacao, sempre resolvida pela session. Cadastro que ja
     * confirmou nao volta para esta tela.
     */
    private function cidadaoPendente(Request $request): ?Cidadao
    {
        $id = $request->session()->get(self::SESSION_KEY);

        if (!is_int($id) && !ctype_digit((string) $id)) {
            return null;
        }

        $cidadao = Cidadao::find((int) $id);

        if (!$cidadao || $cidadao->emailVerificado()) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        return $cidadao;
    }

    /**
     * "matheus.estrela@defesacivil.mg.gov.br" -> "mat***@defesacivil.mg.gov.br".
     * A tela precisa lembrar a pessoa de qual caixa checar sem imprimir o
     * endereco inteiro numa pagina que qualquer um com a session aberta ve.
     */
    private function mascararEmail(string $email): string
    {
        [$local, $dominio] = array_pad(explode('@', $email, 2), 2, '');

        if ($dominio === '') {
            return str_repeat('*', mb_strlen($email));
        }

        $visivel = mb_substr($local, 0, min(3, max(1, mb_strlen($local) - 1)));

        return $visivel . '***@' . $dominio;
    }
}
