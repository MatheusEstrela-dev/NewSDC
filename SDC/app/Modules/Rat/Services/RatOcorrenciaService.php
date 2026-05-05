<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\DTOs\RatBoDTO;
use App\Modules\Rat\Models\RatOcorrencia;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class RatOcorrenciaService
{
    public function manageOcorrencia(RatBoDTO $dto): RatOcorrencia
    {
        $data = array_filter([
            'numero_bos'   => $dto->numeroBos,
            'prazo_edicao' => $dto->prazoEdicao,
            'status'       => $dto->status ?? 0,
            'created_by'   => $dto->criadoPor ?? Auth::id(),
            'updated_by'   => Auth::id(),
        ], fn($v) => $v !== null);

        if ($dto->numeroBos) {
            return RatOcorrencia::updateOrCreate(
                ['numero_bos' => $dto->numeroBos],
                $data
            );
        }

        return RatOcorrencia::create($data);
    }

    public function findOrFail(int $id): RatOcorrencia
    {
        return RatOcorrencia::findOrFail($id);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return RatOcorrencia::orderByDesc('created_at')->paginate($perPage);
    }
}
