<?php

declare(strict_types=1);

namespace App\Services\Rat;

use App\Models\AuditLog;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Serviço de auditoria do módulo RAT.
 *
 * Métodos públicos:
 *   log()    — registra uma entrada de auditoria
 *   history() — retorna histórico paginado de ações em uma ocorrência
 */
class RatAuditService
{
    /**
     * Registra uma ação de auditoria para a entidade RAT.
     *
     * @param string $event       Nome do evento (ex: 'rat.criado', 'rat.finalizado')
     * @param string $tableName   Nome da tabela (ex: 'rat_ocorrencias')
     * @param int    $rowId       ID da linha na tabela
     * @param array  $payload     Dados adicionais do evento
     */
    public function log(string $event, string $tableName, int $rowId, array $payload = []): void
    {
        AuditLog::create([
            'user_id'    => auth()->id(),
            'event'      => $event,
            'table_name' => $tableName,
            'row_id'     => $rowId,
            'new_values' => $payload,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Retorna histórico de auditoria paginado para uma ocorrência específica.
     */
    public function history(string $tableName, int $rowId, int $perPage = 20): LengthAwarePaginator
    {
        return AuditLog::where('table_name', $tableName)
            ->where('row_id', $rowId)
            ->with('user:id,name')
            ->latest()
            ->paginate($perPage);
    }
}
