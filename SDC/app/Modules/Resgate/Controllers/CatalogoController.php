<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Ranking\Enums\FaixaRanking;
use App\Modules\Ranking\Services\LeaderboardQuery;
use App\Modules\Ranking\Services\RankingReadService;
use App\Modules\Ranking\Services\TemporadaDoRanking;
use App\Modules\Resgate\Enums\AcaoProposta;
use App\Modules\Resgate\Enums\EscopoCarteira;
use App\Modules\Resgate\Enums\TipoItem;
use App\Modules\Resgate\Exceptions\RegraDoCatalogo;
use App\Modules\Resgate\Requests\ProporItemCatalogoRequest;
use App\Modules\Resgate\Services\CatalogoResgate;
use App\Modules\Resgate\Support\EnteDoUsuario;
use App\Modules\Resgate\Support\Rastro;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Catalogo de premios - Fase 2: vitrine, propostas e decisoes (quatro olhos).
 * Nenhuma rota aqui cria pedido de resgate: isso e da Fase 3.
 */
final class CatalogoController extends Controller
{
    public function index(
        Request $request,
        CatalogoResgate $catalogo,
        EnteDoUsuario $entes,
        TemporadaDoRanking $temporada,
        RankingReadService $leitura,
    ): Response {
        $user = $request->user();
        abort_unless($user?->can('resgate.catalogo.ver'), 403);

        $podePropor = $user->can('resgate.catalogo.propor');
        $podeAprovar = $user->can('resgate.catalogo.aprovar');

        // Faixa que libera premios: a do municipio do usuario na temporada
        // fechada (D2). Serve so para o indicador "liberado para voce".
        $municipio = $entes->resolver($user, EscopoCarteira::Municipio, null);
        $faixaFechada = $municipio !== null
            ? $temporada->resultadoNaTemporadaAnterior(EscopoCarteira::Municipio->escopoDoPlacar(), $municipio, $leitura, app(LeaderboardQuery::class), new DateTimeImmutable())
            : null;

        $itens = array_map(static fn (array $item): array => $item + [
            'liberado' => $faixaFechada !== null
                && FaixaRanking::from($faixaFechada['faixa'])->pontosMinimos() >= FaixaRanking::from($item['faixa_minima'])->pontosMinimos(),
        ], $catalogo->vigentes());

        return Inertia::render('Resgate/Catalogo', [
            'itens' => $itens,
            // A fila vive em pagina propria; aqui so o atalho com a contagem.
            'pendentesTotal' => ($podePropor || $podeAprovar) ? count($catalogo->pendentes()) : 0,
            'faixaFechada' => $faixaFechada,
            'podePropor' => $podePropor,
            'podeAprovar' => $podeAprovar,
        ]);
    }

    /** Fila de propostas aguardando decisao - pagina propria do SPA. */
    public function propostas(Request $request, CatalogoResgate $catalogo): Response
    {
        $user = $request->user();
        abort_unless($user?->can('resgate.catalogo.propor') || $user?->can('resgate.catalogo.aprovar'), 403);

        $pendentes = $catalogo->pendentes();

        return Inertia::render('Resgate/Propostas', [
            'pendentes' => $pendentes,
            'nomes' => $this->nomesDeUsuarios(array_column($pendentes, 'proposto_por')),
            'podeAprovar' => $user->can('resgate.catalogo.aprovar'),
            'usuarioId' => (int) $user->id,
        ]);
    }

    /** Formulario de proposta, pagina propria. `?item=CODIGO` propoe mudanca num item vigente. */
    public function novaProposta(Request $request, CatalogoResgate $catalogo): Response
    {
        abort_unless($request->user()?->can('resgate.catalogo.propor'), 403);

        return Inertia::render('Resgate/PropostaNova', [
            'item' => $this->vigenteDaQuery($request, $catalogo),
            'tipos' => array_map(static fn (TipoItem $t): array => ['value' => $t->value, 'label' => $t->label()], TipoItem::cases()),
        ]);
    }

    /** Formulario de unidade de bem permanente, pagina propria (`?item=CODIGO`). */
    public function novaUnidade(Request $request, CatalogoResgate $catalogo): Response
    {
        abort_unless($request->user()?->can('resgate.catalogo.propor'), 403);
        $item = $this->vigenteDaQuery($request, $catalogo);
        abort_unless($item !== null && $item['tipo'] === TipoItem::BemPermanente->value, 404);

        return Inertia::render('Resgate/UnidadeNova', ['item' => $item]);
    }

    public function propor(ProporItemCatalogoRequest $request, CatalogoResgate $catalogo): RedirectResponse
    {
        $dados = $request->validated();

        $this->traduzirRegra(fn () => $catalogo->propor(
            AcaoProposta::from($dados['acao']),
            $dados['codigo'],
            $dados['dados'] ?? [],
            $dados['justificativa'],
            (int) $request->user()->id,
            Rastro::daRequisicao($request),
        ));

        return redirect()->route('resgate.catalogo.propostas')->with('success', 'Proposta registrada. Ela precisa ser aprovada por outra pessoa.');
    }

    public function decidir(Request $request, int $proposta, CatalogoResgate $catalogo): RedirectResponse
    {
        abort_unless($request->user()?->can('resgate.catalogo.aprovar'), 403);
        $dados = $request->validate([
            'aprovar' => ['required', 'boolean'],
            'justificativa' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $aprovar = filter_var($dados['aprovar'], FILTER_VALIDATE_BOOLEAN);

        $this->traduzirRegra(fn () => $catalogo->decidir(
            $proposta, $aprovar, $dados['justificativa'], (int) $request->user()->id, Rastro::daRequisicao($request),
        ));

        return back()->with('success', $aprovar ? 'Proposta aprovada e publicada no catálogo.' : 'Proposta recusada.');
    }

    public function cadastrarUnidade(Request $request, CatalogoResgate $catalogo): RedirectResponse
    {
        abort_unless($request->user()?->can('resgate.catalogo.propor'), 403);
        $dados = $request->validate([
            'codigo' => ['required', 'string', 'regex:/^[A-Za-z0-9-]{3,40}$/'],
            'patrimonio' => ['required', 'string', 'max:40'],
            'descricao' => ['required', 'string', 'max:200'],
            'placa' => ['nullable', 'string', 'regex:/^[A-Za-z]{3}-?[0-9][A-Za-z0-9][0-9]{2}$/'],
            'renavam' => ['nullable', 'digits_between:9,11'],
            'chassi' => ['nullable', 'string', 'size:17'],
            'numero_serie' => ['nullable', 'string', 'max:80'],
            'inventario_equipamento_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->traduzirRegra(fn () => $catalogo->cadastrarUnidade(
            $dados['codigo'], $dados, (int) $request->user()->id, Rastro::daRequisicao($request),
        ));

        return redirect()->route('resgate.catalogo')->with('success', 'Unidade cadastrada.');
    }

    /** @return array<string, mixed>|null Item vigente indicado em `?item=`, ou null. */
    private function vigenteDaQuery(Request $request, CatalogoResgate $catalogo): ?array
    {
        $codigo = strtoupper((string) $request->query('item', ''));
        if ($codigo === '') {
            return null;
        }
        foreach ($catalogo->vigentes() as $item) {
            if ($item['codigo'] === $codigo) {
                return $item;
            }
        }
        abort(404);
    }

    /** Regra de negocio recusada vira erro de validacao legivel na tela. */
    private function traduzirRegra(callable $operacao): mixed
    {
        try {
            return $operacao();
        } catch (RegraDoCatalogo $e) {
            throw ValidationException::withMessages([$e->campo => $e->getMessage()]);
        }
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, string>
     */
    private function nomesDeUsuarios(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        return $ids === [] ? [] : User::query()->whereIn('id', $ids)->pluck('name', 'id')->all();
    }
}
