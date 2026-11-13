<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Imagem de capa do curso, no mesmo disco privado dos demais arquivos do EAD.
 * Uma capa por curso: a nova substitui a anterior.
 */
class CapaDoCursoService
{
    public const MIMES = ['jpg', 'jpeg', 'png', 'webp'];

    public const TAMANHO_MAXIMO_KB = 2048;

    public function __construct(private readonly ArquivoCursoService $arquivos) {}

    public function trocar(Treinamento $curso, UploadedFile $imagem): void
    {
        $anterior = $curso->capa_path;
        $nova = $this->arquivos->guardar($imagem, "capas/{$curso->id}")['arquivo_path'];

        $this->gravar($curso, $nova);
        $this->arquivos->apagar($anterior);
    }

    public function remover(Treinamento $curso): void
    {
        $anterior = $curso->capa_path;

        $this->gravar($curso, null);
        $this->arquivos->apagar($anterior);
    }

    public function responder(Treinamento $curso): StreamedResponse
    {
        $disco = Storage::disk(ArquivoCursoService::DISK);
        abort_if($curso->capa_path === null || ! $disco->exists($curso->capa_path), 404);

        // O endereco leva o carimbo da ultima troca: a imagem de um endereco
        // nunca muda, entao pode ficar no cache do navegador.
        return $disco->response($curso->capa_path, null, [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** A coluna nao e de preenchimento em massa; grava pelo model para avisar as telas abertas. */
    private function gravar(Treinamento $curso, ?string $caminho): void
    {
        $curso->forceFill(['capa_path' => $caminho])->save();
    }
}
