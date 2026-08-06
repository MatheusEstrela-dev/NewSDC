<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Domain\Repositories\PedidoAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\DTOs\ParecerDTO;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAhParecer;
use Illuminate\Database\Eloquent\Collection;

/**
 * Parecer tecnico (RN-10).
 *
 * O parecer favoravel e o que habilita o avanco da analise DLOG para o
 * Diretor (RN-11), mas quem consulta esse fato na hora da transicao e o
 * TramitacaoService, pelo repositorio.
 */
final class ParecerService
{
    public function __construct(
        private readonly PedidoAhRepositoryInterface $pedidos,
    ) {}

    /**
     * @return array{0: ?PedidoAhParecer, 1: ?string}
     */
    public function emitir(int $pedidoId, ParecerDTO $dto, ?int $usuarioId): array
    {
        if (trim($dto->parecer) === '') {
            return [null, 'O texto do parecer é obrigatório.'];
        }

        $pedido = PedidoAh::findOrFail($pedidoId);

        if ($pedido->status === StatusPedidoAh::EdicaoCompdec) {
            return [null, 'O pedido ainda está em edição pelo município e não pode receber parecer.'];
        }

        if ($pedido->status->ehTerminal()) {
            return [null, 'O processo já foi encerrado e não pode receber parecer.'];
        }

        $parecer = PedidoAhParecer::create($dto->toArray() + [
            'pedido_ah_id' => $pedidoId,
            'user_id'      => $usuarioId,
        ]);

        return [$parecer, null];
    }

    public function remover(int $parecerId): bool
    {
        return (bool) PedidoAhParecer::findOrFail($parecerId)->delete();
    }

    /**
     * @return Collection<int, PedidoAhParecer>
     */
    public function doPedido(int $pedidoId): Collection
    {
        return PedidoAhParecer::query()
            ->with('autor:id,name')
            ->where('pedido_ah_id', $pedidoId)
            ->orderByDesc('data_parecer')
            ->get();
    }

    /** RN-11. */
    public function temFavoravel(int $pedidoId): bool
    {
        return $this->pedidos->temParecerFavoravel($pedidoId);
    }
}
