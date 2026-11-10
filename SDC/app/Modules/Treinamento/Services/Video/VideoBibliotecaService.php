<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Video;

use App\Models\User;
use App\Modules\Treinamento\Enums\TipoItem;
use App\Modules\Treinamento\Enums\VideoStatus;
use App\Modules\Treinamento\Exceptions\OperacaoDeVideoInvalida;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use Illuminate\Pagination\LengthAwarePaginator;

class VideoBibliotecaService
{
    private const POR_PAGINA = 20;

    public function __construct(private readonly AuditoriaDeVideo $auditoria) {}

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function listar(?string $q, bool $somenteProntos): LengthAwarePaginator
    {
        $pagina = Video::query()
            ->withExists('itens as em_uso')
            ->when($q !== null && $q !== '', fn ($query) => $query->where('titulo', 'ilike', '%'.addcslashes($q, '%_\\').'%'))
            ->when($somenteProntos, fn ($query) => $query->whereNotNull('hls_versao'))
            ->orderByDesc('id')
            ->paginate(self::POR_PAGINA);

        $donos = User::query()->whereIn('id', $pagina->pluck('user_id')->unique())->pluck('name', 'id');

        return $pagina->through(fn (Video $video) => $this->resumo($video, $donos[$video->user_id] ?? null));
    }

    /**
     * @return array<string, mixed>
     */
    public function resumo(Video $video, ?string $dono = null): array
    {
        return [
            'id' => $video->id,
            'titulo' => $video->titulo,
            'status' => $video->status->value,
            'status_label' => $video->status->getLabel(),
            'duracao_segundos' => $video->duracao_segundos,
            'dono' => $dono ?? User::query()->whereKey($video->user_id)->value('name'),
            'em_uso' => (bool) ($video->em_uso ?? $video->emUso()),
        ];
    }

    public function criar(int $userId, string $titulo): Video
    {
        return Video::query()->create([
            'titulo' => $titulo,
            'user_id' => $userId,
            'status' => VideoStatus::SEM_VIDEO,
        ]);
    }

    public function renomear(Video $video, string $titulo): Video
    {
        $video->update(['titulo' => $titulo]);

        return $video;
    }

    public function vincular(ModuloItem $item, Video $video): ModuloItem
    {
        if ($item->tipo !== TipoItem::VIDEO) {
            throw new OperacaoDeVideoInvalida('Apenas itens do tipo video recebem um video.');
        }

        // Linha travada: um vinculo e uma exclusao do mesmo video nao se intercalam.
        ConexaoTreinamento::transacao(function () use ($item, $video) {
            $atual = Video::withTrashed()->lockForUpdate()->find($video->id);

            if ($atual === null || $atual->trashed()) {
                throw new OperacaoDeVideoInvalida('O video foi excluido da biblioteca.');
            }
            if (! $atual->temVersaoPublicada()) {
                throw new OperacaoDeVideoInvalida('O video ainda nao esta pronto para uso.');
            }

            $item->update(['video_id' => $atual->id]);
        });

        $this->auditoria->noCursoDoItem($item, "video \"{$video->titulo}\" vinculado ao item");

        return $item;
    }

    /** O video continua na biblioteca. */
    public function desvincular(ModuloItem $item): ModuloItem
    {
        $item->update(['video_id' => null]);
        $this->auditoria->noCursoDoItem($item, 'video desvinculado do item');

        return $item;
    }

    public function excluir(Video $video): void
    {
        ConexaoTreinamento::transacao(function () use ($video) {
            $atual = Video::query()->lockForUpdate()->findOrFail($video->id);
            $usos = $atual->itens()->count();

            if ($usos > 0) {
                throw new OperacaoDeVideoInvalida("Video em uso por {$usos} itens");
            }

            // A remocao de HLS e original entra com o processamento.
            $atual->delete();
        });
    }
}
