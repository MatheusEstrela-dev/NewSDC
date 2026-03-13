<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\Enums\Status;
use App\Modules\Rat\Models\Rat;
use Illuminate\Support\Facades\DB;

/**
 * Persiste alterações em RATs — criação, atualização, rascunho e finalização.
 * Depende de RatRepositoryInterface e RatProtocoloService (abstrações).
 *
 * 4 métodos púблicos:
 *   create()    — cria RAT em branco com protocolo único (transação)
 *   update()    — persiste dados com status EM_ANDAMENTO
 *   saveDraft() — persiste dados mantendo status RASCUNHO
 *   finalize()  — muda status para FINALIZADO (com guard clauses)
 */
class RatWriteService
{
    public function __construct(
        private readonly RatRepositoryInterface $repository,
        private readonly RatProtocoloService    $protocoloService,
    ) {}

    /** Cria um RAT em branco com protocolo sequencial único dentro de transação atômica. */
    public function create(): Rat
    {
        return DB::transaction(function () {
            $protocolo = $this->protocoloService->generate();
            return $this->repository->create($this->buildInitialData($protocolo));
        });
    }

    /** Cria um RAT com dados do formulário em uma única transação. */
    public function createWithData(array $data): Rat
    {
        return DB::transaction(function () use ($data) {
            $protocolo = $this->protocoloService->generate();
            $rat = $this->repository->create($this->buildInitialData($protocolo));
            return $this->repository->update($rat->id, array_merge($data, [
                'status' => Status::RASCUNHO->value,
            ]));
        });
    }

    /** Persiste todos os campos editáveis e avança o status para EM_ANDAMENTO. */
    public function update(string $id, array $data): Rat
    {
        return $this->repository->update($id, array_merge($data, [
            'status' => Status::EM_ANDAMENTO->value,
        ]));
    }

    /** Persiste os dados mantendo o status RASCUNHO. */
    public function saveDraft(string $id, array $data): Rat
    {
        return $this->repository->update($id, array_merge($data, [
            'status' => Status::RASCUNHO->value,
        ]));
    }

    /** Finaliza o RAT — aborta se já finalizado. */
    public function finalize(string $id): Rat
    {
        $rat = $this->repository->findById($id);
        abort_if(is_null($rat), 404, 'RAT não encontrado.');
        abort_if($rat->status === Status::FINALIZADO->value, 422, 'RAT já está finalizado.');

        $this->repository->updateStatus($id, Status::FINALIZADO->value);
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
            'anexos'       => [],
        ];
    }
}

