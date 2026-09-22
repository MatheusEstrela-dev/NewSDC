<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ranking\DTOs\FiltroPlacar;
use App\Modules\Ranking\Enums\EscopoPlacar;
use App\Modules\Ranking\Enums\TipoPeriodo;
use App\Modules\Ranking\Services\LeaderboardQuery;
use App\Modules\Ranking\Services\RankingReadService;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use App\Modules\Ranking\Support\CatalogoRegras;
use App\Modules\Ranking\Support\RankingAccess;

final class RankingController extends Controller
{
    public function index(Request $request, RankingReadService $leitura): Response
    {
        if (RankingAccess::preview($request->user())) {
            return Inertia::render('Ranking/Index', [
                'preview' => true,
                'filtros' => ['escopo' => 'usuario', 'periodo' => TipoPeriodo::Mes->chave(new DateTimeImmutable()), 'modulo' => 'all', 'pagina' => 1],
                'regras' => CatalogoRegras::todos(),
                'cobertura' => 'Catálogo proposto para homologação. A simulação não grava pontos nem altera regras.',
            ]);
        }
        abort_unless(config('ranking.habilitado') && ! config('ranking.modo_sombra'), 404);
        $user = $request->user();
        abort_unless($user?->can('ranking.placar.view'), 403);

        $dados = $request->validate([
            'escopo' => ['sometimes', Rule::in(['usuario', 'orgao', 'municipio'])],
            'periodo' => ['sometimes', 'string', 'regex:/^(acumulado|mes:\\d{4}-(0[1-9]|1[0-2])|ano:\\d{4})$/'],
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
            'periodo' => $dados['periodo'] ?? TipoPeriodo::Mes->chave(new DateTimeImmutable()),
            'modulo' => $dados['modulo'] ?? 'all',
            'pagina' => (int) ($dados['pagina'] ?? 1),
        ];

        $payload = ['resumo' => null, 'placar' => null, 'extrato' => null, 'regras' => [], 'indisponivel' => false];
        try {
            $filtro = new FiltroPlacar($escopo, $filtros['periodo'], $filtros['modulo'], $filtros['pagina'], 25, $leitura->geracao());
            $placar = app(LeaderboardQuery::class);
            if ($entidadeId !== null) {
                $payload['resumo'] = $leitura->resumo((int) $entidadeId, $filtro, $placar);
            }
            if ($estadual) {
                $payload['placar'] = $placar->pagina($filtro);
            }
            if ($user->can('ranking.extrato.view')) {
                $payload['extrato'] = $leitura->extrato((int) $user->id, $filtros['periodo'], (int) ($dados['extrato_pagina'] ?? 1));
            }
            if ($user->can('ranking.regras.view')) {
                $payload['regras'] = $leitura->regras();
            }
        } catch (\PDOException $e) {
            report($e);
            $payload = ['resumo' => null, 'placar' => null, 'extrato' => null, 'regras' => [], 'indisponivel' => true];
        }

        return Inertia::render('Ranking/Index', $payload + [
            'filtros' => $filtros,
            'semContexto' => $entidadeId === null,
            'podeVerInstitucional' => $user->can('ranking.placar.orgao') || $estadual,
            'estadual' => $estadual,
            'cobertura' => 'Piloto RAT/PAE. Cobertura parcial; IPCM em apuracao.',
        ]);
    }
}
