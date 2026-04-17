<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Models\AuditLog;
use App\Models\UserNotificationPreference;
use App\Modules\Demandas\Models\Task;
use App\Modules\Pae\Models\PaeProtocolo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ActivityFeedService
{
    private const TABLE_MODULE = [
        'rat_ocorrencias'       => 'rat',
        'pae_protocolos'        => 'pae',
        'tasks'                 => 'demandas',
        'dec_entrada_processos' => 'decretacoes',
    ];

    private const MODULE_LABEL = [
        'rat'         => 'RAT',
        'pae'         => 'PAE',
        'demandas'    => 'Demanda',
        'decretacoes' => 'Decretacao',
    ];

    public function getFeed(int $userId, int $limit = 7): array
    {
        $enabledModules = UserNotificationPreference::where('user_id', $userId)
            ->where('canal_sistema', true)
            ->pluck('module')
            ->toArray();

        if (empty($enabledModules)) {
            $enabledModules = array_values(self::TABLE_MODULE);
        }

        $enabledTables = array_keys(array_filter(
            self::TABLE_MODULE,
            fn ($module) => in_array($module, $enabledModules, true)
        ));

        $ownActions      = $this->queryOwnActions($userId, $enabledTables);
        $assignedActions = $this->queryAssignedActions($userId, $enabledTables);

        return $ownActions
            ->merge($assignedActions)
            ->unique('id')
            ->sortByDesc('created_at')
            ->take($limit)
            ->map(fn (AuditLog $log) => $this->formatItem($log))
            ->values()
            ->toArray();
    }

    private function queryOwnActions(int $userId, array $tables): Collection
    {
        if (empty($tables)) {
            return collect();
        }

        return AuditLog::where('user_id', $userId)
            ->whereIn('table_name', $tables)
            ->whereNotIn('event', [AuditLog::EVENT_LOGIN, AuditLog::EVENT_LOGOUT])
            ->latest('created_at')
            ->limit(20)
            ->get();
    }

    private function queryAssignedActions(int $userId, array $tables): Collection
    {
        $result = collect();

        if (in_array('pae_protocolos', $tables, true)) {
            $paeIds = PaeProtocolo::where('analista_atual_id', $userId)->pluck('id');

            if ($paeIds->isNotEmpty()) {
                $result = $result->merge(
                    AuditLog::where('table_name', 'pae_protocolos')
                        ->whereIn('row_id', $paeIds)
                        ->where('user_id', '!=', $userId)
                        ->whereNotIn('event', [AuditLog::EVENT_LOGIN, AuditLog::EVENT_LOGOUT])
                        ->latest('created_at')
                        ->limit(10)
                        ->get()
                );
            }
        }

        if (in_array('tasks', $tables, true)) {
            $taskIds = Task::where('atribuido_para_id', $userId)->pluck('id');

            if ($taskIds->isNotEmpty()) {
                $result = $result->merge(
                    AuditLog::where('table_name', 'tasks')
                        ->whereIn('row_id', $taskIds)
                        ->where('user_id', '!=', $userId)
                        ->whereNotIn('event', [AuditLog::EVENT_LOGIN, AuditLog::EVENT_LOGOUT])
                        ->latest('created_at')
                        ->limit(10)
                        ->get()
                );
            }
        }

        return $result;
    }

    private function formatItem(AuditLog $log): array
    {
        $module = self::TABLE_MODULE[$log->table_name] ?? 'sistema';
        $label  = self::MODULE_LABEL[$module] ?? 'Sistema';

        return [
            'id'        => $log->id,
            'type'      => $this->resolveType($log),
            'municipio' => $label,
            'acao'      => $this->resolveAcao($log, $label),
            'data'      => $this->tempoRelativo($log->created_at),
            'protocolo' => $this->resolveReferencia($log),
        ];
    }

    private function resolveType(AuditLog $log): string
    {
        if ($log->event === AuditLog::EVENT_INSERT) {
            return 'new_process';
        }

        if ($log->table_name === 'rat_ocorrencias') {
            return 'alert';
        }

        $novos = $log->new_values ?? [];

        if (
            isset($novos['reconhecimento']) &&
            str_starts_with((string) $novos['reconhecimento'], 'Reconhecido')
        ) {
            return 'approval';
        }

        if (isset($novos['status']) && in_array($novos['status'], ['aprovado', 'concluida'], true)) {
            return 'approval';
        }

        return 'analysis';
    }

    private function resolveAcao(AuditLog $log, string $label): string
    {
        $verbs = [
            AuditLog::EVENT_INSERT => 'criado',
            AuditLog::EVENT_UPDATE => 'atualizado',
            AuditLog::EVENT_DELETE => 'removido',
        ];

        $verb   = $verbs[$log->event] ?? 'modificado';
        $novos  = $log->new_values ?? [];
        $titulo = $novos['titulo'] ?? $novos['num_protocolo'] ?? $novos['n_protocolo_fide'] ?? null;

        if ($titulo) {
            return "{$label} {$verb}: {$titulo}";
        }

        return "{$label} #{$log->row_id} {$verb}";
    }

    private function resolveReferencia(AuditLog $log): string
    {
        $novos = $log->new_values ?? [];

        return $novos['num_protocolo']
            ?? $novos['n_protocolo_fide']
            ?? $novos['protocolo']
            ?? $novos['titulo']
            ?? ($log->table_name . ' #' . $log->row_id);
    }

    private function tempoRelativo(Carbon $dt): string
    {
        $diff = (int) now()->diffInMinutes($dt);

        if ($diff < 60) {
            return "{$diff} min";
        }

        $horas   = (int) floor($diff / 60);
        $minutos = $diff % 60;

        if ($horas < 24) {
            return $minutos > 0 ? "{$horas}h {$minutos}m" : "{$horas}h";
        }

        $dias = (int) floor($horas / 24);
        return "{$dias}d";
    }
}
