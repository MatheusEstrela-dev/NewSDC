<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Domain\Repositories\PedidoAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\Domain\Specifications\MunicipioPodeAbrirPedido;
use App\Modules\AjudaHumanitaria\DTOs\PedidoAhDTO;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Ciclo de vida do pedido fora da tramitacao: abertura, edicao, remocao e
 * listagem.
 *
 * Nao altera status. Toda mudanca de status passa pelo TramitacaoService.
 */
final class PedidoAhService
{
    /** Tentativas de gravacao sob colisao de numero (RN-01). */
    private const TENTATIVAS_NUMERACAO = 3;

    public function __construct(
        private readonly PedidoAhRepositoryInterface $pedidos,
        private readonly NumeracaoPedidoService $numeracao,
        private readonly MunicipioPodeAbrirPedido $podeAbrir,
    ) {}

    /**
     * RN-02 e RN-03.
     *
     * @return array{0: ?PedidoAh, 1: ?string}
     */
    public function abrir(PedidoAhDTO $dto, ?int $usuarioId): array
    {
        $resultado = $this->podeAbrir->verificar(
            $this->pedidos->municipioTemPedidoEmEdicao($dto->municipioId)
        );

        if (! $resultado->permitido) {
            return [null, $resultado->motivo];
        }

        $ano = $this->numeracao->anoCorrente();

        for ($tentativa = 1; $tentativa <= self::TENTATIVAS_NUMERACAO; $tentativa++) {
            try {
                $pedido = PedidoAh::create($dto->toArray() + [
                    'numero'               => $this->numeracao->proximoNumero($ano),
                    'ano'                  => $ano,
                    'status'               => StatusPedidoAh::EdicaoCompdec,
                    'data_entrada_sistema' => now(),
                    'created_by'           => $usuarioId,
                ]);

                return [$pedido, null];
            } catch (UniqueConstraintViolationException $colisao) {
                if ($tentativa === self::TENTATIVAS_NUMERACAO) {
                    return [null, 'Nao foi possível gerar o número do pedido. Tente novamente.'];
                }
            }
        }

        return [null, 'Nao foi possível abrir o pedido.'];
    }

    /**
     * Edicao so e permitida enquanto o pedido esta com o municipio.
     *
     * @return array{0: ?PedidoAh, 1: ?string}
     */
    public function atualizar(int $pedidoId, PedidoAhDTO $dto): array
    {
        $pedido = $this->obter($pedidoId);

        if ($pedido->status !== StatusPedidoAh::EdicaoCompdec) {
            return [null, 'O pedido já foi enviado para análise e não pode mais ser editado.'];
        }

        $pedido->update($dto->toArray());

        return [$pedido->fresh(), null];
    }

    /**
     * @return array{0: bool, 1: ?string}
     */
    public function remover(int $pedidoId): array
    {
        $pedido = $this->obter($pedidoId);

        if ($pedido->status !== StatusPedidoAh::EdicaoCompdec) {
            return [false, 'Somente pedido em edição pode ser excluído. Use o cancelamento.'];
        }

        return [(bool) $pedido->delete(), null];
    }

    public function obter(int $pedidoId): PedidoAh
    {
        return PedidoAh::with(['municipio', 'itens'])->findOrFail($pedidoId);
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        return PedidoAh::query()
            ->with(['municipio:id,nome,uf'])
            ->when($filtros['municipio_id'] ?? null, fn ($q, $id) => $q->where('municipio_id', (int) $id))
            ->when(isset($filtros['status']) && $filtros['status'] !== '', fn ($q) => $q->where('status', (int) $filtros['status']))
            ->when($filtros['ano'] ?? null, fn ($q, $ano) => $q->where('ano', (int) $ano))
            ->when($filtros['cobrade_id'] ?? null, fn ($q, $id) => $q->where('cobrade_id', (int) $id))
            ->when($filtros['search'] ?? null, function ($q, $termo) {
                $q->where(function ($sub) use ($termo) {
                    $sub->where('numero', 'like', "%{$termo}%")
                        ->orWhere('numero_decreto', 'like', "%{$termo}%")
                        ->orWhereHas('municipio', fn ($m) => $m->where('nome', 'like', "%{$termo}%"));
                });
            })
            ->orderByDesc('ano')
            ->orderByDesc('numero')
            ->paginate($perPage)
            ->withQueryString();
    }
}
