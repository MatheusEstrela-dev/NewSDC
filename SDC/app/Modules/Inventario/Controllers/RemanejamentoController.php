<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Inventario\Queries\OpcoesRemanejamentoQuery;
use App\Modules\Inventario\Requests\SalvarRemanejamentoRequest;
use App\Modules\Inventario\Services\EnviarRemanejamentoSeplag;
use App\Modules\Inventario\Services\PlanilhaRemanejamento;
use App\Modules\Inventario\Services\RegistrarChamadoDoLote;
use App\Modules\Inventario\Services\RemanejamentoService;
use App\Modules\Inventario\Support\RemanejamentoApresentacao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller fino: RemanejamentoProibido e ValidationException, entao erro de
 * dominio volta como erro de sessao por chave sem try/catch aqui.
 */
class RemanejamentoController extends Controller
{
    public function __construct(
        private readonly RemanejamentoService $service,
        private readonly RegistrarChamadoDoLote $chamado,
        private readonly EnviarRemanejamentoSeplag $seplag,
        private readonly PlanilhaRemanejamento $planilha,
        private readonly OpcoesRemanejamentoQuery $opcoes,
        private readonly RemanejamentoApresentacao $apresentacao,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Inventario/RemanejamentoForm', [
            'remanejamento' => null,
            'opcoes' => $this->opcoes->paraFormulario(),
        ]);
    }

    public function store(SalvarRemanejamentoRequest $request): RedirectResponse
    {
        $this->service->registrar($request->dados());

        return redirect()->route('inventario.movimentacoes.index')->with('success', 'Remanejamento registrado.');
    }

    public function edit(Remanejamento $remanejamento): Response|RedirectResponse
    {
        if (! $remanejamento->estaAtivo()) {
            return redirect()->route('inventario.movimentacoes.index')->withErrors(['remanejamento' => 'Lote já desfeito.']);
        }

        return Inertia::render('Inventario/RemanejamentoForm', [
            'remanejamento' => $this->apresentacao->paraFormulario($remanejamento),
            'opcoes' => $this->opcoes->paraFormulario(),
        ]);
    }

    public function update(SalvarRemanejamentoRequest $request, Remanejamento $remanejamento): RedirectResponse
    {
        $this->service->editar($remanejamento, $request->dados());

        return redirect()->route('inventario.movimentacoes.index')->with('success', 'Remanejamento atualizado.');
    }

    public function desfazer(Request $request, Remanejamento $remanejamento): RedirectResponse
    {
        $this->service->desfazer($remanejamento, (int) $request->user()->id);

        return redirect()->back()->with('success', 'Lote desfeito. Equipamentos e estações voltaram ao estado anterior.');
    }

    public function planilha(Remanejamento $remanejamento): HttpResponse
    {
        return response($this->planilha->gerar($remanejamento), 200, [
            'Content-Type' => PlanilhaRemanejamento::MIME,
            'Content-Disposition' => 'attachment; filename="'.$this->planilha->nomeArquivo($remanejamento).'"',
        ]);
    }

    public function chamado(Request $request, Remanejamento $remanejamento): RedirectResponse
    {
        $demanda = $this->chamado->executar($remanejamento, (int) $request->user()->id);

        return redirect()->back()->with('success', 'Chamado '.$demanda->protocolo.' registrado.');
    }

    public function seplag(Request $request, Remanejamento $remanejamento): RedirectResponse
    {
        $lote = $this->seplag->executar($remanejamento, (int) $request->user()->id);

        return redirect()->back()->with('success', "Planilha enviada à SEPLAG ({$lote->seplag_envios}º envio).");
    }
}
