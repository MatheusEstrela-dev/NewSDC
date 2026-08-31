<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Enums\StatusTransferencia;
use App\Modules\AjudaHumanitaria\Models\DepositoAh;
use App\Modules\AjudaHumanitaria\Models\TransferenciaAh;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Consulta das transferencias de material entre depositos.
 *
 * Somente leitura, pelo mesmo motivo das liberacoes: transferencia movimenta
 * estoque, e criar uma aqui sem lancar no ledger produziria registro sem
 * lastro. Quando houver fluxo de escrita, ele passa por
 * RegistrarMovimentoEstoque, com um par TRANSF_SAIDA e TRANSF_ENTRADA.
 */
class TransferenciaAhController extends Controller
{
    private const POR_PAGINA = 25;

    /**
     * Colunas ordenaveis, mapeadas para a coluna real.
     *
     * Whitelist obrigatoria: sort vem da URL e iria direto para o ORDER BY.
     * Origem e destino ficam de fora porque vivem em relacao com depositos.
     */
    private const ORDENACAO_PERMITIDA = [
        'codigo'    => 'codigo_legado',
        'saida'     => 'saiu_em',
        'chegada'   => 'chegou_em',
        'motorista' => 'motorista',
        'situacao'  => 'status',
    ];

    public function index(Request $request): Response
    {
        $filtros = $this->filtrosDaRequisicao($request);
        $pagina  = $this->consulta($filtros)->paginate(self::POR_PAGINA)->withQueryString();

        return Inertia::render('AjudaHumanitaria/Transferencias/Index', [
            'transferencias' => [
                'data' => collect($pagina->items())
                    ->map(fn (TransferenciaAh $t): array => $this->paraLinha($t))
                    ->all(),
                'meta' => [
                    'current_page' => $pagina->currentPage(),
                    'last_page'    => $pagina->lastPage(),
                    'per_page'     => $pagina->perPage(),
                    'total'        => $pagina->total(),
                    'from'         => $pagina->firstItem(),
                    'to'           => $pagina->lastItem(),
                ],
            ],
            'estatisticas' => $this->estatisticas($filtros),
            'depositos'    => $this->depositosEnvolvidos(),
            'opcoesStatus' => StatusTransferencia::options(),
            'filtros'      => $filtros,
            'ordenacao'    => ['sort' => $filtros['sort'] ?? 'saida', 'direction' => $filtros['direction']],
        ]);
    }

    public function show(int $id): Response
    {
        $transferencia = TransferenciaAh::query()
            ->with([
                'origem:id,nome,abreviacao',
                'destino:id,nome,abreviacao',
                'itens.material:id,nome,unidade_medida',
            ])
            ->findOrFail($id);

        // array_merge, e nao o operador +: com + a chave existente vence, e
        // 'itens' continuaria sendo a contagem que paraLinha() devolve para a
        // listagem, em vez da lista que o detalhe precisa.
        return Inertia::render('AjudaHumanitaria/Transferencias/Show', [
            'transferencia' => array_merge($this->paraLinha($transferencia), [
                'observacao' => $transferencia->observacao,
                'itens'      => $transferencia->itens->map(static fn ($item): array => [
                    'id'       => $item->id,
                    'material' => $item->material?->nome,
                    'unidade'  => $item->material?->unidade_medida,
                    'qtd'      => (float) $item->qtd,
                ])->all(),
            ]),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $consulta = $this->consulta($this->filtrosDaRequisicao($request));
        $nome     = 'transferencias-ajuda-humanitaria-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($consulta): void {
            $saida = fopen('php://output', 'wb');

            fwrite($saida, "\xEF\xBB\xBF");
            fputcsv($saida, [
                'Codigo legado', 'Origem', 'Destino', 'Saida', 'Chegada',
                'Situacao', 'Motorista', 'Veiculo', 'Placa', 'Responsavel', 'Itens',
            ], ';');

            foreach ($consulta->cursor() as $t) {
                fputcsv($saida, [
                    $t->codigo_legado,
                    $t->origem?->nome,
                    $t->destino?->nome,
                    $t->saiu_em?->format('d/m/Y H:i'),
                    $t->chegou_em?->format('d/m/Y H:i'),
                    $t->status?->label(),
                    $t->motorista,
                    $t->veiculo,
                    $t->placa,
                    $t->responsavel,
                    $t->itens_count ?? $t->itens->count(),
                ], ';');
            }

            fclose($saida);
        }, $nome, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array<string, mixed>
     */
    private function filtrosDaRequisicao(Request $request): array
    {
        $status = $request->has('status') && $request->string('status')->toString() !== ''
            ? StatusTransferencia::tryFrom((int) $request->integer('status'))?->value
            : null;

        return [
            'deposito_id' => $request->integer('deposito_id') ?: null,
            'busca'       => trim((string) $request->string('busca')) ?: null,
            'status'      => $status,
            'data_inicio' => $this->dataValida($request->string('data_inicio')->toString()),
            'data_fim'    => $this->dataValida($request->string('data_fim')->toString()),
            'sort'        => array_key_exists($request->string('sort')->toString(), self::ORDENACAO_PERMITIDA)
                ? $request->string('sort')->toString()
                : null,
            'direction'   => strtolower((string) $request->input('direction')) === 'asc' ? 'asc' : 'desc',
        ];
    }

    private function dataValida(string $data): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) === 1 ? $data : null;
    }

    /**
     * Consulta unica da listagem e do CSV.
     *
     * O filtro de deposito casa origem OU destino: quem procura por Barbacena
     * quer o que saiu de la e tambem o que chegou.
     *
     * @param  array<string, mixed>  $filtros
     */
    private function consulta(array $filtros): Builder
    {
        return TransferenciaAh::query()
            ->with(['origem:id,nome,abreviacao', 'destino:id,nome,abreviacao'])
            ->withCount('itens')
            ->when($filtros['deposito_id'], fn ($q, $id) => $q->where(
                fn ($sub) => $sub->where('deposito_origem_id', $id)->orWhere('deposito_destino_id', $id)
            ))
            ->when($filtros['status'] !== null, fn ($q) => $q->where('status', $filtros['status']))
            ->when($filtros['busca'], fn ($q, $busca) => $q->where(function ($sub) use ($busca): void {
                $sub->where('codigo_legado', 'ilike', '%'.$busca.'%')
                    ->orWhere('motorista', 'ilike', '%'.$busca.'%')
                    ->orWhere('placa', 'ilike', '%'.$busca.'%')
                    ->orWhere('responsavel', 'ilike', '%'.$busca.'%');
            }))
            ->when($filtros['data_inicio'], fn ($q, $d) => $q->whereDate('saiu_em', '>=', $d))
            ->when($filtros['data_fim'], fn ($q, $d) => $q->whereDate('saiu_em', '<=', $d))
            // Desempate por id para que a paginacao seja estavel.
            ->orderBy(
                self::ORDENACAO_PERMITIDA[$filtros['sort'] ?? ''] ?? 'saiu_em',
                $filtros['direction'],
            )
            ->orderByDesc('id');
    }

    /**
     * @return array<string, mixed>
     */
    private function paraLinha(TransferenciaAh $t): array
    {
        return [
            'id'             => $t->id,
            'codigo_legado'  => $t->codigo_legado,
            'origem'         => $t->origem?->nome,
            'origem_sigla'   => $t->origem?->abreviacao,
            'destino'        => $t->destino?->nome,
            'destino_sigla'  => $t->destino?->abreviacao,
            'saiu_em'        => $t->saiu_em?->toIso8601String(),
            'chegou_em'      => $t->chegou_em?->toIso8601String(),
            'status'         => $t->status?->value,
            'status_label'   => $t->status?->label(),
            'status_cor'     => $t->status?->cor(),
            'motorista'      => $t->motorista,
            'veiculo'        => $t->veiculo,
            'placa'          => $t->placa,
            'responsavel'    => $t->responsavel,
            'itens'          => $t->itens_count ?? $t->itens->count(),
        ];
    }

    /**
     * Cartoes contam sem aplicar o proprio status, para nao zerar a comparacao
     * quando o usuario clica em um deles.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, int>
     */
    private function estatisticas(array $filtros): array
    {
        $semStatus = $filtros;
        $semStatus['status'] = null;

        $base = $this->consulta($semStatus)->getQuery();

        $contar = fn (?int $status): int => (int) (clone $base)
            ->when($status !== null, fn ($q) => $q->where('ajuda_h_transferencias.status', $status))
            ->count();

        return [
            'total'      => $contar(null),
            'em_transito' => $contar(StatusTransferencia::EmTransito->value),
            'concluidas' => $contar(StatusTransferencia::Concluida->value),
            'canceladas' => $contar(StatusTransferencia::Cancelada->value),
        ];
    }

    /**
     * Depositos que aparecem como origem ou destino de alguma transferencia.
     *
     * @return array<int, array{id: int, nome: string, sigla: string}>
     */
    private function depositosEnvolvidos(): array
    {
        return DepositoAh::query()
            ->where(fn ($q) => $q->whereHas('transferenciasDeSaida')->orWhereHas('transferenciasDeEntrada'))
            ->orderBy('nome')
            ->get(['id', 'nome', 'abreviacao'])
            ->map(static fn (DepositoAh $d): array => [
                'id'    => $d->id,
                'nome'  => $d->nome,
                'sigla' => $d->abreviacao,
            ])
            ->all();
    }
}
