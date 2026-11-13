<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Jobs;

use App\Modules\Treinamento\Enums\VideoStatus;
use App\Modules\Treinamento\Exceptions\OperacaoDeVideoInvalida;
use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Models\VideoUpload;
use App\Modules\Treinamento\Services\Video\PublicadorHls;
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

    public int $timeout = 1800;

    public int $tries = 1;

    private const IDS_POR_CONSULTA = 1000;

    public function __construct()
    {
        $this->onConnection(config('treinamento.video.conexao_fila'));
        $this->onQueue(config('treinamento.video.fila'));
    }

    public function handle(VideoUploadService $uploads, PublicadorHls $publicador): void
    {
        $disco = Storage::disk(config('treinamento.video.disco'));

        // Cada etapa e isolada: falha em uma nao impede as seguintes.
        $etapas = [
            'uploads_cancelados' => fn () => $this->cancelarUploadsExpirados($uploads, $disco),
            'originais_removidos' => fn () => $this->limparOriginais($disco),
            'hls_removidos' => fn () => $this->limparHls($disco, $publicador),
            'logs_removidos' => fn () => $this->limparLogs(),
        ];

        Log::channel('treinamento')->info('video.limpeza', array_map(
            fn (callable $etapa) => $this->isolar($etapa) ?? 0,
            $etapas,
        ));
    }

    /**
     * Margem sobre o transcode mais longo: o mtime do diretorio nao muda no
     * rename, entao uma versao recem-publicada carrega o mtime da ultima
     * escrita do ffmpeg.
     */
    private static function carencia(): int
    {
        return (new \ReflectionClass(ProcessarVideoJob::class))->getDefaultProperties()['timeout'] + 3600;
    }

    /** Executa uma unidade de trabalho; a falha vai para o report e devolve null. */
    private function isolar(callable $acao): mixed
    {
        try {
            return $acao();
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function cancelarUploadsExpirados(VideoUploadService $uploads, Filesystem $disco): int
    {
        $cancelados = 0;

        VideoUpload::query()
            ->where('status', VideoUpload::ABERTO)
            ->where('expira_em', '<', now())
            ->chunkById(100, function ($lote) use ($uploads, $disco, &$cancelados) {
                foreach ($lote as $upload) {
                    $cancelados += $this->isolar(function () use ($upload, $uploads, $disco) {
                        $this->cancelarUpload($upload, $uploads, $disco);

                        return 1;
                    }) ?? 0;
                }
            });

        return $cancelados;
    }

    private function cancelarUpload(VideoUpload $upload, VideoUploadService $uploads, Filesystem $disco): void
    {
        $video = Video::withTrashed()->find($upload->video_id);

        // Video apagado: nao ha estado de video a reverter, so encerrar o upload.
        if ($this->semVideo($video)) {
            $disco->deleteDirectory(CaminhosVideo::partes($upload->id));
            $upload->update(['status' => VideoUpload::CANCELADO]);

            return;
        }

        try {
            $uploads->cancelar($upload);
        } catch (OperacaoDeVideoInvalida) {
            // Finalizado ou cancelado por outra replica no meio da varredura.
        }
    }

    /** Original so e necessario enquanto o video nao esta PRONTO (processar e reprocessar). */
    private function limparOriginais(Filesystem $disco): int
    {
        $manter = (bool) config('treinamento.video.manter_original');
        $removidos = 0;

        foreach ($this->videosPorDiretorio($disco, 'originais') as $id => $video) {
            $removidos += $this->isolar(function () use ($disco, $id, $video, $manter) {
                $dir = CaminhosVideo::originais($id);
                $remover = $this->semVideo($video)
                    || (! $manter && $video->status === VideoStatus::PRONTO && ! $this->recente($disco, $dir));

                if ($remover) {
                    $disco->deleteDirectory($dir);

                    return 1;
                }

                // Com acervo, o video PRONTO guarda so o original vigente: o de uma
                // troca que falhou de vez nao e de ninguem e ficaria para sempre.
                // Sem original_path (processado antes do acervo) nao ha o que comparar.
                if ($manter && $video->status === VideoStatus::PRONTO && $video->original_path !== null && ! $this->recente($disco, $dir)) {
                    return $this->removerOriginaisSuperados($disco, $dir, $video->original_path);
                }

                return 0;
            }) ?? 0;
        }

        return $removidos;
    }

    private function removerOriginaisSuperados(Filesystem $disco, string $dir, string $vigente): int
    {
        $superados = array_filter($disco->files($dir), fn (string $arquivo) => $arquivo !== $vigente);

        foreach ($superados as $arquivo) {
            $disco->delete($arquivo);
        }

        return count($superados) > 0 ? 1 : 0;
    }

    /** Toda remocao de HLS passa pelo publicador (mantem o gauge de bytes em dia). */
    private function limparHls(Filesystem $disco, PublicadorHls $publicador): int
    {
        $removidos = 0;

        foreach ($this->videosPorDiretorio($disco, 'hls') as $id => $video) {
            $removidos += $this->isolar(function () use ($disco, $publicador, $id, $video) {
                if ($this->semVideo($video)) {
                    $publicador->removerTudo($video ?? (new Video)->forceFill(['id' => $id]));

                    return 1;
                }

                return $this->limparVersoes($disco, $publicador, $video);
            }) ?? 0;
        }

        return $removidos;
    }

    private function limparVersoes(Filesystem $disco, PublicadorHls $publicador, Video $video): int
    {
        // A anterior segue valendo na janela de troca: aluno ainda pode estar assistindo.
        $preservar = array_filter([$video->hls_versao, Cache::get("video_versao_anterior:{$video->id}")]);
        $removidos = 0;

        foreach ($disco->directories(CaminhosVideo::hlsRaiz($video->id)) as $diretorio) {
            if (in_array(basename($diretorio), $preservar, true) || $this->recente($disco, $diretorio)) {
                continue;
            }

            $removidos += $this->isolar(function () use ($publicador, $video, $diretorio) {
                $publicador->removerVersao($video, basename($diretorio));

                return 1;
            }) ?? 0;
        }

        return $removidos;
    }

    private function limparLogs(): int
    {
        $limite = time() - (int) config('treinamento.video.log_ffmpeg_dias') * 86400;
        $removidos = 0;

        foreach (glob(dirname(CaminhosVideo::logFfmpeg(0, 'x')).'/*.log') ?: [] as $log) {
            $removidos += $this->isolar(fn () => (int) (filemtime($log) < $limite && @unlink($log))) ?? 0;
        }

        return $removidos;
    }

    private function semVideo(?Video $video): bool
    {
        return $video === null || $video->trashed();
    }

    /**
     * Videos (inclusive apagados) dos diretorios numericos de `$raiz`, indexados
     * pelo id; null onde o registro nao existe mais. Uma listagem e uma
     * consulta (em blocos) por etapa.
     *
     * @return Collection<int, ?Video>
     */
    private function videosPorDiretorio(Filesystem $disco, string $raiz): Collection
    {
        $ids = collect($disco->directories($raiz))
            ->map(fn (string $dir) => basename($dir))
            ->filter(fn (string $nome) => (string) (int) $nome === $nome && (int) $nome > 0)
            ->map(fn (string $nome) => (int) $nome);

        $videos = collect();

        foreach ($ids->chunk(self::IDS_POR_CONSULTA) as $bloco) {
            $videos = $videos->union(Video::withTrashed()->whereIn('id', $bloco->all())->get()->keyBy('id'));
        }

        return $ids->mapWithKeys(fn (int $id) => [$id => $videos->get($id)]);
    }

    private function recente(Filesystem $disco, string $relativo): bool
    {
        return $disco->lastModified($relativo) > time() - self::carencia();
    }
}
