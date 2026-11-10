<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Arquivos do EAD (PDFs dos itens e entregas de projeto) no disco privado
 * `treinamentos`. O binario so sai por stream, em endpoint ja autorizado.
 */
class ArquivoCursoService
{
    public const DISK = 'treinamentos';

    /**
     * @return array{arquivo_path: string, arquivo_nome: string, arquivo_mime: string|null, arquivo_tamanho: int}
     */
    public function guardar(UploadedFile $arquivo, string $diretorio): array
    {
        $extensao = $arquivo->guessExtension() ?: ($arquivo->getClientOriginalExtension() ?: 'bin');
        $path = Storage::disk(self::DISK)->putFileAs($diretorio, $arquivo, Str::uuid()->toString().'.'.$extensao);

        if ($path === false) {
            throw new \RuntimeException('Nao foi possivel gravar o arquivo.');
        }

        return [
            'arquivo_path' => $path,
            'arquivo_nome' => $arquivo->getClientOriginalName(),
            'arquivo_mime' => $arquivo->getMimeType(),
            'arquivo_tamanho' => (int) $arquivo->getSize(),
        ];
    }

    public function apagar(?string $path): void
    {
        if ($path !== null && $path !== '') {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public function responder(string $path, string $nome, bool $download): StreamedResponse
    {
        $disk = Storage::disk(self::DISK);
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, $nome, [], $download ? 'attachment' : 'inline');
    }
}
