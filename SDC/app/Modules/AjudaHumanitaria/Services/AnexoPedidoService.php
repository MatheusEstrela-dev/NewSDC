<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Anexos do pedido (RN-22): PDF de ate 2 MB.
 *
 * A validacao acontece aqui, e nao apenas no FormRequest, porque a regra e do
 * dominio do modulo e precisa valer tambem quando o anexo vier de importacao
 * ou de comando.
 */
final class AnexoPedidoService
{
    /**
     * @return array{0: ?Media, 1: ?string}
     */
    public function anexar(int $pedidoId, UploadedFile $arquivo): array
    {
        if ($arquivo->getClientMimeType() !== 'application/pdf'
            && strtolower((string) $arquivo->getClientOriginalExtension()) !== 'pdf') {
            return [null, 'Apenas arquivos PDF são aceitos.'];
        }

        $limite = (int) config('ajuda-humanitaria.upload_limits.anexo_pedido', 2 * 1024 * 1024);

        if ($arquivo->getSize() > $limite) {
            $limiteMb = round($limite / 1024 / 1024, 1);

            return [null, "Arquivo acima do limite de {$limiteMb} MB."];
        }

        $pedido = PedidoAh::findOrFail($pedidoId);

        $media = $pedido
            ->addMedia($arquivo->getRealPath())
            ->usingFileName($arquivo->hashName())
            ->usingName($arquivo->getClientOriginalName())
            ->toMediaCollection(PedidoAh::MEDIA_ANEXOS);

        return [$media, null];
    }

    public function remover(int $mediaId): bool
    {
        return (bool) Media::findOrFail($mediaId)->delete();
    }

    /**
     * @return array<int, array{id: int, nome: string, tamanho: int, url: string, criado_em: ?string}>
     */
    public function listar(int $pedidoId): array
    {
        return PedidoAh::findOrFail($pedidoId)
            ->getMedia(PedidoAh::MEDIA_ANEXOS)
            ->map(static fn (Media $media): array => [
                'id'        => (int) $media->id,
                'nome'      => (string) $media->name,
                'tamanho'   => (int) $media->size,
                'url'       => $media->getUrl(),
                'criado_em' => $media->created_at?->toIso8601String(),
            ])
            ->all();
    }
}
