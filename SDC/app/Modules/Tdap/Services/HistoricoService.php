<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Modules\Tdap\Models\Historico;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

/**
 * Service de auditoria de negocio do TDAP.
 *
 * Eventos de dominio (criar/ativar/encerrar/validar/aprovar) sao registrados
 * aqui via Observers (Fase 5). Complementa o LogsModelChanges existente que
 * audita diff campo-a-campo.
 *
 * Tipos de evento canonicos:
 *   - cronograma.criado, cronograma.ativado, cronograma.encerrado, cronograma.prorrogado
 *   - viagem.registrada, viagem.aprovada, viagem.rejeitada, viagem.removida
 *   - vistoria.criada, vistoria.aprovada, vistoria.reprovada
 */
class HistoricoService
{
    /** @var array<string, string> Map de FQN do Model para chave compacta */
    private const ENTITY_TYPE_MAP = [
        \App\Modules\Tdap\Models\Cronograma::class    => 'cronograma',
        \App\Modules\Tdap\Models\CronoViagem::class   => 'viagem',
        \App\Modules\Tdap\Models\Vistoria::class      => 'vistoria',
        \App\Modules\Tdap\Models\CronoCaminhao::class => 'crono_caminhao',
        \App\Modules\Tdap\Models\Prestador::class     => 'prestador',
        \App\Modules\Tdap\Models\Caminhao::class      => 'caminhao',
        \App\Modules\Tdap\Models\Ata::class           => 'ata',
        \App\Modules\Tdap\Models\Lote::class          => 'lote',
    ];

    /**
     * Registra um evento de negocio.
     *
     * @param  array<string, mixed>|null  $payload
     */
    public function registrar(
        string $tipoEvento,
        Model $entity,
        ?string $obs = null,
        ?array $payload = null,
        ?int $userId = null,
    ): Historico {
        return Historico::create([
            'data_evento' => now(),
            'tipo_evento' => $tipoEvento,
            'entity_type' => $this->resolveEntityType($entity),
            'entity_id'   => (int) $entity->getKey(),
            'user_id'     => $userId ?? Auth::id(),
            'obs'         => $obs,
            'payload'     => $payload,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(int $perPage = 25, array $filtros = []): LengthAwarePaginator
    {
        return Historico::query()
            ->with('user:id,name')
            ->when($filtros['entity_type'] ?? null, fn ($q, $t) => $q->where('entity_type', (string) $t))
            ->when($filtros['entity_id'] ?? null, fn ($q, $id) => $q->where('entity_id', (int) $id))
            ->when($filtros['tipo_evento'] ?? null, fn ($q, $t) => $q->where('tipo_evento', (string) $t))
            ->when($filtros['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', (int) $id))
            ->when($filtros['de'] ?? null, fn ($q, $de) => $q->whereDate('data_evento', '>=', (string) $de))
            ->when($filtros['ate'] ?? null, fn ($q, $ate) => $q->whereDate('data_evento', '<=', (string) $ate))
            ->orderByDesc('data_evento')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function obter(int $id): Historico
    {
        return Historico::query()->with('user:id,name')->findOrFail($id);
    }

    /**
     * Linhas planas para exportacao CSV (respeita os filtros da listagem).
     *
     * @param  array<string, mixed>  $filtros
     * @return array<int, array<string, mixed>>
     */
    public function exportar(array $filtros = []): array
    {
        $rows = Historico::query()
            ->with('user:id,name')
            ->when($filtros['entity_type'] ?? null, fn ($q, $t) => $q->where('entity_type', (string) $t))
            ->when($filtros['entity_id'] ?? null, fn ($q, $id) => $q->where('entity_id', (int) $id))
            ->when($filtros['tipo_evento'] ?? null, fn ($q, $t) => $q->where('tipo_evento', (string) $t))
            ->when($filtros['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', (int) $id))
            ->when($filtros['de'] ?? null, fn ($q, $de) => $q->whereDate('data_evento', '>=', (string) $de))
            ->when($filtros['ate'] ?? null, fn ($q, $ate) => $q->whereDate('data_evento', '<=', (string) $ate))
            ->orderByDesc('data_evento')
            ->orderByDesc('id')
            ->limit(5000)
            ->get();

        return $rows->map(fn (Historico $h) => [
            'Data Evento' => $h->data_evento?->format('d/m/Y H:i:s'),
            'Tipo Evento' => $h->tipo_evento,
            'Entidade'    => $h->entity_type,
            'Entidade Id' => (int) $h->entity_id,
            'Responsavel' => $h->user?->name ?? 'Sistema',
            'Observacao'  => $h->obs,
        ])->all();
    }

    /**
     * @return array<string, int>
     */
    public function obterEstatisticas(): array
    {
        $inicioMes = now()->startOfMonth();

        $row = Historico::query()
            ->selectRaw('
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE data_evento >= ?) AS mes_atual,
                COUNT(DISTINCT entity_id) FILTER (WHERE entity_type = ?) AS cronogramas_envolvidos
            ', [$inicioMes, 'cronograma'])
            ->first();

        return [
            'total'                  => (int) ($row->total ?? 0),
            'mes_atual'              => (int) ($row->mes_atual ?? 0),
            'cronogramas_envolvidos' => (int) ($row->cronogramas_envolvidos ?? 0),
        ];
    }

    private function resolveEntityType(Model $entity): string
    {
        $fqn = $entity::class;

        return self::ENTITY_TYPE_MAP[$fqn] ?? class_basename($fqn);
    }
}
