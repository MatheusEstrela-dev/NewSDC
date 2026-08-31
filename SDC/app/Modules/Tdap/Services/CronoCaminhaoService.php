<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Modules\Tdap\DTOs\CronoCaminhaoDTO;
use App\Modules\Tdap\Models\Caminhao;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\CronoCaminhao;
use App\Modules\Tdap\Models\CronoViagem;
use App\Modules\Tdap\Models\Lote;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CronoCaminhaoService
{
    public function listarDoCronograma(int $cronogramaId): Collection
    {
        return CronoCaminhao::query()
            ->doCronograma($cronogramaId)
            ->with(['caminhao:id,placa,marca,modelo,capacidade_m3,ativo'])
            ->withCount(['viagensValidadas', 'viagensPendentes'])
            ->orderBy('ordem')
            ->get();
    }

    public function obter(int $id): CronoCaminhao
    {
        return CronoCaminhao::query()
            ->with(['cronograma:id,numero', 'caminhao:id,placa,marca,modelo,capacidade_m3'])
            ->withCount(['viagensValidadas', 'viagensPendentes'])
            ->findOrFail($id);
    }

    public function alocar(CronoCaminhaoDTO $dto): CronoCaminhao
    {
        return DB::transaction(function () use ($dto): CronoCaminhao {
            $cronograma = Cronograma::findOrFail($dto->cronograma_id);

            if ($cronograma->encerrado_em) {
                throw new \DomainException('Cronograma encerrado nao aceita novos caminhoes.');
            }

            $caminhao = Caminhao::findOrFail($dto->caminhao_id);
            if (! $caminhao->ativo) {
                throw new \DomainException("Caminhao {$caminhao->placa} esta inativo.");
            }

            $jaAlocado = CronoCaminhao::query()
                ->where('cronograma_id', $dto->cronograma_id)
                ->where('caminhao_id', $dto->caminhao_id)
                ->whereNull('deleted_at')
                ->exists();

            if ($jaAlocado) {
                throw new \DomainException("Caminhao {$caminhao->placa} ja alocado neste cronograma.");
            }

            return CronoCaminhao::create($dto->toArray());
        });
    }

    public function atualizar(int $id, CronoCaminhaoDTO $dto): CronoCaminhao
    {
        return DB::transaction(function () use ($id, $dto): CronoCaminhao {
            $cc = CronoCaminhao::query()->with('cronograma:id,encerrado_em')->findOrFail($id);

            // Mesmo guard de alocar(): cronograma encerrado e registro fechado.
            // Sem ele era possivel remexer em agua_prevista depois do
            // encerramento e desalinhar o volume contratado do que foi pago.
            if ($cc->cronograma?->encerrado_em) {
                throw new \DomainException('Cronograma encerrado nao aceita alteracao de alocacao.');
            }

            // somente campos editaveis (caminhao nao pode trocar via update; remova e aloque outro)
            $cc->update([
                'comunidade_id' => $dto->comunidade_id,
                'agua_prevista' => $dto->agua_prevista,
                'num_viagens'   => $dto->num_viagens,
                'ordem'         => $dto->ordem,
            ]);

            return $cc->fresh();
        });
    }

    public function remover(int $id): bool
    {
        $cc = CronoCaminhao::query()
            ->withCount('viagens')
            ->findOrFail($id);

        if ($cc->viagens_count > 0) {
            throw new \DomainException(
                "Este caminhao tem {$cc->viagens_count} viagem(ns) registrada(s). Remova-as antes."
            );
        }

        return (bool) $cc->delete();
    }

    /**
     * Recalcula agua_entregue e vr_total com base nas viagens validadas.
     * Chamado pelo CronoViagemService apos validar/rejeitar.
     */
    public function recalcularEntregas(int $cronoCaminhaoId): void
    {
        DB::transaction(function () use ($cronoCaminhaoId): void {
            $cc = CronoCaminhao::query()
                ->with(['caminhao:id,capacidade_m3', 'cronograma.lote:id,valor_m3'])
                ->lockForUpdate()
                ->find($cronoCaminhaoId);

            // Sem crono_caminhao correspondente: nada a recalcular.
            if ($cc === null) {
                return;
            }

            $viagensValidadas = (int) CronoViagem::query()
                ->where('crono_caminhao_id', $cronoCaminhaoId)
                ->where('validado', CronoViagem::STATUS_APROVADA)
                ->count();

            $capacidade = (float) ($cc->caminhao?->capacidade_m3 ?? 0);
            $valorM3 = (float) ($cc->cronograma?->lote?->valor_m3 ?? 0);

            $aguaEntregue = $viagensValidadas * $capacidade;
            $vrTotal = $aguaEntregue * $valorM3;

            // forceFill: agua_entregue e vr_total nao estao em $fillable
            // (sao derivados — somente este Service pode escreve-los).
            $cc->forceFill([
                'agua_entregue' => $aguaEntregue,
                'vr_total'      => $vrTotal,
            ])->save();
        });
    }
}
