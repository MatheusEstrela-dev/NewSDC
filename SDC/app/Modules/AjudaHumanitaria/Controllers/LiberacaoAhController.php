<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Enums\StatusLiberacao;
use App\Modules\AjudaHumanitaria\Models\DepositoAh;
use App\Modules\AjudaHumanitaria\Models\LiberacaoAh;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Consulta das liberacoes de material migradas do legado.
 *
 * Somente leitura. Nao existe fluxo de criacao de liberacao no sistema novo:
 * as 3.582 linhas sao historico do gestaocedec, e criar aqui geraria registro
 * sem lastro no ledger de estoque.
 */
class LiberacaoAhController extends Controller
{
    private const POR_PAGINA = 25;

    /**
     * Colunas que a listagem pode ordenar, mapeadas para a coluna real.
     *
     * Whitelist obrigatoria: sort vem da URL e iria direto para o ORDER BY.
     * Municipio e deposito ficam de fora porque vivem em relacao, e ordenar por
     * eles exigiria join na consulta que tambem serve o CSV.
     */
    private const ORDENACAO_PERMITIDA = [
        'codigo'       => 'codigo_legado',
        'beneficiario' => 'beneficiario',
        'data_libera'  => 'data_libera',
        'situacao'     => 'status',
    ];

    public function index(Request $request): Response
    {
        $filtros = $this->filtrosDaRequisicao($request);
        $pagina  = $this->consulta($filtros)->paginate(self::POR_PAGINA)->withQueryString();

        return Inertia::render('AjudaHumanitaria/Liberacoes/Index', [
            'liberacoes' => [
                'data' => collect($pagina->items())
                    ->map(fn (LiberacaoAh $l): array => $this->paraLinha($l))
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
            'depositos'    => $this->depositosComLiberacao(),
            'opcoesStatus' => StatusLiberacao::options(),
            'filtros'      => $filtros,
            'ordenacao'    => ['sort' => $filtros['sort'] ?? 'data_libera', 'direction' => $filtros['direction']],
        ]);
    }

    public function show(int $id): Response
    {
        $liberacao = LiberacaoAh::query()
            ->with([
                'municipio:id,nome,uf',
                'deposito:id,nome,abreviacao',
                'solicitante:id,name',
                'recibos',
                'itens.material:id,nome,unidade_medida',
            ])
            ->findOrFail($id);

        // array_merge, e nao o operador +: com + a chave existente vence, e
        // 'recibos' continuaria sendo a contagem que paraLinha() devolve para a
        // listagem, em vez da lista que o detalhe precisa. O sintoma era mudo:
        // a tela exibia "Nenhum recibo" mesmo quando havia um.
        return Inertia::render('AjudaHumanitaria/Liberacoes/Show', [
            'liberacao' => array_merge($this->paraLinha($liberacao), [
                'observacao'          => $liberacao->observacao,
                'motivo_cancelamento' => $liberacao->motivo_cancelamento,
                'cancelado_em'        => $liberacao->cancelado_em?->toIso8601String(),
                'solicitante'         => $liberacao->solicitante?->name,
                'payload_legado'      => $liberacao->payload_legado,
                'itens'               => $liberacao->itens->map(static fn ($item): array => [
                    'id'       => $item->id,
                    'material' => $item->material?->nome,
                    'unidade'  => $item->material?->unidade_medida,
                    'qtd'      => (float) $item->qtd,
                ])->all(),
                'recibos' => $liberacao->recibos->map(static fn ($r): array => [
                    'id'          => $r->id,
                    'pago_em'     => $r->pago_em?->toDateString(),
                    'n_documento' => $r->n_documento,
                    'n_recibo'    => $r->n_recibo,
                    'responsavel' => $r->responsavel_recebimento,
                    'cpf'         => $r->cpf_responsavel,
                    'placa'       => $r->placa_veiculo,
                    'motivo'      => $r->motivo,
                ])->all(),
            ]),
        ]);
    }

    /**
     * Exporta em CSV o mesmo recorte da tela.
     *
     * cursor() em vez de get(): sem filtro sao 3.582 linhas, e o conjunto
     * cresce a cada carga do legado.
     */
    public function export(Request $request): StreamedResponse
    {
        $consulta = $this->consulta($this->filtrosDaRequisicao($request));
        $nome     = 'liberacoes-ajuda-humanitaria-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($consulta): void {
            $saida = fopen('php://output', 'wb');

            fwrite($saida, "\xEF\xBB\xBF");
            fputcsv($saida, [
                'Codigo legado', 'Municipio', 'UF', 'Deposito', 'Beneficiario',
                'Data da liberacao', 'Prazo', 'Situacao', 'Recibos', 'Pago em',
            ], ';');

            foreach ($consulta->cursor() as $l) {
                $recibo = $l->recibos->first();

                fputcsv($saida, [
                    $l->codigo_legado,
                    $l->municipio?->nome,
                    $l->municipio?->uf,
                    $l->deposito?->nome,
                    $l->beneficiario,
                    $l->data_libera?->format('d/m/Y'),
                    $l->data_limite?->format('d/m/Y'),
                    $l->status?->label(),
                    $l->recibos->count(),
                    $recibo?->pago_em?->format('d/m/Y'),
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
            ? StatusLiberacao::tryFrom((int) $request->integer('status'))?->value
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

    /** Data fora do formato ISO e ignorada em vez de derrubar a consulta. */
    private function dataValida(string $data): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) === 1 ? $data : null;
    }

    /**
     * Consulta unica da listagem e do CSV, para que o arquivo nao divirja da
     * tela que o usuario acabou de ver.
     *
     * @param  array<string, mixed>  $filtros
     */
    private function consulta(array $filtros): Builder
    {
        return LiberacaoAh::query()
            ->with(['municipio:id,nome,uf', 'deposito:id,nome,abreviacao', 'recibos'])
            ->when($filtros['deposito_id'], fn ($q, $id) => $q->where('deposito_id', $id))
            ->when($filtros['status'] !== null, fn ($q) => $q->where('status', $filtros['status']))
            // A busca cobre beneficiario, codigo do legado e nome do municipio:
            // sao os tres caminhos por onde alguem procura uma liberacao.
            ->when($filtros['busca'], fn ($q, $busca) => $q->where(function ($sub) use ($busca): void {
                $sub->where('beneficiario', 'ilike', '%'.$busca.'%')
                    ->orWhere('codigo_legado', 'ilike', '%'.$busca.'%')
                    ->orWhereHas('municipio', fn ($m) => $m->where('nome', 'ilike', '%'.$busca.'%'));
            }))
            ->when($filtros['data_inicio'], fn ($q, $d) => $q->whereDate('data_libera', '>=', $d))
            ->when($filtros['data_fim'], fn ($q, $d) => $q->whereDate('data_libera', '<=', $d))
            // Desempate por id para que a paginacao seja estavel: sem ele,
            // linhas com a mesma data podem trocar de pagina entre requisicoes.
            ->orderBy(
                self::ORDENACAO_PERMITIDA[$filtros['sort'] ?? ''] ?? 'data_libera',
                $filtros['direction'],
            )
            ->orderByDesc('id');
    }

    /**
     * @return array<string, mixed>
     */
    private function paraLinha(LiberacaoAh $l): array
    {
        return [
            'id'            => $l->id,
            'codigo_legado' => $l->codigo_legado,
            'municipio'     => $l->municipio?->nome,
            'uf'            => $l->municipio?->uf,
            'deposito'      => $l->deposito?->nome,
            'sigla'         => $l->deposito?->abreviacao,
            'beneficiario'  => $l->beneficiario,
            'data_libera'   => $l->data_libera?->toDateString(),
            'data_limite'   => $l->data_limite?->toDateString(),
            'status'        => $l->status?->value,
            'status_label'  => $l->status?->label(),
            'status_cor'    => $l->status?->cor(),
            'recibos'       => $l->recibos->count(),
        ];
    }

    /**
     * Os cartoes acompanham deposito, busca e periodo, mas nao o proprio
     * status: filtrar por status zeraria os demais cartoes e o usuario
     * perderia a comparacao que o levou a clicar.
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
            ->when($status !== null, fn ($q) => $q->where('ajuda_h_liberacoes.status', $status))
            ->count();

        return [
            'total'     => $contar(null),
            'pendentes' => $contar(StatusLiberacao::Pendente->value),
            'pagas'     => $contar(StatusLiberacao::Paga->value),
            'canceladas' => $contar(StatusLiberacao::Cancelada->value),
        ];
    }

    /**
     * Apenas depositos que aparecem em alguma liberacao. Oferecer os 24 no
     * filtro geraria consulta que volta vazia.
     *
     * @return array<int, array{id: int, nome: string, sigla: string}>
     */
    private function depositosComLiberacao(): array
    {
        return DepositoAh::query()
            ->whereHas('liberacoes')
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
