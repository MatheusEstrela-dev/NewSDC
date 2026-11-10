<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Video;

use App\Modules\Treinamento\Enums\VideoStatus;
use App\Modules\Treinamento\Jobs\ProcessarVideoJob;
use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Models\VideoUpload;
use App\Modules\Treinamento\Support\CaminhosVideo;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Upload em partes. Requisicoes consecutivas do mesmo upload caem em replicas
 * diferentes: nada vive em memoria do processo; o estado esta em
 * treinamento.video_uploads e no disco `treinamentos`, e a exclusao mutua usa
 * Cache::lock (redis compartilhado).
 */
class VideoUploadService
{
    private const TRAVA_TTL = 30;

    private const TRAVA_ESPERA = 5;

    public function abrir(Video $video, int $userId, string $nome, int $tamanhoTotal, string $mime): VideoUpload
    {
        if ($tamanhoTotal > (int) config('treinamento.video.tamanho_maximo_bytes')) {
            throw new \DomainException('O arquivo excede o tamanho maximo permitido.');
        }
        if (! in_array($mime, config('treinamento.video.mimes'), true)) {
            throw new \DomainException('Formato de video nao aceito.');
        }

        return $this->comTrava("video-upload-abrir:{$video->id}", function () use ($video, $userId, $nome, $tamanhoTotal, $mime) {
            $video->refresh();

            if ($video->status->emProcessamento()) {
                throw new \DomainException('O video esta em processamento; aguarde antes de enviar outro arquivo.');
            }

            return ConexaoTreinamento::transacao(function () use ($video, $userId, $nome, $tamanhoTotal, $mime) {
                $tamanhoParte = (int) config('treinamento.video.tamanho_parte_bytes');

                $video->uploads()->where('status', VideoUpload::ABERTO)->get()
                    ->each(fn (VideoUpload $anterior) => $this->encerrar($anterior));

                $upload = $video->uploads()->create([
                    'user_id' => $userId,
                    'nome_original' => $nome,
                    'mime' => $mime,
                    'tamanho_total' => $tamanhoTotal,
                    'tamanho_parte' => $tamanhoParte,
                    'total_partes' => (int) ceil($tamanhoTotal / $tamanhoParte),
                    'partes_recebidas' => 0,
                    'tamanho_recebido' => 0,
                    'status' => VideoUpload::ABERTO,
                    'expira_em' => now()->addHours((int) config('treinamento.video.upload_expira_horas')),
                ]);

                // Video PRONTO continua servindo a versao atual ate o novo ficar pronto.
                if ($video->status === VideoStatus::SEM_VIDEO || $video->status === VideoStatus::FALHOU) {
                    $video->update(['status' => VideoStatus::UPLOAD_ABERTO]);
                }

                return $upload;
            });
        });
    }

    public function receberParte(VideoUpload $upload, int $n, UploadedFile $parte): VideoUpload
    {
        return $this->comTrava("video-upload:{$upload->id}", function () use ($upload, $n, $parte) {
            $upload->refresh();

            if (! $upload->estaAberto()) {
                throw new \DomainException('Este upload nao esta aberto.');
            }
            if ($n < 1 || $n > $upload->total_partes) {
                throw new \DomainException('Numero de parte fora da faixa.');
            }
            if ($parte->getSize() !== $this->tamanhoEsperado($upload, $n)) {
                throw new \DomainException('Tamanho da parte diferente do esperado.');
            }

            $parte->storeAs(CaminhosVideo::partes($upload->id), "{$n}.part", 'treinamentos');

            // Recontado do disco: reenviar uma parte nao duplica a contagem.
            $disco = Storage::disk('treinamentos');
            $arquivos = $disco->files(CaminhosVideo::partes($upload->id));

            $upload->update([
                'partes_recebidas' => count($arquivos),
                'tamanho_recebido' => array_sum(array_map(fn (string $arquivo) => $disco->size($arquivo), $arquivos)),
            ]);

            return $upload;
        });
    }

    public function finalizar(VideoUpload $upload): Video
    {
        return $this->comTrava("video-upload:{$upload->id}", function () use ($upload) {
            $upload->refresh();
            $video = $upload->video;

            // Segunda chamada (ou outra replica): ja despachado.
            if ($upload->status === VideoUpload::COMPLETO) {
                return $video;
            }
            if (! $upload->estaAberto()) {
                throw new \DomainException('Este upload nao esta aberto.');
            }
            if (! $upload->completo()) {
                throw new \DomainException('Ainda faltam partes para concluir o envio.');
            }

            ConexaoTreinamento::transacao(function () use ($upload, $video) {
                $upload->update(['status' => VideoUpload::COMPLETO]);

                // Com versao anterior publicada o video segue PRONTO ate o novo processar.
                $video->update($video->hls_versao !== null
                    ? ['erro' => null]
                    : ['status' => VideoStatus::ENVIADO, 'erro' => null]);
            });

            // Depois do commit e ainda sob a trava: um unico despacho por upload.
            ProcessarVideoJob::dispatch($video->id, $upload->id);

            return $video;
        });
    }

    public function cancelar(VideoUpload $upload): void
    {
        $this->comTrava("video-upload:{$upload->id}", function () use ($upload) {
            $upload->refresh();

            if ($upload->status !== VideoUpload::ABERTO) {
                throw new \DomainException('Apenas um upload aberto pode ser cancelado.');
            }

            ConexaoTreinamento::transacao(function () use ($upload) {
                $this->encerrar($upload);

                $video = $upload->video;
                $video->update([
                    'status' => $video->hls_versao !== null ? VideoStatus::PRONTO : VideoStatus::SEM_VIDEO,
                ]);
            });
        });
    }

    /** Apaga as partes e marca o upload como cancelado. */
    private function encerrar(VideoUpload $upload): void
    {
        Storage::disk('treinamentos')->deleteDirectory(CaminhosVideo::partes($upload->id));
        $upload->update(['status' => VideoUpload::CANCELADO]);
    }

    private function tamanhoEsperado(VideoUpload $upload, int $n): int
    {
        if ($n < $upload->total_partes) {
            return $upload->tamanho_parte;
        }

        return $upload->tamanho_total - $upload->tamanho_parte * ($upload->total_partes - 1);
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $acao
     * @return T
     */
    private function comTrava(string $chave, Closure $acao): mixed
    {
        $trava = Cache::lock($chave, self::TRAVA_TTL);

        try {
            $trava->block(self::TRAVA_ESPERA);
        } catch (LockTimeoutException) {
            throw new \DomainException('Outra operacao deste video esta em andamento; tente novamente.');
        }

        try {
            return $acao();
        } finally {
            $trava->release();
        }
    }
}
