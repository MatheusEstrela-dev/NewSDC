<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Jobs;

use App\Modules\Treinamento\Enums\VideoStatus;
use App\Modules\Treinamento\Exceptions\OperacaoDeVideoInvalida;
use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Models\VideoUpload;
use App\Modules\Treinamento\Services\Video\VideoUploadService;
use App\Modules\Treinamento\Support\CaminhosVideo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Limpeza diaria: uploads vencidos, originais ja processados, versoes HLS
 * antigas, diretorios de videos inexistentes ou apagados e logs do ffmpeg velhos.
 */
class LimparVideosOrfaosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Diretorios HLS mais novos que isto podem estar sendo publicados agora. */
    private const CARENCIA_SEGUNDOS = 7200;

    public function __construct()
    {
        $this->onConnection(config('treinamento.video.conexao_fila'));
        $this->onQueue(config('treinamento.video.fila'));
    }

    public function handle(VideoUploadService $uploads): void
    {
        $disco = Storage::disk(config('treinamento.video.disco'));

        Log::channel('treinamento')->info('video.limpeza', [
            'uploads_cancelados' => $this->cancelarUploadsExpirados($uploads),
            'originais_removidos' => $this->limparOriginais($disco),
            'hls_removidos' => $this->limparHls($disco),
            'logs_removidos' => $this->limparLogs(),
        ]);
    }

    private function cancelarUploadsExpirados(VideoUploadService $uploads): int
    {
        $cancelados = 0;

        VideoUpload::query()
            ->where('status', VideoUpload::ABERTO)
            ->where('expira_em', '<', now())
            ->each(function (VideoUpload $upload) use ($uploads, &$cancelados) {
                try {
                    $uploads->cancelar($upload);
                    $cancelados++;
                } catch (OperacaoDeVideoInvalida) {
                    // Finalizado ou cancelado por outra replica no meio da varredura.
                }
            });

        return $cancelados;
    }

    /** Original so e necessario enquanto o video nao esta PRONTO (processar e reprocessar). */
    private function limparOriginais(Filesystem $disco): int
    {
        $manter = (bool) config('treinamento.video.manter_original');

        return $this->removerDiretorios(
            $disco,
            'originais',
            fn (?Video $video) => $this->semVideo($video) || (! $manter && $video->status === VideoStatus::PRONTO),
        );
    }

    private function limparHls(Filesystem $disco): int
    {
        $removidos = $this->removerDiretorios($disco, 'hls', fn (?Video $video) => $this->semVideo($video));

        foreach ($this->videosPorDiretorio($disco, 'hls') as $id => $video) {
            if ($this->semVideo($video)) {
                continue;
            }

            // A anterior segue valendo na janela de troca: aluno ainda pode estar assistindo.
            $preservar = array_filter([$video->hls_versao, Cache::get("video_versao_anterior:{$id}")]);

            foreach ($disco->directories(CaminhosVideo::hlsRaiz($id)) as $diretorio) {
                if (in_array(basename($diretorio), $preservar, true) || $this->recente($disco, $diretorio)) {
                    continue;
                }

                $disco->deleteDirectory($diretorio);
                $removidos++;
            }
        }

        return $removidos;
    }

    private function limparLogs(): int
    {
        $limite = time() - (int) config('treinamento.video.log_ffmpeg_dias') * 86400;
        $removidos = 0;

        foreach (glob(dirname(CaminhosVideo::logFfmpeg(0, 'x')).'/*.log') ?: [] as $log) {
            if (filemtime($log) < $limite && @unlink($log)) {
                $removidos++;
            }
        }

        return $removidos;
    }

    private function semVideo(?Video $video): bool
    {
        return $video === null || $video->trashed();
    }

    /** @param  callable(?Video): bool  $remover */
    private function removerDiretorios(Filesystem $disco, string $raiz, callable $remover): int
    {
        $removidos = 0;

        foreach ($this->videosPorDiretorio($disco, $raiz) as $id => $video) {
            if ($remover($video)) {
                $disco->deleteDirectory("{$raiz}/{$id}");
                $removidos++;
            }
        }

        return $removidos;
    }

    /**
     * Videos (inclusive apagados) dos diretorios numericos de `$raiz`, indexados
     * pelo id; null onde o registro nao existe mais.
     *
     * @return Collection<int, ?Video>
     */
    private function videosPorDiretorio(Filesystem $disco, string $raiz): Collection
    {
        $ids = collect($disco->directories($raiz))
            ->map(fn (string $dir) => basename($dir))
            ->filter(fn (string $nome) => ctype_digit($nome))
            ->map(fn (string $nome) => (int) $nome);

        $videos = Video::withTrashed()->whereIn('id', $ids->all())->get()->keyBy('id');

        return $ids->mapWithKeys(fn (int $id) => [$id => $videos->get($id)]);
    }

    private function recente(Filesystem $disco, string $relativo): bool
    {
        return $disco->lastModified($relativo) > time() - self::CARENCIA_SEGUNDOS;
    }
}
