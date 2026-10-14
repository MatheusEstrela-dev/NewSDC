<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Models\User;
use App\Modules\Tdap\Models\CronoViagem;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\Lote;

/**
 * Relatorio do cronograma em tres perspectivas (#62).
 *
 * - cedec:     gestao estadual -- execucao, decisao do municipio e valor
 *              liberado (unica com dado financeiro).
 * - prestador: Prestador/COMPDEC -- o atesto: cada viagem com a validacao do
 *              Estado e a confirmacao do municipio, para conferencia e
 *              assinatura das duas partes.
 * - copasa:    captacao -- pontos, frota que capta e volume captado por dia.
 *
 * O m3 de uma viagem e a capacidade do caminhao, a mesma regra de
 * CronoCaminhaoService::recalcularEntregas e do CronoViagemResource.
 */
class CronogramaRelatorioService
{
    public const PERSPECTIVAS = ['cedec', 'prestador', 'copasa'];

    /** Perspectiva inicial pelo perfil: o COMPDEC abre no atesto. */
    public function perspectivaPadrao(?User $user): string
    {
        return $user?->hasRole('compdec') ? 'prestador' : 'cedec';
    }

    public function montar(Cronograma $cronograma, string $perspectiva): array
    {
        // Direto do lote: o CronogramaService::obter carrega o lote so com
        // id/numero/nome, e o valor viria vazio.
        $valorM3 = (float) (Lote::query()->whereKey($cronograma->lote_id)->value('valor_m3') ?? 0);

        $viagens = CronoViagem::query()
            ->whereHas('cronoCaminhao', fn ($q) => $q->where('cronograma_id', $cronograma->id))
            ->with(['cronoCaminhao.caminhao', 'confirmador:id,name', 'validador:id,name'])
            ->orderBy('data_registro')
            ->orderBy('id')
            ->get()
            ->map(function (CronoViagem $v): array {
                $caminhao = $v->cronoCaminhao?->caminhao;

                return [
                    'id'                 => $v->id,
                    'data'               => $v->data_registro?->toDateString(),
                    'placa'              => $caminhao?->placa,
                    'm3'                 => (float) ($caminhao?->capacidade_m3 ?? 0),
                    'status'             => $v->status,
                    'status_confirmacao' => $v->status_confirmacao,
                    'validador'          => $v->validador?->name,
                    'confirmador'        => $v->confirmador?->name,
                    'obs'                => $v->obs_confirmacao ?: $v->obs_aprovacao ?: $v->obs,
                ];
            });

        $aprovadas = $viagens->where('status', 'aprovada');
        $m3Aprovado = round((float) $aprovadas->sum('m3'), 2);

        $resumo = [
            'total'                => $viagens->count(),
            'aprovadas'            => $aprovadas->count(),
            'rejeitadas'           => $viagens->where('status', 'rejeitada')->count(),
            'pendentes_validacao'  => $viagens->where('status', 'pendente')->count(),
            'confirmadas'          => $viagens->where('status_confirmacao', 'confirmada')->count(),
            'reprovadas'           => $viagens->where('status_confirmacao', 'reprovada')->count(),
            'pendentes_municipio'  => $viagens->where('status_confirmacao', 'pendente')->count(),
            'm3_aprovado'          => $m3Aprovado,
            'm3_registrado'        => round((float) $viagens->sum('m3'), 2),
        ];

        if ($perspectiva === 'cedec') {
            $resumo['valor_m3'] = $valorM3;
            $resumo['valor_aprovado'] = round($m3Aprovado * $valorM3, 2);
        }

        $diario = $viagens->groupBy('data')->map(fn ($doDia, $data) => [
            'data'      => $data,
            'viagens'   => $doDia->count(),
            'aprovadas' => $doDia->where('status', 'aprovada')->count(),
            'm3'        => round((float) $doDia->sum('m3'), 2),
            'placas'    => $doDia->pluck('placa')->filter()->unique()->values()->all(),
        ])->values()->all();

        return [
            'perspectiva' => $perspectiva,
            'resumo'      => $resumo,
            'diario'      => $diario,
            // A Copasa olha captacao, nao o atesto de cada viagem.
            'viagens'     => $perspectiva === 'copasa' ? [] : $viagens->values()->all(),
        ];
    }
}
