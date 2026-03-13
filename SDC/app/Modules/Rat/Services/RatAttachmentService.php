<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Models\Rat;
use App\Modules\Rat\Models\RatImagem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Gerencia upload e remoção de arquivos do RAT.
 *
 * Responsável por:
 *  - Persistir arquivos no disco (storage/app/public/rat/{id}/)
 *  - Criar/remover registros em rat_imagens
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

    /** Faz upload do arquivo, cria registro em rat_imagens e retorna o model. */
    public function store(Rat $rat, UploadedFile $file): RatImagem
    {
        $id   = (string) Str::uuid();
        $ext  = $file->getClientOriginalExtension();
        $path = $file->storeAs("rat/{$rat->id}", "{$id}.{$ext}", self::DISK);

        return RatImagem::create([
            'id'          => $id,
            'rat_id'      => $rat->id,
            'nome'        => $file->getClientOriginalName(),
            'path'        => $path,
            'tipo'        => $file->getMimeType(),
            'tamanho'     => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);
    }

    /** Remove o arquivo do disco e o registro de rat_imagens. */
    public function destroy(Rat $rat, string $imagemId): void
    {
        $imagem = RatImagem::where('rat_id', $rat->id)->where('id', $imagemId)->firstOrFail();

        Storage::disk(self::DISK)->delete($imagem->path);
        $imagem->delete();
    }
}
