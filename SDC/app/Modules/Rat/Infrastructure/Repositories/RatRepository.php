<?php

declare(strict_types=1);

namespace App\Modules\Rat\Infrastructure\Repositories;

use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\DTOs\RatFilterDTO;
use App\Modules\Rat\Models\Rat;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Implementação concreta do RatRepositoryInterface.
 *
 * Responsabilidades:
 * - Consultagas ao banco de dados via Eloquent
 * - Aplicar filtros, paginação e ordenação
 * - Retornar coleções de Rat models
 */
class RatRepository implements RatRepositoryInterface
{
    /**
     * Buscar um RAT por ID (UUID ou Inteiro).
     */
    public function findById(string $id): ?object
    {
        return Rat::find($id) ?? \App\Models\Rat\RatOcorrencia::find($id);
    }

    /**
     * Listar todos os RATs com paginação e filtros.
     */
    public function paginate(RatFilterDTO $filters): LengthAwarePaginator
    {
        // Agora usamos RatOcorrencia como fonte principal para a lista
        $query = \App\Models\Rat\RatOcorrencia::query();

        // Filtro por protocolo (numero_bos)
        if ($filters->protocolo) {
            $query->where('numero_bos', 'like', "%{$filters->protocolo}%");
        }

        // Filtro por status
        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }

        // Filtro por município, tipo COBRADE, natureza (estão no relato de Dados Gerais)
        // Nota: Filtros complexos podem exigir joins ou whereHas futuramente.
        
        // Filtro por data de início/fim/ano
        if ($filters->dataInicio) {
            $query->whereDate('created_at', '>=', $filters->dataInicio);
        }
        if ($filters->dataFim) {
            $query->whereDate('created_at', '<=', $filters->dataFim);
        }
        if ($filters->ano) {
            $query->whereYear('created_at', (int)$filters->ano);
        }

        // Filtro por criado por
        if ($filters->criadoPor) {
            $query->where('created_by', $filters->criadoPor);
        }

        // Ordenação padrão
        $query->orderBy('created_at', 'desc');

        // Paginação
        return $query->paginate($filters->perPage ?? 15);
    }

    /**
     * Obter lista de municípios disponíveis.
     */
    public function getMunicipalities(): array
    {
        return Rat::query()
            ->whereNotNull('local')
            ->get()
            ->pluck('local.municipio')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Criar novo RAT.
     */
    public function create(array $data): object
    {
        return Rat::create($data);
    }

    /**
     * Atualizar um RAT existente.
     */
    public function update(string $id, array $data): object
    {
        $rat = $this->findById($id);

        if (!$rat) {
            throw new \Exception("RAT com ID {$id} não encontrado");
        }

        if ($rat instanceof Rat) {
            $rat->update($data);
        } else {
            // Se for RatOcorrencia, a atualização é mais complexa e deve ser via Service
            // Mas para compatibilidade básica:
            $rat->update(array_filter([
                'status' => $data['status'] ?? null,
                'numero_bos' => $data['protocolo'] ?? null,
            ]));
        }

        return $rat;
    }

    /**
     * Deletar um RAT.
     */
    public function delete(string $id): void
    {
        $rat = $this->findById($id);

        if ($rat) {
            $rat->delete();
        }
    }

    /**
     * Contar total de RATs.
     */
    public function count(): int
    {
        return Rat::count();
    }

    /**
     * Contar RATs por status.
     */
    public function countByStatus(int $status): int
    {
        return Rat::where('status', $status)->count();
    }

    /**
     * Atualizar status de um RAT.
     */
    public function updateStatus(string $id, string $status): void
    {
        $rat = $this->findById($id);
        if (!$rat) {
            throw new \Exception("RAT com ID {$id} não encontrado");
        }
        $rat->update(['status' => $status]);
    }

    /**
     * Obter a sequência mais recente de um RAT para um determinado ano.
     */
    public function getLatestSequence(int $year): int
    {
        $latestRat = Rat::query()->whereYear('created_at', $year)->latest('created_at')->first();
        $latestOcorrencia = \App\Models\Rat\RatOcorrencia::query()->whereYear('created_at', $year)->latest('created_at')->first();

        $seqRat = $latestRat ? (int)substr($latestRat->protocolo ?? '0', -5) : 0;
        $seqOc = $latestOcorrencia ? (int)substr($latestOcorrencia->numero_bos ?? '0', -5) : 0;

        return (int) max($seqRat, $seqOc);
    }
}
