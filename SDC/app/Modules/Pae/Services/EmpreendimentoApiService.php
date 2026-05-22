<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Modules\Pae\Models\PaeEmpnto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EmpreendimentoApiService
{
    private const DEFAULT_PER_PAGE = 15;
    private const MAX_PER_PAGE = 100;

    /** @var array<int, string> */
    private const RELATIONS_LIST = ['municipio', 'empdor', 'latestProtocolo'];

    /**
     * @param array<string, mixed> $filters
     */
    public function listPaginated(array $filters = [], int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $perPage = max(1, min(self::MAX_PER_PAGE, $perPage));

        return PaeEmpnto::query()
            ->with(self::RELATIONS_LIST)
            ->when($filters['municipio_id'] ?? null, fn ($q, $v) => $q->where('municipio_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('nome', 'like', "%{$v}%"))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findById(int $id): PaeEmpnto
    {
        return PaeEmpnto::with(self::RELATIONS_LIST)->findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): PaeEmpnto
    {
        $emp = PaeEmpnto::create($data + ['user_update' => (string) (auth()->id() ?? 0)]);

        return $emp->load(self::RELATIONS_LIST);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(PaeEmpnto $emp, array $data): PaeEmpnto
    {
        $emp->fill($data + ['user_update' => (string) (auth()->id() ?? $emp->user_update)]);
        $emp->save();

        return $emp->load(self::RELATIONS_LIST);
    }

    public function delete(PaeEmpnto $emp): void
    {
        $emp->delete();
    }
}
