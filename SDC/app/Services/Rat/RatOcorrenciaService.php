<?php

declare(strict_types=1);

namespace App\Services\Rat;

use App\Modules\Rat\DTOs\RatBoDTO;
use App\Modules\Rat\DTOs\RatOcorrenciaFiltroDTO;
use App\Models\Rat\RatOcorrencia;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Regras de negócio da ocorrência RAT.
 *
 * Métodos públicos:
 *   manageOcorrencia() — cria ou atualiza uma ocorrência com validações
 *   finalizar()        — muda status para Finalizado (1)
 *   paginate()         — lista paginada com filtros opcionais
 *   findOrFail()       — busca por ID ou lança 404
 */
class RatOcorrenciaService
{
    private const STATUS_RASCUNHO   = 0;
    private const STATUS_FINALIZADO = 1;

    /**
     * Cria ou atualiza uma ocorrência a partir de um DTO tipado.
     * Gera número BOS sequencial se nenhum for informado.
     */
    public function manageOcorrencia(RatBoDTO $dto, ?int $id = null): RatOcorrencia
    {
        return DB::transaction(function () use ($dto, $id) {
            if ($id) {
                $ocorrencia = RatOcorrencia::findOrFail($id);
                $ocorrencia->update($dto->toArray());
                return $ocorrencia->fresh();
            }

            $data = $dto->toArray();

            if (empty($data['numero_bos'])) {
                $data['numero_bos'] = $this->generateNumeroBos();
            }

            $data['status']     = $data['status']     ?? self::STATUS_RASCUNHO;
            $data['created_by'] = $data['created_by'] ?? auth()->id();

            return RatOcorrencia::create($data);
        });
    }

    /**
     * Finaliza uma ocorrência — não permite re-finalização.
     */
    public function finalizar(int $id): RatOcorrencia
    {
        $ocorrencia = RatOcorrencia::findOrFail($id);

        abort_if(
            $ocorrencia->status === self::STATUS_FINALIZADO,
            422,
            'Ocorrência já está finalizada.'
        );

        $ocorrencia->update(['status' => self::STATUS_FINALIZADO]);

        return $ocorrencia->fresh();
    }

    /** Lista paginada de ocorrências com filtros do DTO. */
    public function paginate(RatOcorrenciaFiltroDTO $filtro): LengthAwarePaginator
    {
        $query = RatOcorrencia::query()->latest();

        if ($filtro->status !== null) {
            $query->where('status', $filtro->status);
        }

        if ($filtro->numeroBos !== null) {
            $query->where('numero_bos', 'like', "%{$filtro->numeroBos}%");
        }

        return $query->paginate($filtro->porPagina);
    }

    /** Busca por ID ou lança 404. */
    public function findOrFail(int $id): RatOcorrencia
    {
        return RatOcorrencia::with('relatosMorph')->findOrFail($id);
    }

    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------

    private function generateNumeroBos(): string
    {
        $year = date('Y');
        $seq  = RatOcorrencia::whereYear('created_at', $year)->count() + 1;

        return sprintf('%d-%05d', $year, $seq);
    }
}
