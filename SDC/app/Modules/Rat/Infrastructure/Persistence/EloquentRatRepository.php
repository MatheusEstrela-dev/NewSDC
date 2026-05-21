<?php

declare(strict_types=1);

namespace App\Modules\Rat\Infrastructure\Persistence;

use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class EloquentRatRepository implements RatRepositoryInterface
{
    public function create(array $data): RatOcorrencia
    {
        $userId = Auth::id();

        return RatOcorrencia::create([
            'numero_bos' => $data['numero_bos'] ?? $this->generateProtocolo(),
            'status'     => 0,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public function findById(string $id): ?RatOcorrencia
    {
        return RatOcorrencia::with([
            'creator',
            'updater',
            'relatosMorph.conteudo',
            'historicos',
        ])->find($id);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return RatOcorrencia::with(['creator'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getMunicipalities(): array
    {
        return RatRelatoDadosGerais::whereNotNull('local_municipio')
            ->distinct()
            ->pluck('local_municipio')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    public function delete(string $id): void
    {
        RatOcorrencia::where('id', $id)->delete();
    }

    public function updateStatus(string $id, int $status): void
    {
        RatOcorrencia::where('id', $id)->update([
            'status'     => $status,
            'updated_by' => Auth::id(),
        ]);
    }

    public function update(string $id, array $data): RatOcorrencia
    {
        $ocorrencia = RatOcorrencia::findOrFail($id);
        $ocorrencia->update(array_merge($data, ['updated_by' => Auth::id()]));
        return $ocorrencia->fresh();
    }

    public function findAll(int $limit = 10): Collection
    {
        return RatOcorrencia::with(['creator'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getLatestSequence(int $year): int
    {
        $seq = 0;

        // Formato atual: YYYY-NNNNNNNNN-XXX (sufixo 000 ou código real da unidade)
        $latestNew = RatOcorrencia::withTrashed()
            ->where('numero_bos', 'like', "{$year}-%-%")
            ->where('numero_bos', 'not like', 'RAT-%')
            ->lockForUpdate()
            ->orderByDesc('numero_bos')
            ->value('numero_bos');

        if ($latestNew && preg_match('/^\d{4}-(\d+)-\d{3}$/', $latestNew, $m)) {
            $seq = max($seq, (int) $m[1]);
        }

        // Formato legado: RAT-YYYY-NNNNNN
        $latestOld = RatOcorrencia::where('numero_bos', 'like', "RAT-{$year}-%")
            ->lockForUpdate()
            ->orderByDesc('numero_bos')
            ->value('numero_bos');

        if ($latestOld) {
            $seq = max($seq, (int) substr($latestOld, strrpos($latestOld, '-') + 1));
        }

        return $seq;
    }

    private function generateProtocolo(): string
    {
        $year = now()->year;
        $seq  = $this->getLatestSequence($year) + 1;

        return sprintf('%d-%09d-000', $year, $seq);
    }
}
