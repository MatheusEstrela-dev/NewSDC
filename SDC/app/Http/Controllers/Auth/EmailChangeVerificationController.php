<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\Auth\EmailChange\CodeExpiredException;
use App\Exceptions\Auth\EmailChange\InvalidCodeException;
use App\Exceptions\Auth\EmailChange\MaxResendsReachedException;
use App\Exceptions\Auth\EmailChange\ResendCooldownException;
use App\Exceptions\Auth\EmailChange\TooManyAttemptsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyEmailChangeRequest;
use App\Models\EmailChangeRequest;
use App\Services\Auth\EmailChangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EmailChangeVerificationController extends Controller
{
    public function __construct(private readonly EmailChangeService $service)
    {
    }

    public function verify(VerifyEmailChangeRequest $request): RedirectResponse
    {
        $user = $request->user();
        $code = $request->string('code')->value();

        try {
            $this->service->confirmChange($user, $code);
        } catch (InvalidCodeException $e) {
            return back()->withErrors([
                'code' => "Codigo invalido. Restam {$e->remaining} tentativa(s).",
            ]);
        } catch (CodeExpiredException) {
            return back()->withErrors([
                'code' => 'Codigo expirado. Solicite um novo.',
            ]);
        } catch (TooManyAttemptsException) {
            return back()->withErrors([
                'code' => 'Limite de tentativas atingido. Solicite um novo codigo.',
            ]);
        }

        // Email + email_verified_at mudaram -> cache do payload Inertia
        // precisa ser purgado (mesmo padrao do FirstAccessController).
        Cache::forget("inertia_user_data_{$user->id}");

        return back()->with('success', 'E-mail atualizado e verificado com sucesso.');
    }

    public function resend(Request $request): RedirectResponse
    {
        try {
            $this->service->resendCode($request->user());
        } catch (ResendCooldownException $e) {
            return back()->withErrors([
                'resend' => "Aguarde {$e->secondsRemaining}s antes de reenviar.",
            ]);
        } catch (MaxResendsReachedException) {
            return back()->withErrors([
                'resend' => 'Limite de reenvios atingido. Cancele e tente novamente.',
            ]);
        } catch (CodeExpiredException) {
            return back()->withErrors([
                'resend' => 'O pedido expirou. Cancele e abra um novo.',
            ]);
        }

        return back()->with('success', 'Novo codigo enviado.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        EmailChangeRequest::activeFor($request->user()->id)
            ->update(['cancelled_at' => now()]);

        Cache::forget("inertia_user_data_{$request->user()->id}");

        return back()->with('success', 'Pedido de troca de e-mail cancelado.');
    }
}
