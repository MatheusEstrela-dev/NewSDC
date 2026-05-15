<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Core\Outbox\OutboxDispatcher;
use App\Modules\Tdap\Domain\Events\ProcessoTdapAbertoV1;
use App\Modules\Tdap\Domain\Events\ProcessoTdapTransitadoV1;
use App\Modules\Tdap\DTOs\ProcessoTdapDTO;
use App\Modules\Tdap\DTOs\TransicaoProcessoDTO;
use App\Modules\Tdap\Enums\EstadoProcesso;
use App\Modules\Tdap\Models\ProcessoTdap;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Aplica regras de dominio do agregado ProcessoTdap.
 *
 * Padrao: abre/transita o agregado dentro de DB::transaction e persiste
 * Domain Events no outbox na MESMA transacao (garantia exactly-once).
 */
class ProcessoTdapService
{
    public function __construct(
        private readonly OutboxDispatcher $outbox,
    ) {}

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(int $perPage = 20, array $filtros = []): LengthAwarePaginator
    {
        return ProcessoTdap::query()
            ->with(['municipio:id,nome,uf'])
            ->when($filtros['estado'] ?? null, fn ($q, $e) => $q->where('estado', (string) $e))
            ->when($filtros['swimlane'] ?? null, fn ($q, $s) => $q->where('swimlane_atual', (string) $s))
            ->when($filtros['municipio_id'] ?? null, fn ($q, $id) => $q->where('municipio_id', (int) $id))
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $q->whereRaw('UPPER(numero) LIKE ?', ['%'.mb_strtoupper((string) $termo).'%']))
            ->orderByDesc('aberto_em')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function obter(string $id): ProcessoTdap
    {
        return ProcessoTdap::query()
            ->with(['municipio:id,nome,uf', 'abertoPor:id,name'])
            ->findOrFail($id);
    }

    /**
     * Abre um novo ProcessoTdap em estado RASCUNHO.
     * Emite ProcessoTdapAbertoV1 no outbox.
     */
    public function abrir(ProcessoTdapDTO $dto): ProcessoTdap
    {
        return DB::transaction(function () use ($dto): ProcessoTdap {
            $userId = Auth::id();

            $processo = ProcessoTdap::create([
                'numero'         => $dto->numero,
                'estado'         => EstadoProcesso::Rascunho->value,
                'swimlane_atual' => EstadoProcesso::Rascunho->swimlane()->value,
                'municipio_id'   => $dto->municipio_id,
                'contexto'       => $dto->contexto,
                'aberto_em'      => now(),
                'aberto_por'     => $userId,
            ]);

            $this->outbox->persist(new ProcessoTdapAbertoV1(
                eventId:       \App\Core\Events\DomainEvent::newId(),
                aggregateType: 'processo_tdap',
                aggregateId:   $processo->id,
                occurredAt:    new \DateTimeImmutable(),
                metadata: [
                    'numero'       => $processo->numero,
                    'municipio_id' => $processo->municipio_id,
                    'aberto_por'   => $userId,
                    'contexto'     => $processo->contexto,
                ],
            ));

            return $processo->fresh();
        });
    }

    /**
     * Transita o processo para o estado alvo.
     * Emite ProcessoTdapTransitadoV1 no outbox.
     *
     * @return array{ProcessoTdap, ?string} [processo atualizado, mensagem de erro se houver]
     */
    public function transitar(string $id, TransicaoProcessoDTO $dto): array
    {
        return DB::transaction(function () use ($id, $dto): array {
            $processo = ProcessoTdap::query()->lockForUpdate()->findOrFail($id);

            if ($processo->isTerminal()) {
                return [$processo, 'Processo encerrado nao pode mais ser transitado.'];
            }

            $estadoAtual = $processo->estado;
            if (! $estadoAtual->podeTransitarPara($dto->estadoAlvo)) {
                $permitidos = array_map(fn ($e) => $e->label(), $estadoAtual->transicoesPermitidas());
                $msg = "Transicao {$estadoAtual->label()} -> {$dto->estadoAlvo->label()} nao permitida. "
                    . 'Permitidos: '.(empty($permitidos) ? 'nenhum (terminal)' : implode(', ', $permitidos));

                return [$processo, $msg];
            }

            $userId = Auth::id();
            $processo->forceFill([
                'estado'         => $dto->estadoAlvo->value,
                'swimlane_atual' => $dto->estadoAlvo->swimlane()->value,
                'encerrado_em'   => $dto->estadoAlvo === EstadoProcesso::Encerrado ? now() : $processo->encerrado_em,
            ])->save();

            $this->outbox->persist(new ProcessoTdapTransitadoV1(
                eventId:       \App\Core\Events\DomainEvent::newId(),
                aggregateType: 'processo_tdap',
                aggregateId:   $processo->id,
                occurredAt:    new \DateTimeImmutable(),
                metadata: [
                    'estado_anterior' => $estadoAtual->value,
                    'estado_novo'     => $dto->estadoAlvo->value,
                    'swimlane_atual'  => $dto->estadoAlvo->swimlane()->value,
                    'motivo'          => $dto->motivo,
                    'user_id'         => $userId,
                ],
            ));

            return [$processo->fresh(), null];
        });
    }

    /**
     * @return array<string, int>
     */
    public function obterEstatisticas(): array
    {
        $row = ProcessoTdap::query()
            ->selectRaw('
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE encerrado_em IS NULL) AS abertos,
                COUNT(*) FILTER (WHERE estado = ?) AS em_execucao,
                COUNT(*) FILTER (WHERE estado = ?) AS encerrados
            ', [
                EstadoProcesso::EmExecucao->value,
                EstadoProcesso::Encerrado->value,
            ])
            ->first();

        return [
            'total'       => (int) ($row->total ?? 0),
            'abertos'     => (int) ($row->abertos ?? 0),
            'em_execucao' => (int) ($row->em_execucao ?? 0),
            'encerrados'  => (int) ($row->encerrados ?? 0),
        ];
    }
}
