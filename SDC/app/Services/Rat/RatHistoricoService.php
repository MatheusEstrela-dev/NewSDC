<?php

declare(strict_types=1);

namespace App\Services\Rat;

use App\Models\Rat\RatOcorrencia;
use App\Models\Rat\RatOcorrenciaHistorico;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Registra e consulta o histórico de eventos de ocorrências RAT.
 *
 * Diferente do RatAuditService (que grava em audit_logs genérico),
 * este serviço usa a tabela dedicada rat_ocorrencia_historico,
 * permitindo cronologia estruturada por ocorrência.
 *
 * Métodos públicos:
 *   registrar()   — grava um evento no histórico
 *   cronologia()  — retorna eventos paginados de uma ocorrência
 *   recentes()    — retorna os últimos N eventos (sem paginação)
 */
class RatHistoricoService
{
    /**
     * Grava um evento no histórico da ocorrência.
     *
     * @param RatOcorrencia $ocorrencia  Instância da ocorrência
     * @param string        $evento      Nome do evento (ex: 'envolvido.adicionado')
     * @param array         $carga       Dados extras do evento (IDs, nomes, diferenças)
     */
    public function log(RatOcorrencia $ocorrencia, string $evento, array $carga = []): void
    {
        /** @var \Illuminate\Contracts\Auth\Guard $auth */
        $auth = auth();
        RatOcorrenciaHistorico::create([
            'ocorrencia_id' => $ocorrencia->id,
            'user_id'       => $auth->id(),
            'evento'        => $evento,
            'payload'       => empty($carga) ? null : $carga,
            'ip_address'    => request()->ip(),
            'user_agent'    => request()->userAgent(),
        ]);
    }

    /**
     * Retorna a cronologia paginada de uma ocorrência em ordem cronológica decrescente.
     */
    public function timeline(int $ocorrenciaId, int $porPagina = 20): LengthAwarePaginator
    {
        return RatOcorrenciaHistorico::where('ocorrencia_id', $ocorrenciaId)
            ->with('user:id,name')
            ->latest()
            ->paginate($porPagina);
    }

    /**
     * Retorna os últimos N eventos de uma ocorrência (sem paginação — útil para widgets).
     */
    public function recent(int $ocorrenciaId, int $limite = 10): \Illuminate\Support\Collection
    {
        return RatOcorrenciaHistorico::where('ocorrencia_id', $ocorrenciaId)
            ->with('user:id,name')
            ->latest()
            ->limit($limite)
            ->get();
    }
}
