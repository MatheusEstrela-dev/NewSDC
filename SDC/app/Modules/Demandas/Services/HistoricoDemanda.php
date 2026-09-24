<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Services;

use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaAuditLog;
use Carbon\CarbonInterface;

/**
 * Unico ponto de escrita da aba Historico. Casos de uso chamam isto uma vez por
 * acao do usuario; o workflow nao registra nada, para uma resolucao de tres
 * passos aparecer como um fato so.
 */
final class HistoricoDemanda
{
    public function registrar(
        Demanda $demanda,
        ?int $userId,
        AcaoHistoricoDemanda $acao,
        string $detalhes,
        array $metadata = [],
        ?string $campo = null,
        ?string $anterior = null,
        ?string $novo = null,
        ?CarbonInterface $em = null,
    ): DemandaAuditLog {
        $log = new DemandaAuditLog([
            'task_id' => $demanda->getKey(),
            'user_id' => $userId,
            'acao' => $acao->value,
            'campo' => $campo,
            'valor_anterior' => $anterior,
            'valor_novo' => $novo,
            'metadata' => array_merge($metadata, ['rotulo' => $acao->rotulo(), 'detalhes' => $detalhes]),
        ]);
        if ($em !== null) {
            $log->created_at = $em;
        }
        $log->save();

        return $log;
    }
}
