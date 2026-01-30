<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Models\User;
use App\Modules\Compdec\Domain\Entities\Orgao;
use App\Modules\Compdec\Domain\ValueObjects\TipoOrgao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        $municipios = Municipio::orderBy('nome')->get(['id', 'nome']);
        
        return Inertia::render('Auth/Reset', [
            'municipios' => $municipios,
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'reset_type' => 'required|in:cpf,municipio',
            'cpf' => 'required_if:reset_type,cpf',
            'id_municipio' => 'required_if:reset_type,municipio',
        ]);

        $emails = [];

        if ($request->reset_type === 'cpf') {
            $cpf = preg_replace('/[^0-9]/', '', $request->cpf);
            $user = User::where('cpf', $cpf)->first();
            
            if ($user && $user->email) {
                $emails[] = $user->email;
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
                            $emails[] = $coord->email;
                        }
                    }
                } else {
                    // Se não houver coordenador, envia para todos os usuários do órgão
                    foreach ($orgao->usuarios as $user) {
                        if ($user->email) {
                            $emails[] = $user->email;
                        }
                    }
                }
            }
        }

        if (empty($emails)) {
            return back()->with('error', 'Usuário não encontrado com os dados informados.');
        }

        // Remove duplicatas e emails vazios
        $emails = array_unique(array_filter($emails));
        $sent = false;

        foreach ($emails as $email) {
            // Password::sendResetLink busca o usuário pelo email e envia o link
            $status = Password::sendResetLink(['email' => $email]);
            
            if ($status == Password::RESET_LINK_SENT) {
                $sent = true;
            }
        }

        if ($sent) {
            return back()->with('success', 'Link de redefinição enviado para o(s) e-mail(s) vinculado(s). Verifique sua caixa de entrada e spam.');
        }

        return back()->with('error', 'Não foi possível enviar o e-mail de redefinição. Verifique se o e-mail do usuário está correto ou contate o suporte.');
    }
}