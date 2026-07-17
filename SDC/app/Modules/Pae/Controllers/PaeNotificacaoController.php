<?php

declare(strict_types=1);

namespace App\Modules\Pae\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pae\Models\PaeNotificacao;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Requests\EmitirNotificacaoRequest;
use App\Modules\Pae\Requests\RegistrarDevolutivaRequest;
use App\Modules\Pae\Services\PaeNotificacaoService;
use Illuminate\Http\RedirectResponse;

class PaeNotificacaoController extends Controller
{
    public function __construct(
        private readonly PaeNotificacaoService $service
    ) {}

    public function store(EmitirNotificacaoRequest $request, PaeProtocolo $paeProtocolo): RedirectResponse
    {
        $notificacao = $this->service->emitir($paeProtocolo, $request->user(), $request->validated());

        return back()->with('success', "Notificacao SEI {$notificacao->num_sei} emitida com prazo de 30 dias.");
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
