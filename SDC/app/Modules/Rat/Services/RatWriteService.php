<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\DTOs\CreateRatDTO;
use App\Modules\Rat\DTOs\UpdateRatDTO;
use App\Modules\Rat\Enums\Status;
use App\Modules\Rat\Models\Rat;
use Illuminate\Support\Facades\DB;

/**
 * Persiste alterações em RATs — criação, atualização, rascunho e finalização.
 * Usa o Model Eloquent diretamente, sem abstração de repositório.
 *
 * 5 métodos públicos:
 *   create()         — cria RAT em branco com protocolo único (transação)
 *   createWithData() — cria RAT com dados do formulário (transação)
 *   update()         — persiste dados com status EM_ANDAMENTO
 *   saveDraft()      — persiste dados mantendo status RASCUNHO
 *   finalize()       — muda status para FINALIZADO (com guard clauses)
 */
class RatWriteService
{
    public function __construct(
        private readonly RatProtocoloService $protocoloService,
    ) {}

    /** Cria um RAT em branco com protocolo sequencial único dentro de transação atômica. */
    public function create(): Rat
    {
        return DB::transaction(function () {
            $protocolo = $this->protocoloService->generate();

            return Rat::create(array_merge(
                ['created_by' => auth()->id(), 'updated_by' => auth()->id()],
                $this->buildInitialData($protocolo)
            ));
        });
    }

    /** Cria um RAT com dados do formulário em uma única transação. */
    public function createWithData(CreateRatDTO $dto): Rat
    {
        return DB::transaction(function () use ($dto) {
            $protocolo = $this->protocoloService->generate();

            $rat = Rat::create(array_merge(
                ['created_by' => auth()->id(), 'updated_by' => auth()->id()],
                $this->buildInitialData($protocolo)
            ));

            $data = $dto->toArray();

            if (!empty($data)) {
                $rat->update($this->buildUpdateData($data, Status::RASCUNHO->value));
            }

            return $rat->fresh();
        });
    }

    /** Persiste todos os campos editáveis e avança o status para EM_ANDAMENTO. */
    public function update(string $id, UpdateRatDTO $dto): Rat
    {
        $rat = Rat::findOrFail($id);
        $rat->update($this->buildUpdateData($dto->toArray(), Status::EM_ANDAMENTO->value));

        return $rat->fresh();
    }

    /** Persiste os dados mantendo o status RASCUNHO. */
    public function saveDraft(string $id, UpdateRatDTO $dto): Rat
    {
        $rat = Rat::findOrFail($id);
        $rat->update($this->buildUpdateData($dto->toArray(), Status::RASCUNHO->value));

        return $rat->fresh();
    }

    /** Finaliza o RAT — aborta se já finalizado. */
    public function finalize(string $id): Rat
    {
        $rat = Rat::findOrFail($id);
        abort_if($rat->isFinalizado(), 422, 'RAT já está finalizado.');

        $rat->update(['status' => Status::FINALIZADO->value, 'updated_by' => auth()->id()]);

        return $rat->fresh();
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function buildInitialData(string $protocolo): array
    {
        return [
            'protocolo'    => $protocolo,
            'status'       => Status::RASCUNHO->value,
            'dados_gerais' => [],
            'local'        => [],
            'endereco'     => [],
            'comunicacao'  => [],
            'recursos'     => [],
            'envolvidos'   => [],
            'vistoria'     => [],
            'historico'    => [],
        ];
    }

    private function buildUpdateData(array $data, string $status): array
    {
        $dadosGerais = $data['dadosGerais'] ?? null;

        return array_filter([
            'dados_gerais' => $dadosGerais,
            'comunicacao'  => $data['comunicacao'] ?? null,
            'local'        => $data['local']        ?? null,
            'endereco'     => $data['endereco']     ?? null,
            'tem_vistoria' => isset($dadosGerais['tem_vistoria'])
                ? (bool) $dadosGerais['tem_vistoria'] : null,
            'recursos'     => $data['recursos']     ?? null,
            'envolvidos'   => $data['envolvidos']   ?? null,
            'vistoria'     => $data['vistoria']     ?? null,
            'historico'    => $data['historico']    ?? null,
            'status'       => $status,
            'updated_by'   => auth()->id(),
        ], fn($v) => !is_null($v));
    }
}

