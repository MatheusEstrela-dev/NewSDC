<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Models\User;
use App\Modules\Compdec\Domain\Entities\Orgao;
use App\Modules\Compdec\Domain\ValueObjects\TipoOrgao;
use App\Modules\Treinamento\Models\Cidadao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        // O catalogo de municipios NAO vai mais nos props (eram ~850 linhas
        // embutidas em toda pagina publica). O front busca sob demanda em
        // municipios() conforme o usuario digita.
        return Inertia::render('Auth/Reset', [
            'status' => session('status'),
        ]);
    }

    /**
     * Autocomplete publico de municipios para o fluxo "Por Municipio".
     * Filtra o catalogo cacheado em memoria (acento-insensivel) e devolve no
     * maximo 20 itens. Exige >= 2 caracteres para nao varrer a lista inteira.
     */
    public function municipios(Request $request): JsonResponse
    {
        $termo = trim((string) $request->query('q', ''));

        if (mb_strlen($termo) < 2) {
            return response()->json([]);
        }

        $needle = (string) Str::of($termo)->lower()->ascii();

        $resultados = Municipio::catalogo()
            ->filter(fn (array $m): bool => str_contains((string) Str::of($m['nome'])->lower()->ascii(), $needle))
            ->take(20)
            ->values();

        return response()->json($resultados);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $throttleKey = 'password-reset:' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'throttle' => "Muitas tentativas. Tente novamente em {$seconds} segundos.",
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        $request->validate([
            'reset_type' => 'required|in:cpf,municipio',
            'cpf' => 'required_if:reset_type,cpf',
            'id_municipio' => 'required_if:reset_type,municipio',
        ]);

        // [email => broker] - broker decide qual tabela/provider valida o token
        // (password_reset_tokens/users vs cidadao_password_reset_tokens/cidadaos).
        $resets = [];

        if ($request->reset_type === 'cpf') {
            $cpf = preg_replace('/[^0-9]/', '', $request->cpf);
            $user = User::where('cpf', $cpf)->first();

            if ($user && $user->email) {
                $resets[$user->email] = 'users';
            } else {
                // CPF pode ser de um cidadao do Portal de Treinamentos, nao de um
                // servidor - guard separado, mesmo fluxo de "esqueci a senha".
                $cidadao = Cidadao::where('cpf', $cpf)->first();
                if ($cidadao && $cidadao->email) {
                    $resets[$cidadao->email] = 'cidadaos';
                }
            }
        } elseif ($request->reset_type === 'municipio') {
            $orgao = Orgao::where('municipio_id', $request->id_municipio)
                ->where('tipo', TipoOrgao::COMPDEC)
                ->first();

            if ($orgao) {
                // Tenta enviar para coordenadores
                $coordinators = $orgao->coordenadores;

                if ($coordinators->count() > 0) {
                    foreach ($coordinators as $coord) {
                        if ($coord->email) {
                            $resets[$coord->email] = 'users';
                        }
                    }
                } else {
                    // Se não houver coordenador, envia para todos os usuários do órgão
                    foreach ($orgao->usuarios as $user) {
                        if ($user->email) {
                            $resets[$user->email] = 'users';
                        }
                    }
                }
            }
        }

        $genericSuccessMessage = 'Se os dados informados estiverem corretos, um link de redefinicao sera enviado para o e-mail cadastrado. Verifique sua caixa de entrada e spam.';

        if (empty($resets)) {
            return back()->with('success', $genericSuccessMessage);
        }

        foreach ($resets as $email => $broker) {
            Password::broker($broker)->sendResetLink(['email' => $email]);
        }

        return back()->with('success', $genericSuccessMessage);
    }
}