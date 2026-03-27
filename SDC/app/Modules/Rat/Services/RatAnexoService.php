<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Enums\CategoriaAnexo;
use App\Modules\Rat\Models\Rat;
use App\Modules\Rat\Models\RatAnexo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gerencia upload, listagem, download e remocao de anexos de um RAT.
 *
 * Persistencia: tabela rat_anexos (relacional) + disco configuravel.
 * Estrutura de pastas: rat/{ratId}/{categoria_folder}/{uuid}.{ext}
 */
class RatAnexoService
{
    private const DISK    = 'public';
    private const MAX_KB  = 10 * 1024;

    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
    ];

    public function getAllowedMimes(): array
    {
        return self::ALLOWED_MIMES;
    }

    public function getMaxKb(): int
    {
        return self::MAX_KB;
    }

    /**
     * Faz upload do arquivo, persiste no disco e cria registro na tabela.
     */
    public function store(
        Rat $rat,
        UploadedFile $file,
        CategoriaAnexo $categoria,
        ?string $descricao = null,
    ): RatAnexo {
        return $this->storeForRatId($rat->id, $file, $categoria, $descricao);
    }

    /**
     * Faz upload usando o ID do RAT diretamente (suporta rats UUID e rat_ocorrencias int).
     */
    public function storeForRatId(
        string $ratId,
        UploadedFile $file,
        CategoriaAnexo $categoria,
        ?string $descricao = null,
    ): RatAnexo {
        $uuid      = (string) Str::uuid();
        $ext       = $file->getClientOriginalExtension();
        $nomeArq   = "{$uuid}.{$ext}";
        $folder    = $categoria->getStorageFolder();
        $directory = "rat/{$ratId}/{$folder}";
        $path      = $file->storeAs($directory, $nomeArq, self::DISK);

        return RatAnexo::create([
            'rat_id'        => $ratId,
            'categoria'     => $categoria->value,
            'nome_original' => $file->getClientOriginalName(),
            'nome_arquivo'  => $nomeArq,
            'mime_type'     => $file->getMimeType(),
            'tamanho_bytes' => $file->getSize(),
            'path'          => $path,
            'disk'          => self::DISK,
            'descricao'     => $descricao,
            'uploaded_by'   => auth()->id(),
        ]);
    }

    /**
     * Remove o arquivo do disco e o registro da tabela.
     */
    public function destroy(RatAnexo $anexo): void
    {
        Storage::disk($anexo->disk)->delete($anexo->path);
        $anexo->delete();
    }

    /**
     * Lista anexos de um RAT, opcionalmente filtrados por categoria.
     */
    public function listByRat(
        Rat $rat,
        ?CategoriaAnexo $categoria = null,
    ): Collection {
        $query = $rat->anexos();

        if ($categoria) {
            $query->where('categoria', $categoria->value);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Retorna resposta de download streaming do arquivo.
     */
    public function getDownloadResponse(RatAnexo $anexo): StreamedResponse
    {
        return Storage::disk($anexo->disk)->download(
            $anexo->path,
            $anexo->nome_original,
        );
    }
}
