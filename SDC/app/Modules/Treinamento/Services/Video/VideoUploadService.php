<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Video;

use App\Modules\Treinamento\Enums\VideoStatus;
use App\Modules\Treinamento\Exceptions\OperacaoDeVideoInvalida;
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

    public function __construct(private readonly AuditoriaDeVideo $auditoria) {}

    public function abrir(Video $video, int $userId, string $nome, int $tamanhoTotal, string $mime): VideoUpload
    {
        if ($tamanhoTotal > (int) config('treinamento.video.tamanho_maximo_bytes')) {
            throw new OperacaoDeVideoInvalida('O arquivo excede o tamanho maximo permitido.');
        }
        if (! in_array($mime, config('treinamento.video.mimes'), true)) {
            throw new OperacaoDeVideoInvalida('Formato de video nao aceito.');
        }

        return $this->comTrava("video-upload-abrir:{$video->id}", function () use ($video, $userId, $nome, $tamanhoTotal, $mime) {
            $video->refresh();

            if ($video->status->emProcessamento()) {
                throw new OperacaoDeVideoInvalida('O video esta em processamento; aguarde antes de enviar outro arquivo.');
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

                // Status e so o estado do processamento; a versao publicada (hls_versao)
                // continua sendo servida ate a troca atomica.
                $video->update(['status' => VideoStatus::UPLOAD_ABERTO]);

                return $upload;
            });
        });
    }

    public function receberParte(VideoUpload $upload, int $n, UploadedFile $parte): VideoUpload
    {
        return $this->comTrava("video-upload:{$upload->id}", function () use ($upload, $n, $parte) {
            $upload->refresh();

            if (! $upload->estaAberto()) {
                throw new OperacaoDeVideoInvalida('Este upload nao esta aberto.');
            }
            if ($n < 1 || $n > $upload->total_partes) {
                throw new OperacaoDeVideoInvalida('Numero de parte fora da faixa.');
            }
            if ($parte->getSize() !== $this->tamanhoEsperado($upload, $n)) {
                throw new OperacaoDeVideoInvalida('Tamanho da parte diferente do esperado.');
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

            // Segunda chamada (ou outra replica). Se o video ainda esta ENVIADO e este
            // e o upload vigente, o despacho anterior pode ter se perdido: repete (o job
            // e unico por video, entao um job ja na fila descarta o duplicado). Um upload
            // antigo, superado por outro mais novo, nunca e redespachado.
            if ($upload->status === VideoUpload::COMPLETO) {
                if ($video->status === VideoStatus::ENVIADO && $this->ehUploadCompletoVigente($upload)) {
                    ProcessarVideoJob::dispatch($video->id, $upload->id);
                }

                return $video;
            }

            // Ordem de aquisicao unica: trava do upload e depois a do video (abrir so
            // usa a do video). A transicao e revalidada sob a trava do video para nao
            // completar um upload que abrir esta cancelando.
            $video = $this->comTrava("video-upload-abrir:{$upload->video_id}", fn () => $this->completar($upload));

            // Auditoria antes do despacho: se a fila falhar, a transicao ja esta
            // auditada e a retentativa (ramo COMPLETO) apenas redespacha.
            $this->auditoria->nosCursosQueUsam($video, 'video enviado para processamento');
            ProcessarVideoJob::dispatch($video->id, $upload->id);

            return $video;
        });
    }

    public function cancelar(VideoUpload $upload): void
    {
        $this->comTrava("video-upload:{$upload->id}", function () use ($upload) {
            $upload->refresh();

            if ($upload->status !== VideoUpload::ABERTO) {
                throw new OperacaoDeVideoInvalida('Apenas um upload aberto pode ser cancelado.');
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

    /** Transicao ABERTO -> COMPLETO do upload e ENVIADO do video, sob a trava do video. */
    private function completar(VideoUpload $upload): Video
    {
        $upload->refresh();
        $video = $upload->video;

        if (! $upload->estaAberto()) {
            throw new OperacaoDeVideoInvalida('Este upload nao esta aberto (foi cancelado ou substituido por um novo envio).');
        }
        if (! $upload->completo()) {
            throw new OperacaoDeVideoInvalida('Ainda faltam partes para concluir o envio.');
        }

        ConexaoTreinamento::transacao(function () use ($upload, $video) {
            $upload->update(['status' => VideoUpload::COMPLETO]);

            // hls_versao intacta: o aluno segue na versao atual ate a troca.
            $video->update(['status' => VideoStatus::ENVIADO, 'erro' => null]);
        });

        return $video;
    }

    private function ehUploadCompletoVigente(VideoUpload $upload): bool
    {
        $vigente = VideoUpload::query()
            ->where('video_id', $upload->video_id)
            ->where('status', VideoUpload::COMPLETO)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('id');

        return $vigente === $upload->id;
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
            throw new OperacaoDeVideoInvalida('Outra operacao deste video esta em andamento; tente novamente.');
        }

        try {
            return $acao();
        } finally {
            $trava->release();
        }
    }
}
