<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ranking\DTOs\FiltroPlacar;
use App\Modules\Ranking\Enums\EscopoPlacar;
use App\Modules\Ranking\Enums\TipoPeriodo;
use App\Modules\Ranking\Services\IndicadoresDoPlacar;
use App\Modules\Ranking\Services\LeaderboardQuery;
use App\Modules\Ranking\Exceptions\TetoDeLancamentoExcedido;
use App\Modules\Ranking\Services\PublicarVersaoRegra;
use App\Modules\Ranking\Services\RankingReadService;
use App\Modules\Ranking\Services\TemporadaDoRanking;
use DateTimeImmutable;
use DomainException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use App\Modules\Ranking\Support\CatalogoRegras;
use App\Modules\Ranking\Support\NomesDeParticipantes;
use App\Modules\Ranking\Support\RankingAccess;

final class RankingController extends Controller
{
    public function index(Request $request, RankingReadService $leitura, NomesDeParticipantes $nomes, IndicadoresDoPlacar $indicadores, TemporadaDoRanking $temporada): Response
    {
        if (RankingAccess::preview($request->user())) {
            return Inertia::render('Ranking/Index', [
                'preview' => true,
                'filtros' => ['escopo' => 'usuario', 'periodo' => TipoPeriodo::Trimestre->chave(new DateTimeImmutable()), 'modulo' => 'all', 'pagina' => 1],
                'regras' => CatalogoRegras::todos(),
                'podeGerenciarRegras' => false,
                'cobertura' => 'Catálogo proposto para homologação. A simulação não grava pontos nem altera regras.',
            ]);
        }
        abort_unless(config('ranking.habilitado') && ! config('ranking.modo_sombra'), 404);
        $user = $request->user();
        abort_unless($user?->can('ranking.placar.view'), 403);

        $dados = $request->validate([
            'escopo' => ['sometimes', Rule::in(['usuario', 'orgao', 'municipio'])],
            'periodo' => ['sometimes', 'string', 'regex:/^(acumulado|mes:\\d{4}-(0[1-9]|1[0-2])|trimestre:\\d{4}-T[1-4]|ano:\\d{4})$/'],
            'modulo' => ['sometimes', 'string', 'regex:/^[a-z][a-z0-9_]{0,49}$/'],
            'pagina' => ['sometimes', 'integer', 'min:1', 'max:100000'],
            'extrato_pagina' => ['sometimes', 'integer', 'min:1', 'max:100000'],
        ]);
        $escopo = EscopoPlacar::from($dados['escopo'] ?? 'usuario');
        $estadual = $user->can('ranking.placar.estado');
        if ($escopo !== EscopoPlacar::Usuario) {
            abort_unless($user->can('ranking.placar.orgao') || $estadual, 403);
        }

        // Contexto atual somente autoriza a leitura; nao reatribui pontos historicos.
        $orgao = $escopo !== EscopoPlacar::Usuario ? $user->orgaoPrincipal : null;
        $entidadeId = match ($escopo) {
            EscopoPlacar::Usuario => (int) $user->id,
            EscopoPlacar::Orgao => $orgao?->id,
            EscopoPlacar::Municipio => $orgao?->municipio_id,
        };
        $filtros = [
            'escopo' => $escopo->value,
            // A temporada (trimestre) e o recorte padrao da competicao.
            'periodo' => $dados['periodo'] ?? TipoPeriodo::Trimestre->chave(new DateTimeImmutable()),
            'modulo' => $dados['modulo'] ?? 'all',
            'pagina' => (int) ($dados['pagina'] ?? 1),
        ];

        $payload = ['resumo' => null, 'placar' => null, 'podio' => [], 'extrato' => null, 'regras' => [], 'temporada' => null, 'indisponivel' => false];
        try {
            $filtro = new FiltroPlacar($escopo, $filtros['periodo'], $filtros['modulo'], $filtros['pagina'], 25, $leitura->geracao());
            $placar = app(LeaderboardQuery::class);
            if ($entidadeId !== null) {
                $payload['resumo'] = $leitura->resumo((int) $entidadeId, $filtro, $placar);
            }
            if ($estadual) {
                $payload['placar'] = $placar->pagina($filtro);
                $payload['placar']['linhas'] = $indicadores->anexar($payload['placar']['linhas'], $filtro);
                $primeiraPagina = $filtro->pagina === 1 ? $payload['placar'] : $placar->pagina($filtro->naPagina(1));
                $payload['podio'] = array_values(array_filter(
                    $primeiraPagina['linhas'],
                    static fn (array $linha): bool => $linha['posicao'] <= 3 && $linha['pontos'] > 0,
                ));
            }
            if ($user->can('ranking.extrato.view')) {
                $payload['extrato'] = $leitura->extrato((int) $user->id, $filtros['periodo'], (int) ($dados['extrato_pagina'] ?? 1));
            }
            if ($user->can('ranking.regras.view')) {
                $payload['regras'] = $leitura->regras();
            }
            $payload['temporada'] = $temporada->descrever((int) $user->id, $leitura, $placar, new DateTimeImmutable());
            $this->rotular($payload, $escopo, $entidadeId, $nomes);
        } catch (\PDOException $e) {
            report($e);
            $payload = ['resumo' => null, 'placar' => null, 'podio' => [], 'extrato' => null, 'regras' => [], 'temporada' => null, 'indisponivel' => true];
        }

        return Inertia::render('Ranking/Index', $payload + [
            'filtros' => $filtros,
            'semContexto' => $entidadeId === null,
            'podeVerInstitucional' => $user->can('ranking.placar.orgao') || $estadual,
            'estadual' => $estadual,
            'cobertura' => 'Piloto RAT/PAE. Cobertura parcial; IPCM em apuracao.',
            'podeGerenciarRegras' => $user->can('is-admin'),
            'podeVerCarteira' => $user->can('resgate.carteira.view'),
            // Limiares vindos do config, conferido contra FaixaRanking pelo
            // verify-catalog: o Vue nao mantem uma segunda copia dos cortes.
            'faixas' => config('ranking.faixas'),
            // Mesmo teto do ScoreCalculator: o editor recusa base + bonus acima
            // dele antes de publicar, em vez de a regra ir toda para apuracao.
            'tetoLancamento' => (int) config('ranking.pontuacao.teto_por_lancamento'),
        ]);
    }

    /**
     * Altera sempre a versao VIGENTE publicando versao+1; a versao na URL foi
     * retirada para que versao ja fechada nunca seja alvo de edicao.
     */
    public function atualizarRegra(Request $request, string $ruleKey, PublicarVersaoRegra $publicar): RedirectResponse
    {
        abort_unless(config('ranking.habilitado') && ! config('ranking.modo_sombra'), 404);
        abort_unless($request->user()?->can('is-admin'), 403);

        $dados = $request->validate([
            'habilitada' => ['sometimes', 'boolean'],
            'aceita_bonus' => ['sometimes', 'boolean'],
            'bonus_percentual' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'pontos_base' => ['sometimes', 'integer', 'min:0', 'max:' . (int) config('ranking.pontuacao.teto_por_lancamento')],
        ]);
        // Checagem manual porque `sometimes` pula o campo ausente e nenhuma
        // regra declarativa dispararia com o corpo inteiro vazio.
        if ($dados === []) {
            throw ValidationException::withMessages(['habilitada' => 'Informe ao menos uma alteracao da regra.']);
        }

        $alteracoes = [];
        foreach (['habilitada', 'aceita_bonus'] as $campo) {
            if (array_key_exists($campo, $dados)) {
                $alteracoes[$campo] = filter_var($dados[$campo], FILTER_VALIDATE_BOOLEAN);
            }
        }
        foreach (['bonus_percentual', 'pontos_base'] as $campo) {
            if (array_key_exists($campo, $dados)) {
                $alteracoes[$campo] = (int) $dados[$campo];
            }
        }

        try {
            $publicar->publicar($ruleKey, $alteracoes, (int) $request->user()->id);
        } catch (RecordsNotFoundException) {
            abort(404);
        } catch (TetoDeLancamentoExcedido $e) {
            throw ValidationException::withMessages(['pontos_base' => $e->getMessage()]);
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['habilitada' => $e->getMessage()]);
        }

        return back();
    }

    /**
     * Preenche `rotulo` depois da consulta, so para os ids que vao para a tela
     * (pagina atual - que contem o podio - e a propria entidade do resumo), numa
     * unica ida a origem. Id sem nome fica sem rotulo e a tela cai no codigo.
     */
    private function rotular(array &$payload, EscopoPlacar $escopo, ?int $entidadeId, NomesDeParticipantes $nomes): void
    {
        $linhas = $payload['placar']['linhas'] ?? [];
        $ids = array_merge(array_column($linhas, 'entidade_id'), array_column($payload['podio'], 'entidade_id'));
        if ($payload['resumo'] !== null && $entidadeId !== null) {
            $ids[] = $entidadeId;
        }

        $mapa = $nomes->para($escopo, $ids);
        if ($mapa === []) {
            return;
        }

        foreach ($linhas as $i => $linha) {
            $payload['placar']['linhas'][$i]['rotulo'] = $mapa[$linha['entidade_id']] ?? null;
        }
        foreach ($payload['podio'] as $i => $linha) {
            $payload['podio'][$i]['rotulo'] = $mapa[$linha['entidade_id']] ?? null;
        }
        if ($payload['resumo'] !== null && $entidadeId !== null) {
            $payload['resumo']['rotulo'] = $mapa[$entidadeId] ?? null;
        }
    }
}
