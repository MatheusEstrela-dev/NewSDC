<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Enums\StatusEscala;
use App\Modules\Plantao\Exceptions\PassagemInvalidaException;
use App\Modules\Plantao\Models\EscalaItem;
use App\Modules\Plantao\Services\PassagemServicoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Assumir o turno a partir da vaga escalada.
 *
 * Este controller NAO abre turno por conta propria: delega ao
 * PassagemServicoService::abrirTurno(), o mesmo caminho do botao "Abrir
 * Plantao" da tela de indice. A escala apenas pre-preenche data, periodo e
 * plantonista, e amarra o turno criado a vaga por escala_item_id.
 *
 * Ter um segundo caminho de abertura foi exatamente o defeito F-6 da release
 * anterior, em que a maquina de estados existia sem porta de entrada.
 */
class EscalaAssumirController extends Controller
{
    public function __construct(
        private readonly PassagemServicoService $passagemService
    ) {
    }

    public function __invoke(Request $request, EscalaItem $item): RedirectResponse
    {
        $usuario = $request->user();

        // A vaga e pessoal. Diferente do encerramento -- que tem excecao de
        // supervisao porque o handshake travaria sem ela -- aqui nao ha
        // impasse a destravar: quem nao foi escalado e quer trabalhar abre
        // turno pelo botao normal, que aceita qualquer data e periodo.
        if ((int) $item->plantonista_id !== (int) $usuario->id) {
            abort(403, 'Esta vaga da escala pertence a outro plantonista.');
        }

        $item->loadMissing(['escala', 'tipoTurno', 'plantao']);

        $status = $item->escala?->status;

        if (!$status instanceof StatusEscala || !$status->publicada()) {
            return back()->withErrors([
                'vaga' => 'A escala ainda nao foi publicada.',
            ]);
        }

        if ($item->plantao !== null) {
            return back()->withErrors([
                'vaga' => 'Esta vaga ja foi assumida.',
            ]);
        }

        if ($item->tipoTurno === null) {
            return back()->withErrors([
                'vaga' => 'A vaga esta sem tipo de turno valido.',
            ]);
        }

        try {
            $this->passagemService->abrirTurno([
                'plantonista_id' => (int) $usuario->id,
                'data' => $item->data->toDateString(),
                // O codigo, nao o id: e o que plantoes.periodo guarda.
                'periodo' => $item->tipoTurno->codigo,
                'escala_item_id' => (int) $item->getKey(),
            ]);
        } catch (PassagemInvalidaException $e) {
            return back()->withErrors(['vaga' => $e->getMessage()]);
        }

        return redirect()
            ->route('plantao.index')
            ->with('success', 'Turno assumido a partir da escala.');
    }
}
