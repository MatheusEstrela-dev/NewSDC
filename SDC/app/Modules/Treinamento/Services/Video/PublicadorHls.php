<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Video;

use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Support\CaminhosVideo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/** Publica o HLS gerado como uma nova versao do video. */
class PublicadorHls
{
    private const JANELA_TROCA_SEGUNDOS = 600;

    /** @return string versao publicada */
    public function publicar(Video $video, string $tmpRelativo): string
    {
        $disco = Storage::disk('treinamentos');
        $versao = now()->format('YmdHis');

        while ($disco->exists(CaminhosVideo::hlsVersao($video->id, $versao))) {
            $versao .= 'b';
        }

        rename($disco->path($tmpRelativo), $disco->path(CaminhosVideo::hlsVersao($video->id, $versao)));

        // Janela de troca: quem ainda tem a playlist antiga continua autorizado.
        if ($video->hls_versao !== null) {
            Cache::put("video_versao_anterior:{$video->id}", $video->hls_versao, self::JANELA_TROCA_SEGUNDOS);
        }

        return $versao;
    }

    public function removerVersao(Video $video, string $versao): void
    {
        Storage::disk('treinamentos')->deleteDirectory(CaminhosVideo::hlsVersao($video->id, $versao));
    }

    public function removerTemporarios(Video $video): void
    {
        $disco = Storage::disk('treinamentos');

        foreach ($disco->directories(CaminhosVideo::hlsRaiz($video->id)) as $diretorio) {
            if (str_starts_with(basename($diretorio), '.tmp-')) {
                $disco->deleteDirectory($diretorio);
            }
        }
    }

    public function removerTudo(Video $video): void
    {
        Storage::disk('treinamentos')->deleteDirectory(CaminhosVideo::hlsRaiz($video->id));
        Cache::forget("video_versao_anterior:{$video->id}");
    }
}
