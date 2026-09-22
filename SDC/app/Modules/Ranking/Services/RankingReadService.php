<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\DTOs\FiltroPlacar;
use App\Modules\Ranking\Enums\FaixaRanking;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

final class RankingReadService
{
    private function conexao(): ConnectionInterface
    {
        return DB::connection('ranking_read');
    }

    public function geracao(): int
    {
        return max(1, (int) $this->conexao()->table('ranking.saldos')->max('geracao'));
    }

    public function resumo(int $entidadeId, FiltroPlacar $filtro, LeaderboardQuery $placar): array
    {
        $saldo = $this->conexao()->table('ranking.saldos as s')
            ->join('ranking.periodos as p', 'p.id', '=', 's.periodo_id')
            ->where('p.chave', $filtro->periodoChave)->where('s.geracao', $filtro->geracao)
            ->where('s.escopo', $filtro->escopo->value)->where('s.entidade_id', $entidadeId)
            ->where('s.modulo', $filtro->modulo)->first(['s.pontos', 's.atualizado_em']);

        $pontos = (int) ($saldo?->pontos ?? 0);

        return [
            'pontos' => $pontos,
            'posicao' => $placar->posicaoDe($entidadeId, $filtro),
            'faixa' => FaixaRanking::deSaldo($pontos)->value,
            'atualizado_em' => $saldo?->atualizado_em,
        ];
    }

    /** Extrato pessoal: a identidade vem exclusivamente da sessao autenticada. */
    public function extrato(int $usuarioId, string $periodoChave, int $pagina = 1): array
    {
        $periodo = $this->conexao()->table('ranking.periodos')->where('chave', $periodoChave)->first();
        if ($periodo === null) {
            return ['data' => [], 'current_page' => 1, 'last_page' => 1, 'total' => 0];
        }

        $query = $this->conexao()->table('ranking.transacoes as t')
            ->leftJoin('ranking.lancamentos as l', 'l.transacao_id', '=', 't.id')
            ->where('t.credited_user_id', $usuarioId);
        if ($periodo->inicia_em !== null) {
            $query->where('t.competencia_em', '>=', $periodo->inicia_em);
        }
        if ($periodo->termina_em !== null) {
            $query->where('t.competencia_em', '<', $periodo->termina_em);
        }

        return $query->orderByDesc('t.competencia_em')->orderByDesc('t.id')->orderByDesc('l.id')
            ->paginate(25, [
                't.id', 't.familia', 't.decisao', 't.motivo', 't.competencia_em',
                'l.modulo', 'l.pontos_base', 'l.pontos_bonus', 'l.pontos', 'l.regra_versao',
            ], 'extrato_pagina', $pagina)->toArray();
    }

    public function regras(): array
    {
        return $this->conexao()->table('ranking.regras')->orderBy('modulo')->orderBy('rule_key')
            ->orderByDesc('versao')->limit(500)->get([
                'rule_key', 'versao', 'modulo', 'familia', 'pontos_base', 'bonus_percentual',
                'aceita_bonus', 'habilitada', 'motivo_desabilitada', 'vigente_de', 'vigente_ate',
            ])->map(static fn (object $regra): array => (array) $regra)->all();
    }
}
