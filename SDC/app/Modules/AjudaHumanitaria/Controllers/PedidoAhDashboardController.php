<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\StatusPrestacaoConta;
use App\Modules\AjudaHumanitaria\Models\ParametroAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAhTramite;
use App\Modules\AjudaHumanitaria\Models\PrestacaoConta;
use App\Modules\AjudaHumanitaria\Support\MunicipioDoUsuario;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Painel do modulo, no padrao do dashboard do TDAP.
 *
 * Existe para responder de relance o que exige acao: pedidos parados em
 * analise, prestacoes vencendo e o que aconteceu de mais recente. Nao e
 * relatorio: e fila de trabalho.
 */
class PedidoAhDashboardController extends Controller
{
    /** Um pedido parado alem disso, na mesma etapa, entra na fila de atencao. */
    private const DIAS_PARA_CONSIDERAR_PARADO = 7;

    /** Janela de aviso da prestacao antes do vencimento. */
    private const DIAS_DE_AVISO_PRESTACAO = 10;

    public function index(Request $request): Response
    {
        $municipioId = MunicipioDoUsuario::resolver($request->user());

        return Inertia::render('AjudaHumanitaria/Dashboard', [
            'estatisticas'      => $this->estatisticas($municipioId),
            'aguardandoAcao'    => $this->aguardandoAcao($municipioId),
            'prestacoesEmRisco' => $this->prestacoesEmRisco($municipioId),
            'ultimasTramitacoes' => $this->ultimasTramitacoes($municipioId),
            'canCreate'         => $request->user()?->can('humanitaria.pedidos.create') ?? false,
        ]);
    }

    /**
     * RN-24: quem tem municipio vinculado ve apenas o proprio; quem nao tem
     * opera em ambito estadual.
     */
    private function noEscopo(?int $municipioId): \Illuminate\Database\Eloquent\Builder
    {
        return PedidoAh::query()
            ->when($municipioId !== null, fn ($q) => $q->where('municipio_id', $municipioId));
    }

    /**
     * @return array<string, int>
     */
    private function estatisticas(?int $municipioId): array
    {
        $porStatus = $this->noEscopo($municipioId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $contar = static fn (array $statuses): int => array_sum(
            array_map(static fn (int $s): int => (int) ($porStatus[$s] ?? 0), $statuses)
        );

        return [
            'total'      => array_sum($porStatus),
            'em_edicao'  => $contar([StatusPedidoAh::EdicaoCompdec->value]),
            'em_analise' => $contar([
                StatusPedidoAh::AnaliseDlog->value,
                StatusPedidoAh::AnaliseDiretorDlog->value,
            ]),
            'aguardando_retirada' => $contar([
                StatusPedidoAh::Aprovado->value,
                StatusPedidoAh::AguardandoDisponibilidade->value,
                StatusPedidoAh::AguardandoRetirada->value,
            ]),
            'em_prestacao' => $contar([StatusPedidoAh::Atendido->value]),
            'finalizados'  => $contar([StatusPedidoAh::Finalizado->value]),
        ];
    }

    /**
     * Pedidos parados na mesma etapa ha mais de uma semana.
     *
     * O criterio e a data do ultimo tramite: e o momento em que o processo
     * chegou na etapa atual, e nao a data de abertura.
     *
     * @return array<int, array<string, mixed>>
     */
    private function aguardandoAcao(?int $municipioId): array
    {
        $limite = now()->subDays(self::DIAS_PARA_CONSIDERAR_PARADO);

        return $this->noEscopo($municipioId)
            ->with('municipio:id,nome,uf')
            ->whereIn('status', [
                StatusPedidoAh::AnaliseDlog->value,
                StatusPedidoAh::AnaliseDiretorDlog->value,
                StatusPedidoAh::AguardandoDisponibilidade->value,
                StatusPedidoAh::AguardandoRetirada->value,
            ])
            ->withMax('tramites as ultimo_tramite_em', 'created_at')
            ->orderBy('data_entrada_sistema')
            ->limit(8)
            ->get()
            ->map(function (PedidoAh $pedido) use ($limite): array {
                $desde = $pedido->ultimo_tramite_em !== null
                    ? \Illuminate\Support\Carbon::parse($pedido->ultimo_tramite_em)
                    : $pedido->data_entrada_sistema;

                return [
                    'id'            => $pedido->id,
                    'identificador' => $pedido->identificador,
                    'municipio'     => $pedido->municipio?->nome,
                    'status_label'  => $pedido->status?->label(),
                    'status_cor'    => $pedido->status?->cor(),
                    'parado_desde'  => $desde?->toDateString(),
                    'dias_parado'   => $desde !== null ? (int) $desde->diffInDays(now()) : null,
                    'atrasado'      => $desde !== null && $desde->lessThan($limite),
                ];
            })
            ->all();
    }

    /**
     * RN-16: prestacoes vencidas ou proximas do prazo.
     *
     * E o aviso mais acionavel do painel: passado o prazo, o municipio fica
     * inadimplente com a prestacao.
     *
     * @return array<int, array<string, mixed>>
     */
    private function prestacoesEmRisco(?int $municipioId): array
    {
        $aviso = now()->addDays(self::DIAS_DE_AVISO_PRESTACAO)->toDateString();

        return PrestacaoConta::query()
            ->with(['pedido:id,numero,ano,municipio_id', 'pedido.municipio:id,nome'])
            ->whereIn('status', [
                StatusPrestacaoConta::Pendente->value,
                StatusPrestacaoConta::EmLancamento->value,
            ])
            ->whereNotNull('data_limite')
            ->whereDate('data_limite', '<=', $aviso)
            ->when($municipioId !== null, fn ($q) => $q->whereHas(
                'pedido',
                fn ($p) => $p->where('municipio_id', $municipioId)
            ))
            ->orderBy('data_limite')
            ->limit(8)
            ->get()
            ->map(static fn (PrestacaoConta $prestacao): array => [
                'id'            => $prestacao->id,
                'pedido_id'     => $prestacao->pedido_ah_id,
                'identificador' => $prestacao->pedido
                    ? "{$prestacao->pedido->numero}/{$prestacao->pedido->ano}"
                    : null,
                'municipio'   => $prestacao->pedido?->municipio?->nome,
                'data_limite' => $prestacao->data_limite?->toDateString(),
                'vencida'     => $prestacao->data_limite?->isPast() ?? false,
                'status'      => $prestacao->status?->label(),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function ultimasTramitacoes(?int $municipioId): array
    {
        return PedidoAhTramite::query()
            ->with(['pedido:id,numero,ano,municipio_id', 'pedido.municipio:id,nome', 'autor:id,name'])
            ->when($municipioId !== null, fn ($q) => $q->whereHas(
                'pedido',
                fn ($p) => $p->where('municipio_id', $municipioId)
            ))
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(static fn (PedidoAhTramite $tramite): array => [
                'id'            => $tramite->id,
                'pedido_id'     => $tramite->pedido_ah_id,
                'identificador' => $tramite->pedido
                    ? "{$tramite->pedido->numero}/{$tramite->pedido->ano}"
                    : null,
                'municipio' => $tramite->pedido?->municipio?->nome,
                'de'        => $tramite->status_anterior?->label(),
                'para'      => $tramite->status_novo?->label(),
                'para_cor'  => $tramite->status_novo?->cor(),
                'autor'     => $tramite->autor?->name,
                'quando'    => $tramite->created_at?->toIso8601String(),
            ])
            ->all();
    }
}
