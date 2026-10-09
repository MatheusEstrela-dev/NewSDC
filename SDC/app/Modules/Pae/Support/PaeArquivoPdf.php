<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * PDF dos registros do PAE no disco `pae`. Quem grava dentro de uma transacao
 * usa executar(): qualquer falha depois de guardar remove o arquivo orfao.
 */
final class PaeArquivoPdf
{
    public const DISCO = 'pae';

    private ?string $path = null;

    /**
     * @template T
     *
     * @param  Closure(self): T  $operacao
     * @return T
     */
    public static function executar(Closure $operacao): mixed
    {
        $guardador = new self();
        try {
            return $operacao($guardador);
        } catch (Throwable $e) {
            $guardador->descartar();
            throw $e;
        }
    }

    /**
     * @return array{arquivo_path: string, arquivo_nome_original: string, arquivo_mime: ?string, arquivo_tamanho_bytes: ?int}
     */
    public function guardar(string $diretorio, UploadedFile $arquivo, string $mensagemFalha): array
    {
        $nome = (string) Str::uuid().'.pdf';
        $path = "{$diretorio}/{$nome}";
        if (Storage::disk(self::DISCO)->putFileAs($diretorio, $arquivo, $nome) !== $path) {
            throw new RuntimeException($mensagemFalha);
        }
        $this->path = $path;

        return [
            'arquivo_path' => $path,
            'arquivo_nome_original' => $arquivo->getClientOriginalName(),
            'arquivo_mime' => $arquivo->getMimeType(),
            'arquivo_tamanho_bytes' => $arquivo->getSize(),
        ];
    }

    public static function existe(string $path): bool
    {
        return Storage::disk(self::DISCO)->exists($path);
    }

    public static function baixar(string $path, string $nome): StreamedResponse
    {
        return Storage::disk(self::DISCO)->download($path, $nome);
    }

    private function descartar(): void
    {
        if ($this->path !== null) {
            Storage::disk(self::DISCO)->delete($this->path);
        }
    }
}
