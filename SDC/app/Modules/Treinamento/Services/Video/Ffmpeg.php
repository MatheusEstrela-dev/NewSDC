<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Video;

use App\Modules\Treinamento\DTOs\SondagemVideo;
use App\Modules\Treinamento\Exceptions\FalhaDeVideo;
use Illuminate\Support\Facades\Process;

/** Encapsula ffprobe e ffmpeg. Recebe caminhos absolutos. */
class Ffmpeg
{
    private const TIMEOUT = 3500;

    public function sondar(string $absoluto): SondagemVideo
    {
        $resultado = Process::timeout(60)->run([
            config('treinamento.video.ffmpeg.ffprobe'), '-v', 'error', '-show_streams', '-show_format', '-of', 'json', $absoluto,
        ]);

        if ($resultado->failed()) {
            throw new FalhaDeVideo('Arquivo de video ilegivel: '.mb_substr($resultado->errorOutput(), -300), true);
        }

        $dados = json_decode($resultado->output(), true) ?: [];
        $video = collect($dados['streams'] ?? [])->firstWhere('codec_type', 'video');

        if ($video === null) {
            throw new FalhaDeVideo('O arquivo nao contem uma trilha de video.', true);
        }

        return new SondagemVideo(
            (int) ceil((float) ($dados['format']['duration'] ?? $video['duration'] ?? 0)),
            (int) ($video['width'] ?? 0),
            (int) ($video['height'] ?? 0),
            collect($dados['streams'])->contains('codec_type', 'audio'),
        );
    }

    public function transcodificar(string $origemAbs, string $destinoAbs, SondagemVideo $sondagem, string $logAbs): void
    {
        // O ffmpeg nomeia o subdiretorio de cada stream pelo `name:` do var_stream_map.
        foreach (array_keys($this->renditions($sondagem)) as $nome) {
            @mkdir($destinoAbs.'/'.$nome, 0775, true);
        }
        @mkdir(dirname($logAbs), 0775, true);

        $resultado = Process::timeout(self::TIMEOUT)->run($this->comando($origemAbs, $destinoAbs, $sondagem));
        file_put_contents($logAbs, $resultado->errorOutput());

        if ($resultado->failed()) {
            throw new FalhaDeVideo('Falha no ffmpeg: '.mb_substr($resultado->errorOutput(), -1000));
        }
    }

    /** @return list<string> */
    public function comando(string $origemAbs, string $destinoAbs, SondagemVideo $sondagem): array
    {
        $cfg = config('treinamento.video.ffmpeg');
        $renditions = $this->renditions($sondagem);
        $nomes = array_keys($renditions);
        $total = count($renditions);

        $rotulos = implode('', array_map(fn (int $i) => '[v'.($i + 1).']', array_keys($nomes)));
        $filtros = ["[0:v]split={$total}{$rotulos}"];
        foreach ($nomes as $i => $nome) {
            $n = $i + 1;
            // Proporcao preservada, dimensoes pares e sem ampliar a origem.
            $altura = $renditions[$nome]['altura'];
            $filtros[] = "[v{$n}]scale=w=-2:h='trunc(min(ih,{$altura})/2)*2'[v{$n}out]";
        }

        $comando = [
            'nice', '-n', (string) $cfg['nice'], $cfg['binario'], '-y', '-nostats', '-loglevel', 'warning', '-i', $origemAbs,
            '-threads', (string) $cfg['threads'], '-filter_complex', implode(';', $filtros),
        ];

        $mapa = [];
        foreach ($nomes as $i => $nome) {
            $n = $i + 1;
            $bitrate = $renditions[$nome]['bitrate'];
            array_push(
                $comando,
                '-map', "[v{$n}out]", "-c:v:{$i}", 'libx264', '-preset', $cfg['preset'], '-crf', (string) $cfg['crf'],
                "-maxrate:v:{$i}", $bitrate, "-bufsize:v:{$i}", $this->dobro($bitrate), '-pix_fmt', 'yuv420p',
            );
            if ($sondagem->temAudio) {
                array_push($comando, '-map', 'a:0', "-c:a:{$i}", 'aac', "-b:a:{$i}", $cfg['audio_bitrate']);
            }
            $mapa[] = ($sondagem->temAudio ? "v:{$i},a:{$i}" : "v:{$i}").",name:{$nome}";
        }

        array_push(
            $comando,
            '-force_key_frames', 'expr:gte(t,n_forced*'.$cfg['hls_time'].')', '-sc_threshold', '0',
            '-f', 'hls', '-hls_time', (string) $cfg['hls_time'], '-hls_playlist_type', 'vod', '-hls_segment_type', 'mpegts',
            '-hls_flags', 'independent_segments',
            '-hls_segment_filename', $destinoAbs.'/%v/%05d.ts', '-master_pl_name', 'master.m3u8',
            '-var_stream_map', implode(' ', $mapa), $destinoAbs.'/%v/index.m3u8',
        );

        return $comando;
    }

    /**
     * Renditions ativas que nao ampliam o original; sempre ao menos a menor.
     *
     * @return array<string, array<string, mixed>>
     */
    private function renditions(SondagemVideo $sondagem): array
    {
        $ativas = collect(config('treinamento.video.renditions'))->where('ativa', true)->sortBy('altura');
        $cabem = $ativas->filter(fn (array $r) => $r['altura'] <= $sondagem->altura);

        return ($cabem->isEmpty() ? $ativas->take(1) : $cabem)->all();
    }

    private function dobro(string $bitrate): string
    {
        return ((int) $bitrate * 2).preg_replace('/^\d+/', '', $bitrate);
    }
}
