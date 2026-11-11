<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Video;

use App\Modules\Treinamento\Exceptions\FalhaDeVideo;
use App\Modules\Treinamento\Models\VideoUpload;
use App\Modules\Treinamento\Support\CaminhosVideo;
use Illuminate\Support\Facades\Storage;

/** Concatena as partes recebidas no arquivo original. Idempotente. */
class MontadorDeOriginal
{
    private const BLOCO = 1048576;

    /** @return string caminho relativo do original no disco `treinamentos` */
    public function montar(VideoUpload $upload): string
    {
        $disco = Storage::disk('treinamentos');
        $relativo = CaminhosVideo::original($upload->video_id, $upload->id);

        // Reexecucao: o original ja foi montado por uma tentativa anterior.
        if ($disco->exists($relativo) && $disco->size($relativo) === $upload->tamanho_total) {
            $disco->deleteDirectory(CaminhosVideo::partes($upload->id));

            return $relativo;
        }

        $disco->makeDirectory(CaminhosVideo::originais($upload->video_id));
        $destino = $disco->path($relativo);
        $temporario = $destino.'.tmp';

        $saida = fopen($temporario, 'wb');
        if ($saida === false) {
            throw new FalhaDeVideo('Nao foi possivel criar o arquivo original.');
        }

        try {
            for ($n = 1; $n <= $upload->total_partes; $n++) {
                $entrada = fopen($disco->path(CaminhosVideo::parte($upload->id, $n)), 'rb');
                if ($entrada === false) {
                    throw new FalhaDeVideo("Parte {$n} do upload ausente.", true);
                }
                try {
                    while (! feof($entrada)) {
                        fwrite($saida, (string) fread($entrada, self::BLOCO));
                    }
                } finally {
                    fclose($entrada);
                }
            }
        } catch (\Throwable $e) {
            fclose($saida);
            @unlink($temporario);
            throw $e;
        }
        fclose($saida);

        clearstatcache(true, $temporario);
        if (filesize($temporario) !== $upload->tamanho_total) {
            @unlink($temporario);
            throw new FalhaDeVideo('O arquivo montado nao confere com o tamanho enviado.', true);
        }

        rename($temporario, $destino);
        $disco->deleteDirectory(CaminhosVideo::partes($upload->id));

        return $relativo;
    }
}
