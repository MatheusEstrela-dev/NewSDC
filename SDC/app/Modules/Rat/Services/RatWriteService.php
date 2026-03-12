<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\DTO\RatBoDTO;
use App\Modules\Rat\Models\Rat;
use Illuminate\Support\Facades\DB;

/**
 * Persiste alterações em ocorrências RAT (rat_ocorrencias).
 *
 * FLUXO: Controller -> RatWriteService -> Rat (Model) -> rat_ocorrencias
 *
 * Métodos públicos:
 *   create()          cria ocorrência em branco com numero_bos gerado
 *   createWithData()  cria com dados do DTO
 *   update()          persiste dados do DTO
 *   saveDraft()       persiste dados mantendo status = 0 (rascunho)
 *   finalize()        muda status para 1 (finalizado)
 *   delete()          remove ocorrência
 */
class RatWriteService
{
    /** Cria uma ocorrência em branco com numero_bos sequencial único. */
    public function create(): Rat
    {
        return DB::transaction(function () {
            $year = (int) date('Y');
            $seq  = $this->getLatestSequence($year) + 1;
            $bos  = sprintf('BOS-%d-%05d', $year, $seq);

            return Rat::create([
                'numero_bos'     => $bos,
                'sequencial_ano' => $seq,
                'status'         => 0,
                'created_by'     => (string) auth()->id(),
                'updated_by'     => (string) auth()->id(),
            ]);
        });
    }

    /** Cria uma ocorrência com dados fornecidos pelo DTO. */
    public function createWithData(RatBoDTO $dto): Rat
    {
        return DB::transaction(function () use ($dto) {
            $year = (int) date('Y');
            $seq  = $this->getLatestSequence($year) + 1;

            $data = array_merge($dto->toArray(), [
                'status'     => 0,
                'created_by' => (string) auth()->id(),
                'updated_by' => (string) auth()->id(),
            ]);

            if (empty($data['numero_bos'])) {
                $data['numero_bos']     = sprintf('BOS-%d-%05d', $year, $seq);
                $data['sequencial_ano'] = $seq;
            }

            return Rat::create($data);
        });
    }

    /** Persiste dados de uma ocorrência existente. */
    public function update(int $id, RatBoDTO $dto): Rat
    {
        $rat = Rat::findOrFail($id);

        $allowed = ['numero_bos', 'historico', 'prazo_edicao', 'ocorrencia_origem_id', 'status'];
        $payload = array_intersect_key($dto->toArray(), array_flip($allowed));
        $payload['updated_by'] = (string) auth()->id();

        $rat->update(array_filter($payload, fn ($v) => $v !== null));

        return $rat->fresh();
    }

    /** Persiste dados mantendo status = 0 (rascunho). */
    public function saveDraft(int $id, RatBoDTO $dto): Rat
    {
        $rat = Rat::findOrFail($id);

        $payload = array_merge(
            array_filter($dto->toArray(), fn ($v) => $v !== null),
            ['status' => 0, 'updated_by' => (string) auth()->id()]
        );

        $rat->update($payload);

        return $rat->fresh();
    }

    /** Finaliza a ocorrência — aborta se já finalizada. */
    public function finalize(int $id): Rat
    {
        $rat = Rat::findOrFail($id);
        abort_if($rat->isFinalized(), 422, 'Ocorrência já está finalizada.');

        $rat->update(['status' => 1, 'updated_by' => (string) auth()->id()]);

        return $rat->fresh();
    }

    /** Remove ocorrência (soft delete). */
    public function delete(int $id): void
    {
        Rat::findOrFail($id)->delete();
    }

    /** Retorna o maior sequencial_ano para o ano (usado para gerar BOS). */
    private function getLatestSequence(int $year): int
    {
        return (int) Rat::whereYear('created_at', $year)
            ->lockForUpdate()
            ->max('sequencial_ano');
    }
}
