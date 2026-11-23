<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use GdImage;
use Illuminate\Http\UploadedFile;

/**
 * Otimiza imagens de capa (do curso e das pastas de categoria) antes de gravar:
 * corrige a rotacao das fotos de celular (EXIF), reduz para no maximo 1920 px
 * de largura e converte para WebP. O envio aceita ate 10 MB, mas quem abre a
 * vitrine baixa so algumas centenas de KB.
 *
 * Nunca impede o envio: sem GD/WebP, formato que o GD nao le ou resultado maior
 * que o original sem ter reduzido, grava o arquivo como veio.
 */
final class OtimizadorDeImagem
{
    private const LARGURA_MAXIMA = 1920;

    private const QUALIDADE_WEBP = 82;

    /**
     * Entrega ao $gravar a imagem otimizada (ou a original) e apaga o temporario
     * em seguida, mesmo se a gravacao falhar.
     *
     * @template T
     *
     * @param  callable(UploadedFile): T  $gravar
     * @return T
     */
    public static function paraCapa(UploadedFile $imagem, callable $gravar): mixed
    {
        $temporario = self::otimizar($imagem);

        try {
            return $gravar($temporario ?? $imagem);
        } finally {
            if ($temporario !== null) {
                @unlink($temporario->getPathname());
            }
        }
    }

    private static function otimizar(UploadedFile $imagem): ?UploadedFile
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return null;
        }

        $conteudo = @file_get_contents($imagem->getRealPath());
        $origem = $conteudo !== false ? @imagecreatefromstring($conteudo) : false;
        if (! $origem instanceof GdImage) {
            return null;
        }

        $origem = self::orientar($origem, $imagem);
        $largura = imagesx($origem);
        $reduzir = $largura > self::LARGURA_MAXIMA;
        $final = $reduzir ? imagescale($origem, self::LARGURA_MAXIMA, -1, IMG_BICUBIC) : $origem;
        if (! $final instanceof GdImage) {
            return null;
        }

        // PNG/WebP com transparencia: mantem o canal alfa.
        imagepalettetotruecolor($final);
        imagealphablending($final, false);
        imagesavealpha($final, true);

        $caminho = tempnam(sys_get_temp_dir(), 'capa_');
        if ($caminho === false) {
            return null;
        }

        $tamanho = imagewebp($final, $caminho, self::QUALIDADE_WEBP) ? (int) filesize($caminho) : 0;

        // Sem ganho (nem reducao de tamanho, nem de largura): fica o original.
        if ($tamanho === 0 || (! $reduzir && $tamanho >= (int) $imagem->getSize())) {
            @unlink($caminho);

            return null;
        }

        $nome = pathinfo($imagem->getClientOriginalName(), PATHINFO_FILENAME).'.webp';

        return new UploadedFile($caminho, $nome, 'image/webp', null, true);
    }

    /** Fotos de celular guardam a rotacao no EXIF, que o GD ignora: aplica aqui. */
    private static function orientar(GdImage $imagem, UploadedFile $arquivo): GdImage
    {
        if (! function_exists('exif_read_data') || $arquivo->getMimeType() !== 'image/jpeg') {
            return $imagem;
        }

        $exif = @exif_read_data($arquivo->getRealPath());
        $angulo = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angulo === 0) {
            return $imagem;
        }

        $rotacionada = imagerotate($imagem, $angulo, 0);

        return $rotacionada instanceof GdImage ? $rotacionada : $imagem;
    }
}
