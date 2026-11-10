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

    /**
     * So PDF pode abrir inline; qualquer outro tipo (entregas de aluno podem
     * ser HTML/SVG) sai sempre como anexo, e o nosniff impede o navegador de
     * reinterpretar o conteudo. O mime e o gravado no upload ou, sem ele, o
     * detectado no disco.
     */
    public function responder(string $path, string $nome, bool $download, ?string $mime = null): StreamedResponse
    {
        $disk = Storage::disk(self::DISK);
        abort_unless($disk->exists($path), 404);

        $inline = ! $download && ($mime ?? $disk->mimeType($path)) === 'application/pdf';

        return $disk->response($path, $nome, ['X-Content-Type-Options' => 'nosniff'], $inline ? 'inline' : 'attachment');
    }
}
