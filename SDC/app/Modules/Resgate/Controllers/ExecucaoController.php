<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Resgate\Enums\EscopoCarteira;
use App\Modules\Resgate\Exceptions\RegraDoResgate;
use App\Modules\Resgate\Services\BloqueiosDoResgate;
use App\Modules\Resgate\Services\DossieDoPedido;
use App\Modules\Resgate\Services\DocumentosDoPedido;
use App\Modules\Resgate\Services\ExecucaoDoResgate;
use App\Modules\Resgate\Services\PedidoResgate;
use App\Modules\Resgate\Support\Rastro;
use App\Modules\Resgate\Support\VisaoDoPedido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Execucao do pedido aprovado - Fase 4: termo com SEI, assinaturas das duas
 * partes, entrega com evidencias e confirmacao/contestacao pelo municipio.
 *
 * Lado do Estado: resgate.aprovar (termo, assinatura do Estado) e
 * resgate.entregar (entrega). Lado do municipio: resgate.solicitar e o pedido
 * precisa ser do ente do usuario (VisaoDoPedido::agePeloEnte).
 */
final class ExecucaoController extends Controller
{
    /** Numero do documento no SEI. */
    private const DOCUMENTO_SEI = '/^\d{6,12}$/';

    public function termo(Request $request, int $pedido, ExecucaoDoResgate $execucao): RedirectResponse
    {
        abort_unless($request->user()?->can('resgate.aprovar'), 403);
        $dados = $request->validate([
            'documento_sei' => ['required', 'string', 'regex:' . self::DOCUMENTO_SEI],
            'termo' => ['required', 'file', 'mimes:pdf', 'max:' . (int) config('resgate.anexo_max_kb')],
        ]);

        $this->traduzir(fn () => $execucao->emitirTermo($pedido, $dados['documento_sei'], $request->file('termo'), (int) $request->user()->id, Rastro::daRequisicao($request)));

        return back()->with('success', 'Termo registrado. Aguarda a assinatura das duas partes no SEI.');
    }

    public function assinar(Request $request, int $pedido, ExecucaoDoResgate $execucao, PedidoResgate $pedidos, VisaoDoPedido $visao): RedirectResponse
    {
        $dados = $request->validate([
            'parte' => ['required', Rule::in(['estado', 'municipio'])],
            'documento_sei' => ['required', 'string', 'regex:' . self::DOCUMENTO_SEI],
        ]);
        $user = $request->user();
        if ($dados['parte'] === 'estado') {
            abort_unless($user?->can('resgate.aprovar'), 403);
        } else {
            $this->exigirLadoDoEnte($pedido, $pedidos, $visao, $request);
        }

        $this->traduzir(fn () => $execucao->assinar($pedido, $dados['parte'], $dados['documento_sei'], (int) $user->id, Rastro::daRequisicao($request)));

        return back()->with('success', 'Assinatura registrada.');
    }

    public function entregar(Request $request, int $pedido, ExecucaoDoResgate $execucao): RedirectResponse
    {
        abort_unless($request->user()?->can('resgate.entregar'), 403);
        $request->validate([
            'observacao' => ['required', 'string', 'min:10', 'max:2000'],
            'evidencias' => ['required', 'array', 'min:1', 'max:10'],
            'evidencias.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:' . (int) config('resgate.anexo_max_kb')],
        ]);

        $this->traduzir(fn () => $execucao->entregar($pedido, $request->file('evidencias', []), (string) $request->input('observacao'), (int) $request->user()->id, Rastro::daRequisicao($request)));

        return back()->with('success', 'Entrega registrada. Aguarda a confirmação de recebimento pelo município.');
    }

    public function confirmar(Request $request, int $pedido, ExecucaoDoResgate $execucao, PedidoResgate $pedidos, VisaoDoPedido $visao): RedirectResponse
    {
        $this->exigirLadoDoEnte($pedido, $pedidos, $visao, $request);
        $dados = $request->validate(['observacao' => ['required', 'string', 'min:10', 'max:2000']]);

        $this->traduzir(fn () => $execucao->confirmar($pedido, $dados['observacao'], (int) $request->user()->id, Rastro::daRequisicao($request)));

        return back()->with('success', 'Recebimento confirmado. Resgate concluído e pontos debitados.');
    }

    public function contestar(Request $request, int $pedido, ExecucaoDoResgate $execucao, PedidoResgate $pedidos, VisaoDoPedido $visao): RedirectResponse
    {
        $this->exigirLadoDoEnte($pedido, $pedidos, $visao, $request);
        $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:2000'],
            'anexos' => ['nullable', 'array', 'max:10'],
            'anexos.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:' . (int) config('resgate.anexo_max_kb')],
        ]);

        $this->traduzir(fn () => $execucao->contestar($pedido, (string) $request->input('motivo'), $request->file('anexos', []), (int) $request->user()->id, Rastro::daRequisicao($request)));

        return back()->with('success', 'Contestação registrada. A unidade responsável precisa refazer a entrega.');
    }

    /** Saida administrativa antes da conclusao, com documento de origem. */
    public function anular(Request $request, int $pedido, ExecucaoDoResgate $execucao): RedirectResponse
    {
        abort_unless($request->user()?->can('resgate.aprovar'), 403);
        $dados = $request->validate([
            'documento_origem' => ['required', 'string', 'max:120'],
            'motivo' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $this->traduzir(fn () => $execucao->anular($pedido, $dados['documento_origem'], $dados['motivo'], (int) $request->user()->id, Rastro::daRequisicao($request)));

        return back()->with('success', 'Pedido anulado; pontos e unidade liberados.');
    }

    /** Bloqueio judicial/administrativo do pedido ou do ente inteiro (P7). */
    public function bloquear(Request $request, int $pedido, BloqueiosDoResgate $bloqueios, PedidoResgate $pedidos): RedirectResponse
    {
        abort_unless($request->user()?->can('resgate.bloquear'), 403);
        $dados = $request->validate([
            'alvo' => ['required', Rule::in(['pedido', 'ente'])],
            'tipo' => ['required', Rule::in(['judicial', 'administrativo'])],
            'documento_origem' => ['required', 'string', 'max:120'],
            'motivo' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $detalhe = $pedidos->detalhe($pedido);
        abort_if($detalhe === null, 404);
        $autor = (int) $request->user()->id;
        $rastro = Rastro::daRequisicao($request);

        $dados['alvo'] === 'pedido'
            ? $bloqueios->bloquearPedido($pedido, $dados['tipo'], $dados['documento_origem'], $dados['motivo'], $autor, $rastro)
            : $bloqueios->bloquearEnte(EscopoCarteira::from($detalhe['pedido']['ente_escopo']), $detalhe['pedido']['ente_id'], $dados['tipo'], $dados['documento_origem'], $dados['motivo'], $autor, $rastro);

        return back()->with('success', 'Bloqueio registrado. Nenhuma etapa avança até o encerramento.');
    }

    public function encerrarBloqueio(Request $request, int $pedido, int $bloqueio, BloqueiosDoResgate $bloqueios): RedirectResponse
    {
        abort_unless($request->user()?->can('resgate.bloquear'), 403);
        $dados = $request->validate(['motivo' => ['required', 'string', 'min:10', 'max:2000']]);

        $this->traduzir(fn () => $bloqueios->encerrar($bloqueio, $dados['motivo'], (int) $request->user()->id));

        return back()->with('success', 'Bloqueio encerrado.');
    }

    /** Dossie do pedido em JSON, com o SHA-256 do proprio conteudo. */
    public function dossie(Request $request, int $pedido, DossieDoPedido $dossies, PedidoResgate $pedidos, VisaoDoPedido $visao): JsonResponse
    {
        $detalhe = $pedidos->detalhe($pedido);
        abort_if($detalhe === null, 404);
        abort_unless($visao->podeVer($request->user(), $detalhe['pedido']), 403);
        $dossie = $dossies->montar($pedido);

        return response()->json($dossie, 200, [
            'Content-Disposition' => 'attachment; filename="dossie-' . $detalhe['pedido']['protocolo'] . '.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /** Download do anexo com o hash conferido; arquivo adulterado nao e servido. */
    public function documento(Request $request, int $pedido, int $documento, PedidoResgate $pedidos, DocumentosDoPedido $documentos, VisaoDoPedido $visao): StreamedResponse
    {
        $detalhe = $pedidos->detalhe($pedido);
        abort_if($detalhe === null, 404);
        abort_unless($visao->podeVer($request->user(), $detalhe['pedido']), 403);

        $doc = $documentos->abrir(DB::connection((string) config('resgate.conexao')), $pedido, $documento);
        abort_if($doc === null, 404);
        abort_unless($doc['integro'], 409, 'O arquivo não confere com o hash registrado.');

        return Storage::disk((string) config('resgate.disco'))->download($doc['caminho'], $doc['nome'], ['Content-Type' => $doc['mime']]);
    }

    private function exigirLadoDoEnte(int $pedidoId, PedidoResgate $pedidos, VisaoDoPedido $visao, Request $request): void
    {
        $detalhe = $pedidos->detalhe($pedidoId);
        abort_if($detalhe === null, 404);
        abort_unless($visao->agePeloEnte($request->user(), $detalhe['pedido']), 403);
    }

    private function traduzir(callable $operacao): void
    {
        try {
            $operacao();
        } catch (RegraDoResgate $e) {
            throw ValidationException::withMessages([$e->campo => $e->getMessage()]);
        }
    }
}
