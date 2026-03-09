<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Models\Rat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * SRP: gerencia upload e remoção de arquivos anexos de um RAT.
 *
 * Responsável por:
 *  - Persistir arquivos no disco (storage/app/public/rat/{id}/)
 *  - Manter o array JSON rat.anexos atualizado
 *  - Remover arquivos do disco ao excluir
 */
class RatAttachmentService
{
    private const DISK          = 'public';
    private const MAX_KB        = 10 * 1024;
    private const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
    ];

    public function getAllowedMimes(): array { return self::ALLOWED_MIMES; }
    public function getMaxKb(): int         { return self::MAX_KB; }

    /** Faz upload do arquivo, persiste metadados em rat.anexos e retorna o registro criado. */
    public function store(Rat $rat, UploadedFile $file): array
    {
        ['id' => $id, 'path' => $path] = $this->persist($rat->id, $file);
        $anexo = $this->buildMetadata($file, $id, $path);
        $rat->update(['anexos' => [...($rat->anexos ?? []), $anexo]]);

        return $anexo;
    }

    /** Remove o arquivo do disco e o registro de rat.anexos. */
    public function destroy(Rat $rat, string $anexoId): void
    {
        $existing = collect($rat->anexos ?? []);
        $target   = $existing->firstWhere('id', $anexoId);

        if ($target && !empty($target['path'])) {
            Storage::disk(self::DISK)->delete($target['path']);
        }

        $rat->update([
            'anexos' => $existing->reject(fn($a) => $a['id'] === $anexoId)->values()->all(),
        ]);
    }

    /** Grava o arquivo no disco e retorna id e path. */
    private function persist(string $ratId, UploadedFile $file): array
    {
        $id   = (string) Str::uuid();
        $ext  = $file->getClientOriginalExtension();
        $path = $file->storeAs("rat/{$ratId}", "{$id}.{$ext}", self::DISK);

        return ['id' => $id, 'path' => $path];
    }

    /** Monta o array de metadados do anexo. */
    private function buildMetadata(UploadedFile $file, string $id, string $path): array
    {
        return [
            'id'          => $id,
            'nome'        => $file->getClientOriginalName(),
            'tamanho'     => $file->getSize(),
            'tipo'        => $file->getMimeType(),
            'url'         => Storage::disk(self::DISK)->url($path),
            'path'        => $path,
            'data_upload' => now()->toIso8601String(),
        ];
    }
}
