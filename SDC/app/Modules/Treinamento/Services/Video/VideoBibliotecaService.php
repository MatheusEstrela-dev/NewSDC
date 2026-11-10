<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Video;

use App\Models\User;
use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Services\RegistroDeAcao;
use App\Modules\Treinamento\Enums\TipoItem;
use App\Modules\Treinamento\Enums\VideoStatus;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Models\Video;
use Illuminate\Pagination\LengthAwarePaginator;

class VideoBibliotecaService
{
    private const POR_PAGINA = 20;

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function listar(?string $q, bool $somenteProntos): LengthAwarePaginator
    {
        $pagina = Video::query()
            ->withExists('itens as em_uso')
            ->when($q !== null && $q !== '', fn ($query) => $query->where('titulo', 'ilike', '%'.$q.'%'))
            ->when($somenteProntos, fn ($query) => $query->where('status', VideoStatus::PRONTO->value))
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
            throw new \DomainException('Apenas itens do tipo video recebem um video.');
        }
        if ($video->trashed()) {
            throw new \DomainException('O video foi excluido da biblioteca.');
        }
        if (! $video->estaPronto()) {
            throw new \DomainException('O video ainda nao esta pronto para uso.');
        }

        $item->update(['video_id' => $video->id]);
        $this->auditar($item, "video \"{$video->titulo}\" vinculado ao item");

        return $item;
    }

    /** O video continua na biblioteca. */
    public function desvincular(ModuloItem $item): ModuloItem
    {
        $item->update(['video_id' => null]);
        $this->auditar($item, 'video desvinculado do item');

        return $item;
    }

    public function excluir(Video $video): void
    {
        $usos = $video->itens()->count();

        if ($usos > 0) {
            throw new \DomainException("Video em uso por {$usos} itens");
        }

        // A remocao de HLS e original entra com o processamento.
        $video->delete();
    }

    private function auditar(ModuloItem $item, string $detalhe): void
    {
        $treinamentoId = Modulo::withTrashed()->whereKey($item->modulo_id)->value('treinamento_id');

        app(RegistroDeAcao::class)->registrarNoProtocolo(
            Treinamento::class,
            $treinamentoId,
            AcaoTrilha::Relacionado,
            $detalhe,
        );
    }
}
