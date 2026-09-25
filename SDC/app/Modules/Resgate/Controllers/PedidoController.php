<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Ranking\Enums\EscopoPlacar;
use App\Modules\Ranking\Support\NomesDeParticipantes;
use App\Modules\Resgate\Enums\EscopoCarteira;
use App\Modules\Resgate\Exceptions\RegraDoResgate;
use App\Modules\Resgate\Services\CatalogoResgate;
use App\Modules\Resgate\Services\PedidoResgate;
use App\Modules\Resgate\Support\EnteDoUsuario;
use App\Modules\Resgate\Support\Rastro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pedidos de resgate - Fase 3. O pedido nasce RESERVADO e fica assim ate a
 * CEDEC aprovar ou recusar.
 *
 * Visibilidade: o ente ve os proprios pedidos (resgate.carteira.view); quem
 * decide (resgate.aprovar) ou tem visao estadual ve todos. O ente do pedido
 * vem SEMPRE do vinculo do usuario, nunca da requisicao (D1: opera em nome
 * do proprio ente).
 */
final class PedidoController extends Controller
{
    public function index(Request $request, PedidoResgate $pedidos, NomesDeParticipantes $nomes): Response
    {
        $user = $request->user();
        abort_unless($user?->can('resgate.carteira.view') || $user?->can('resgate.aprovar'), 403);

        $dados = $request->validate(['status' => ['sometimes', 'nullable', Rule::in(['reservado', 'aprovado', 'recusado', 'cancelado', 'expirado'])]]);
        $status = $dados['status'] ?? null;
        $global = $user->can('resgate.aprovar') || $user->can('resgate.carteira.estado');

        if ($global) {
            $lista = $pedidos->listar(null, null, $status);
        } else {
            $municipio = app(EnteDoUsuario::class)->resolver($user, EscopoCarteira::Municipio, null);
            $lista = $municipio !== null ? $pedidos->listar(EscopoCarteira::Municipio, $municipio, $status) : [];
        }

        return Inertia::render('Resgate/Pedidos', [
            'pedidos' => $lista,
            'entes' => $this->nomesDosEntes($lista, $nomes),
            'usuarios' => $this->nomesDeUsuarios(array_column($lista, 'solicitado_por')),
            'filtros' => ['status' => $status],
            'podeAprovar' => $user->can('resgate.aprovar'),
            'visaoGlobal' => $global,
            'usuarioId' => (int) $user->id,
        ]);
    }

    public function novo(Request $request, CatalogoResgate $catalogo, PedidoResgate $pedidos, EnteDoUsuario $entes, NomesDeParticipantes $nomes): Response
    {
        $user = $request->user();
        abort_unless($user?->can('resgate.solicitar'), 403);

        $codigo = strtoupper((string) $request->query('item', ''));
        $item = collect($catalogo->vigentes())->firstWhere('codigo', $codigo);
        abort_if($item === null, 404);

        $escopo = EscopoCarteira::from($item['beneficiario']);
        $enteId = $entes->resolver($user, $escopo, null);
        $avaliacao = $enteId !== null
            ? $pedidos->avaliar($escopo, $enteId, $codigo)
            : ['impedimentos' => ['Seu usuário não está vinculado a um ente deste tipo.'], 'saldo' => 0, 'custo' => $item['custo_pontos'], 'demonstracao' => $item['demonstracao'], 'faixa' => null];

        return Inertia::render('Resgate/PedidoNovo', [
            'item' => $item,
            'ente' => $enteId !== null ? [
                'escopo' => $escopo->value,
                'id' => $enteId,
                'nome' => $nomes->para($escopo->escopoDoPlacar(), [$enteId])[$enteId] ?? null,
            ] : null,
            'avaliacao' => $avaliacao,
            // Idempotencia: a mesma tela enviada duas vezes nao cria dois pedidos.
            'chave' => (string) Str::uuid(),
        ]);
    }

    public function solicitar(Request $request, PedidoResgate $pedidos, EnteDoUsuario $entes, CatalogoResgate $catalogo): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->can('resgate.solicitar'), 403);
        $dados = $request->validate([
            'item' => ['required', 'string', 'regex:/^[A-Za-z0-9-]{3,40}$/'],
            'chave' => ['required', 'uuid'],
            'ciente' => ['accepted'],
        ]);

        $item = collect($catalogo->vigentes())->firstWhere('codigo', strtoupper($dados['item']));
        abort_if($item === null, 404);
        $escopo = EscopoCarteira::from($item['beneficiario']);
        $enteId = $entes->resolver($user, $escopo, null);
        if ($enteId === null) {
            throw ValidationException::withMessages(['item' => 'Seu usuário não está vinculado a um ente deste tipo.']);
        }

        $id = $this->traduzirRegra(fn () => $pedidos->solicitar(
            $escopo, $enteId, $dados['item'], "pedido:{$user->id}:{$dados['chave']}", (int) $user->id, Rastro::daRequisicao($request),
        ));

        return redirect()->route('resgate.pedidos.show', $id)->with('success', 'Pedido registrado e reservado. Aguarda a decisão da CEDEC.');
    }

    public function show(Request $request, int $pedido, PedidoResgate $pedidos, NomesDeParticipantes $nomes): Response
    {
        $user = $request->user();
        $detalhe = $pedidos->detalhe($pedido);
        abort_if($detalhe === null, 404);
        $this->autorizarVisao($user, $detalhe['pedido']);

        return Inertia::render('Resgate/PedidoShow', $detalhe + [
            'entes' => $this->nomesDosEntes([$detalhe['pedido']], $nomes),
            'usuarios' => $this->nomesDeUsuarios(array_merge([$detalhe['pedido']['solicitado_por']], array_column($detalhe['eventos'], 'ator_user_id'))),
            'podeAprovar' => $user->can('resgate.aprovar'),
            'usuarioId' => (int) $user->id,
        ]);
    }

    public function decidir(Request $request, int $pedido, PedidoResgate $pedidos): RedirectResponse
    {
        abort_unless($request->user()?->can('resgate.aprovar'), 403);
        $dados = $request->validate([
            'aprovar' => ['required', 'boolean'],
            'justificativa' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $aprovar = filter_var($dados['aprovar'], FILTER_VALIDATE_BOOLEAN);

        $this->traduzirRegra(fn () => $pedidos->decidir($pedido, $aprovar, $dados['justificativa'], (int) $request->user()->id, Rastro::daRequisicao($request)));

        return back()->with('success', $aprovar ? 'Pedido aprovado. A reserva segue até a entrega.' : 'Pedido recusado; pontos e unidade liberados.');
    }

    public function cancelar(Request $request, int $pedido, PedidoResgate $pedidos): RedirectResponse
    {
        abort_unless($request->user()?->can('resgate.solicitar'), 403);
        $dados = $request->validate(['justificativa' => ['required', 'string', 'min:10', 'max:2000']]);

        $this->traduzirRegra(fn () => $pedidos->cancelar($pedido, $dados['justificativa'], (int) $request->user()->id, Rastro::daRequisicao($request)));

        return back()->with('success', 'Pedido cancelado; pontos e unidade liberados.');
    }

    /** Ente ve o proprio pedido; quem decide ou tem visao estadual ve todos. */
    private function autorizarVisao(?User $user, array $pedido): void
    {
        if ($user?->can('resgate.aprovar') || $user?->can('resgate.carteira.estado')) {
            return;
        }
        abort_unless($user?->can('resgate.carteira.view'), 403);
        $proprio = app(EnteDoUsuario::class)->resolver($user, EscopoCarteira::from($pedido['ente_escopo']), null);
        abort_unless($proprio === $pedido['ente_id'], 403);
    }

    private function traduzirRegra(callable $operacao): mixed
    {
        try {
            return $operacao();
        } catch (RegraDoResgate $e) {
            throw ValidationException::withMessages([$e->campo => $e->getMessage()]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $pedidos
     * @return array<string, string> chave "escopo:id" => nome
     */
    private function nomesDosEntes(array $pedidos, NomesDeParticipantes $nomes): array
    {
        $rotulos = [];
        foreach (['municipio' => EscopoPlacar::Municipio, 'orgao' => EscopoPlacar::Orgao] as $escopo => $placar) {
            $ids = array_column(array_filter($pedidos, static fn (array $p): bool => $p['ente_escopo'] === $escopo), 'ente_id');
            foreach ($nomes->para($placar, $ids) as $id => $nome) {
                $rotulos["{$escopo}:{$id}"] = $nome;
            }
        }

        return $rotulos;
    }

    /** @return array<int, string> */
    private function nomesDeUsuarios(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        return $ids === [] ? [] : User::query()->whereIn('id', $ids)->pluck('name', 'id')->all();
    }
}
