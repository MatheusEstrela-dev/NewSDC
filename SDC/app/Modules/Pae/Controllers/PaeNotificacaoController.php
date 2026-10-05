<?php

declare(strict_types=1);

namespace App\Modules\Pae\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pae\Models\PaeNotificacao;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Requests\EmitirNotificacaoRequest;
use App\Modules\Pae\Requests\RegistrarDevolutivaRequest;
use App\Modules\Pae\Requests\RegistrarDilacaoRequest;
use App\Modules\Pae\Services\PaeNotificacaoService;
use App\Modules\Pae\Support\PrazoNotificacao;
use Illuminate\Http\RedirectResponse;

class PaeNotificacaoController extends Controller
{
    public function __construct(
        private readonly PaeNotificacaoService $service
    ) {}

    public function store(EmitirNotificacaoRequest $request, PaeProtocolo $paeProtocolo): RedirectResponse
    {
        $notificacao = $this->service->emitir($paeProtocolo, $request->user(), $request->validated());

        return back()->with('success', "Notificacao SEI {$notificacao->num_sei} emitida com prazo de ".PrazoNotificacao::PRAZO_DIAS.' dias.');
    }

    public function dilacao(RegistrarDilacaoRequest $request, PaeNotificacao $paeNotificacao): RedirectResponse
    {
        $dados = $request->validated();
        $this->service->registrarDilacao(
            $paeNotificacao,
            (int) $dados['dias_adicionais'],
            $dados['justificativa'],
            $request->user()
        );

        return back()->with('success', 'Dilacao registrada e prazo recalculado.');
    }

    public function devolutiva(RegistrarDevolutivaRequest $request, PaeNotificacao $paeNotificacao): RedirectResponse
    {
        $this->service->registrarDevolutiva(
            $paeNotificacao,
            $request->user(),
            $request->validated()['dt_devolutiva']
        );

        return back()->with('success', 'Devolutiva registrada com sucesso.');
    }
}
