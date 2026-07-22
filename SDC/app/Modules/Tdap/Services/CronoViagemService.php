<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Modules\Tdap\DTOs\CronoViagemDTO;
use App\Modules\Tdap\Models\CronoCaminhao;
use App\Modules\Tdap\Models\CronoViagem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CronoViagemService
{
    public function __construct(
        private readonly CronoCaminhaoService $cronoCaminhaoService,
    ) {}

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listarDoCaminhao(int $cronoCaminhaoId, array $filtros = []): Collection
    {
        return CronoViagem::query()
            ->doCaminhao($cronoCaminhaoId)
            ->with(['validador:id,name'])
            ->when($filtros['status'] ?? null, function ($q, $status) {
                match ($status) {
                    'pendente'  => $q->pendente(),
                    'aprovada'  => $q->aprovada(),
                    'rejeitada' => $q->rejeitada(),
                    default     => null,
                };
            })
            ->orderByDesc('data_registro')
            ->get();
    }

    public function listarPendentesValidacao(int $perPage = 25): LengthAwarePaginator
    {
        return CronoViagem::query()
            ->with([
                'cronoCaminhao.cronograma:id,numero',
                'cronoCaminhao.caminhao:id,placa,marca,modelo',
            ])
            ->pendente()
            ->whereHas('cronoCaminhao')
            ->orderBy('data_registro')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function registrar(CronoViagemDTO $dto): CronoViagem
    {
        return DB::transaction(function () use ($dto): CronoViagem {
            $cc = CronoCaminhao::query()
                ->with('cronograma:id,ativo,encerrado_em')
                ->findOrFail($dto->crono_caminhao_id);

            if (! $cc->cronograma?->ativo) {
                throw new \DomainException('So e possivel registrar viagens em cronogramas ativos.');
            }
            if ($cc->cronograma->encerrado_em) {
                throw new \DomainException('Cronograma encerrado nao aceita novas viagens.');
            }

            return CronoViagem::create([
                'crono_caminhao_id' => $dto->crono_caminhao_id,
                'data_registro'     => $dto->data_registro,
                'obs'               => $dto->obs,
                'validado'          => CronoViagem::STATUS_PENDENTE,
            ]);
        });
    }

    public function validar(int $id, bool $aprovada, ?string $obsAprovacao = null): CronoViagem
    {
        return DB::transaction(function () use ($id, $aprovada, $obsAprovacao): CronoViagem {
            $viagem = CronoViagem::query()->lockForUpdate()->findOrFail($id);

            if ($viagem->validado !== null) {
                throw new \DomainException('Viagem ja foi validada anteriormente.');
            }

            $viagem->update([
                'validado'          => $aprovada ? CronoViagem::STATUS_APROVADA : CronoViagem::STATUS_REJEITADA,
                'data_aprovacao'    => now(),
                'obs_aprovacao'     => $obsAprovacao,
                'user_validacao_id' => Auth::id(),
            ]);

            // Recalcula agregados do CronoCaminhao parent
            $this->cronoCaminhaoService->recalcularEntregas($viagem->crono_caminhao_id);

            return $viagem->fresh();
        });
    }

    public function remover(int $id): bool
    {
        $viagem = CronoViagem::findOrFail($id);
        if ($viagem->validado === CronoViagem::STATUS_APROVADA) {
            throw new \DomainException('Viagem aprovada nao pode ser excluida. Rejeite primeiro.');
        }

        return DB::transaction(function () use ($viagem): bool {
            $cronoCaminhaoId = $viagem->crono_caminhao_id;
            $ok = (bool) $viagem->delete();
            $this->cronoCaminhaoService->recalcularEntregas($cronoCaminhaoId);

            return $ok;
        });
    }
}
