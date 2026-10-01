<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ranking\DTOs\FiltroPlacar;
use App\Modules\Ranking\Enums\TipoPeriodo;
use App\Modules\Ranking\Services\LeaderboardQuery;
use App\Modules\Ranking\Services\RankingReadService;
use App\Modules\Ranking\Services\TemporadaDoRanking;
use App\Modules\Ranking\Support\NomesDeParticipantes;
use App\Modules\Resgate\Contracts\SaldoResgatavel;
use App\Modules\Resgate\Enums\EscopoCarteira;
use App\Modules\Resgate\Support\EnteDoUsuario;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Carteira de resgate do ente - Fase 1, SOMENTE LEITURA.
 *
 * Quem ve o que:
 *  - resgate.carteira.view   : a carteira do PROPRIO ente, resolvida pelo
 *                              orgao principal do usuario. Id na requisicao e
 *                              ignorado - nao da para espiar outro municipio.
 *  - resgate.carteira.estado : qualquer ente, escolhido na tela.
 */
final class CarteiraController extends Controller
{
    public function show(
        Request $request,
        SaldoResgatavel $saldo,
        RankingReadService $leitura,
        TemporadaDoRanking $temporada,
        NomesDeParticipantes $nomes,
        EnteDoUsuario $entes,
    ): Response {
        $user = $request->user();
        abort_unless($user?->can('resgate.carteira.view'), 403);

        $dados = $request->validate([
            'escopo' => ['sometimes', Rule::in(array_column(EscopoCarteira::cases(), 'value'))],
            'ente' => ['sometimes', 'integer', 'min:1'],
        ]);
        $escopo = EscopoCarteira::from($dados['escopo'] ?? EscopoCarteira::Municipio->value);
        $estadual = $user->can('resgate.carteira.estado');
        $enteId = $entes->resolver($user, $escopo, isset($dados['ente']) ? (int) $dados['ente'] : null);

        $payload = ['carteira' => null, 'faixaFechada' => null, 'faixaAtual' => null, 'ente' => null];
        if ($enteId !== null) {
            $agora = new DateTimeImmutable();
            $placar = app(LeaderboardQuery::class);
            $escopoPlacar = $escopo->escopoDoPlacar();

            $payload['carteira'] = $saldo->carteira($escopo, $enteId, $agora)->paraArray();
            // A faixa que LIBERA premios e a da temporada fechada (D2): nao
            // oscila mais. A atual aparece so como referencia.
            $payload['faixaFechada'] = $temporada->resultadoNaTemporadaAnterior($escopoPlacar, $enteId, $leitura, $placar, $agora);
            $atual = $leitura->resumo(
                $enteId,
                new FiltroPlacar($escopoPlacar, TipoPeriodo::Trimestre->chave($agora), 'all', 1, FiltroPlacar::POR_PAGINA_PADRAO, $leitura->geracao()),
                $placar,
            );
            $payload['faixaAtual'] = $atual + ['chave' => TipoPeriodo::Trimestre->chave($agora)];
            $payload['ente'] = [
                'escopo' => $escopo->value,
                'id' => $enteId,
                'nome' => $nomes->para($escopoPlacar, [$enteId])[$enteId] ?? null,
            ];
        }

        return Inertia::render('Resgate/Carteira', $payload + [
            'filtros' => ['escopo' => $escopo->value, 'ente' => $enteId],
            'escopos' => array_map(static fn (EscopoCarteira $e): array => ['value' => $e->value, 'label' => $e->label()], EscopoCarteira::cases()),
            'podeEscolherEnte' => $estadual,
            'entes' => $estadual ? $this->entesParticipantes($escopo, $nomes) : [],
            'faixas' => config('ranking.faixas'),
        ]);
    }

    /**
     * Entes participantes da temporada corrente, nomeados e em ordem alfabetica,
     * para o seletor da visao estadual. Uma consulta de ids e uma de nomes.
     *
     * @return list<array{value: int, label: string}>
     */
    private function entesParticipantes(EscopoCarteira $escopo, NomesDeParticipantes $nomes): array
    {
        $ids = DB::connection((string) config('resgate.conexao'))->table('ranking.participantes as pt')
            ->join('ranking.periodos as p', 'p.id', '=', 'pt.periodo_id')
            ->where('p.chave', TipoPeriodo::Trimestre->chave(new DateTimeImmutable()))
            ->where('pt.escopo', $escopo->escopoDoPlacar()->value)
            ->distinct()->pluck('pt.entidade_id')->map(static fn ($id): int => (int) $id)->all();

        $rotulos = $nomes->para($escopo->escopoDoPlacar(), $ids);
        $opcoes = array_map(static fn (int $id): array => ['value' => $id, 'label' => $rotulos[$id] ?? "#{$id}"], $ids);
        usort($opcoes, static fn (array $a, array $b): int => strcmp($a['label'], $b['label']));

        return $opcoes;
    }
}
