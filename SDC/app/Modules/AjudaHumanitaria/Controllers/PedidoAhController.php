<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Resources\PedidoAhIndexResource;
use App\Modules\AjudaHumanitaria\Services\PedidoAhService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pedido de Material de Ajuda Humanitaria.
 *
 * Controller fino: nao contem regra de negocio. Toda decisao vive no dominio e
 * nos servicos; aqui so ha traducao entre requisicao e resposta.
 */
class PedidoAhController extends Controller
{
    public function __construct(
        private readonly PedidoAhService $pedidos,
    ) {}

    public function index(Request $request): Response
    {
        $filtros = $request->only(['municipio_id', 'status', 'ano', 'cobrade_id', 'search']);
        $perPage = (int) $request->integer('per_page', 15);

        $pedidos = $this->pedidos->listar($perPage, $filtros);

        return Inertia::render('AjudaHumanitaria/Pedidos/Index', [
            'pedidos'       => PedidoAhIndexResource::collection($pedidos),
            'estatisticas'  => fn (): array => $this->estatisticas(),
            'filtros'       => $filtros,
            'opcoesStatus'  => StatusPedidoAh::options(),
            'canCreate'     => $request->user()?->can('humanitaria.pedidos.create') ?? false,
            'canEdit'       => $request->user()?->can('humanitaria.pedidos.edit') ?? false,
            'canDelete'     => $request->user()?->can('humanitaria.pedidos.delete') ?? false,
            'canExport'     => $request->user()?->can('humanitaria.pedidos.export') ?? false,
        ]);
    }

    /**
     * Totais por fase do processo, para os cartoes do topo.
     *
     * @return array<string, int>
     */
    private function estatisticas(): array
    {
        $porStatus = \App\Modules\AjudaHumanitaria\Models\PedidoAh::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $contar = static fn (array $statuses): int => array_sum(
            array_map(static fn (int $s): int => (int) ($porStatus[$s] ?? 0), $statuses)
        );

        return [
            'total' => array_sum($porStatus),
            'em_edicao' => $contar([StatusPedidoAh::EdicaoCompdec->value]),
            'em_analise' => $contar([
                StatusPedidoAh::AnaliseDlog->value,
                StatusPedidoAh::AnaliseDiretorDlog->value,
            ]),
            'em_atendimento' => $contar([
                StatusPedidoAh::Aprovado->value,
                StatusPedidoAh::AguardandoDisponibilidade->value,
                StatusPedidoAh::AguardandoRetirada->value,
            ]),
            'atendidos' => $contar([StatusPedidoAh::Atendido->value]),
            'finalizados' => $contar([StatusPedidoAh::Finalizado->value]),
            'encerrados_sem_atendimento' => $contar([
                StatusPedidoAh::Cancelado->value,
                StatusPedidoAh::Reprovado->value,
            ]),
        ];
    }
}
