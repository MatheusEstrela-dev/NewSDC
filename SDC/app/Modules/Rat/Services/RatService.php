<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Models\Rat;
use App\Modules\Rat\Models\RatAnexo;
use App\Modules\Shared\BaseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RatService extends BaseService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Rat::query();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('numero_protocolo', 'like', "%{$filters['search']}%")
                  ->orWhere('municipio', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById(string $id): ?Rat
    {
        return Rat::with('anexos')->find($id);
    }

    public function findByIdAsJson(string $id): ?array
    {
        $rat = $this->findById($id);
        return $rat ? $rat->toArray() : null;
    }

    public function create(array $data): Rat
    {
        return Rat::create($data);
    }

    public function update(string $id, array $data): bool
    {
        $rat = Rat::find($id);
        if (!$rat) {
            return false;
        }
        return $rat->update($data);
    }

    public function delete(string $id): bool
    {
        $rat = Rat::find($id);
        if (!$rat) {
            return false;
        }
        return $rat->delete();
    }

    public function finalize(string $id): bool
    {
        $rat = Rat::find($id);
        if (!$rat) {
            return false;
        }
        return $rat->update(['status' => 'finalizado']);
    }

    public function getStatistics(): array
    {
        return [
            'total' => Rat::count(),
            'em_andamento' => Rat::where('status', 'em_andamento')->count(),
            'finalizados' => Rat::where('status', 'finalizado')->count(),
        ];
    }

    public function storeAnexos(string $ratId, array $files, ?int $userId = null): array
    {
        return DB::transaction(function () use ($ratId, $files, $userId) {
            $created = [];

            foreach ($files as $file) {
                if (!($file instanceof UploadedFile)) {
                    continue;
                }

                $nomeArquivo = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs("rat-anexos/{$ratId}", $nomeArquivo, 'local');

                $created[] = RatAnexo::create([
                    'rat_id'        => $ratId,
                    'user_id'       => $userId,
                    'nome_original' => $file->getClientOriginalName(),
                    'nome_arquivo'  => $nomeArquivo,
                    'mime_type'     => $file->getMimeType(),
                    'tamanho_bytes' => $file->getSize(),
                    'path'          => $path,
                ]);
            }

            return $created;
        });
    }

    public function deleteAnexo(string $ratId, string $anexoId): bool
    {
        $anexo = RatAnexo::where('rat_id', $ratId)->find($anexoId);

        if (!$anexo) {
            return false;
        }

        Storage::disk('local')->delete($anexo->path);
        $anexo->delete();

        return true;
    }

    public function sync(): void
    {
        // Sync logic with external API
    }

    public function export(array $filters = []): array
    {
        $query = Rat::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get()->toArray();
    }
}
