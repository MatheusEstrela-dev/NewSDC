<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Models\DepositoAh;
use App\Modules\AjudaHumanitaria\Models\EntradaAh;
use App\Modules\AjudaHumanitaria\Models\FonteRecursoAh;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Consulta das entradas de material nos depositos.
 *
 * Somente leitura, como as demais telas do estoque migrado: registrar entrada
 * pelo sistema novo tem de lancar no ledger, e isso passa por
 * RegistrarMovimentoEstoque.
 */
class EntradaAhController extends Controller
{
    private const POR_PAGINA = 25;

    /** Recortes que os cartoes oferecem como filtro rapido. */
    private const SITUACOES = ['ativa', 'cancelada', 'correcao'];

    public function index(Request $request): Response
    {
        $filtros = $this->filtrosDaRequisicao($request);
        $pagina  = $this->consulta($filtros)->paginate(self::POR_PAGINA)->withQueryString();

        return Inertia::render('AjudaHumanitaria/Entradas/Index', [
            'entradas' => [
                'data' => collect($pagina->items())
                    ->map(fn (EntradaAh $e): array => $this->paraLinha($e))
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
            'depositos'    => $this->depositosComEntrada(),
            'fontes'       => $this->fontesUsadas(),
            'filtros'      => $filtros,
        ]);
    }

    public function show(int $id): Response
    {
        $entrada = EntradaAh::query()
            ->with([
                'deposito:id,nome,abreviacao',
                'fonteRecurso:id,nome',
                'fornecedor:id,nome,cpf_cnpj',
                'itens.material:id,nome,unidade_medida',
            ])
            ->findOrFail($id);

        // array_merge, e nao +: com + a chave 'itens' de paraLinha() venceria e
        // o detalhe receberia a contagem no lugar da lista.
        return Inertia::render('AjudaHumanitaria/Entradas/Show', [
            'entrada' => array_merge($this->paraLinha($entrada), [
                'observacao'  => $entrada->observacao,
                'fornecedor'  => $entrada->fornecedor?->nome,
                // O texto de origem do legado, preservado porque em 215 das 752
                // entradas ele nao casa com nenhuma fonte cadastrada.
                'origem_legado' => $entrada->payload_legado['origem'] ?? null,
                'itens'       => $entrada->itens->map(static fn ($item): array => [
                    'id'             => $item->id,
                    'material'       => $item->material?->nome,
                    'unidade'        => $item->material?->unidade_medida,
                    'qtd'            => (float) $item->qtd,
                    'valor_unitario' => $item->valor_unitario !== null ? (float) $item->valor_unitario : null,
                    'valor_total'    => $item->valor_total !== null ? (float) $item->valor_total : null,
                    'data_validade'  => $item->data_validade?->toDateString(),
                ])->all(),
            ]),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $consulta = $this->consulta($this->filtrosDaRequisicao($request));
        $nome     = 'entradas-ajuda-humanitaria-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($consulta): void {
            $saida = fopen('php://output', 'wb');

            fwrite($saida, "\xEF\xBB\xBF");
            fputcsv($saida, [
                'Codigo legado', 'Deposito', 'Recebido em', 'Nota fiscal',
                'Fonte de recurso', 'Situacao', 'Itens', 'Quantidade', 'Observacao',
            ], ';');

            foreach ($consulta->cursor() as $e) {
                fputcsv($saida, [
                    $e->codigo_legado,
                    $e->deposito?->nome,
                    $e->recebido_em?->format('d/m/Y'),
                    $e->nota_fiscal,
                    $e->fonteRecurso?->nome,
                    $e->cancelado ? 'Cancelada' : 'Ativa',
                    $e->itens_count ?? $e->itens->count(),
                    $e->itens_sum_qtd,
                    $e->observacao,
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
        $situacao = $request->string('situacao')->toString();

        return [
            'deposito_id' => $request->integer('deposito_id') ?: null,
            'fonte_id'    => $request->integer('fonte_id') ?: null,
            'busca'       => trim((string) $request->string('busca')) ?: null,
            'situacao'    => in_array($situacao, self::SITUACOES, true) ? $situacao : null,
            'data_inicio' => $this->dataValida($request->string('data_inicio')->toString()),
            'data_fim'    => $this->dataValida($request->string('data_fim')->toString()),
        ];
    }

    private function dataValida(string $data): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) === 1 ? $data : null;
    }

    /**
     * Consulta unica da listagem e do CSV.
     *
     * @param  array<string, mixed>  $filtros
     */
    private function consulta(array $filtros): Builder
    {
        return EntradaAh::query()
            ->with(['deposito:id,nome,abreviacao', 'fonteRecurso:id,nome'])
            ->withCount('itens')
            ->withSum('itens as itens_sum_qtd', 'qtd')
            ->when($filtros['deposito_id'], fn ($q, $id) => $q->where('deposito_id', $id))
            ->when($filtros['fonte_id'], fn ($q, $id) => $q->where('fonte_recurso_id', $id))
            ->when($filtros['busca'], fn ($q, $busca) => $q->where(function ($sub) use ($busca): void {
                $sub->where('codigo_legado', 'ilike', '%'.$busca.'%')
                    ->orWhere('nota_fiscal', 'ilike', '%'.$busca.'%')
                    ->orWhere('observacao', 'ilike', '%'.$busca.'%')
                    ->orWhereHas('itens.material', fn ($m) => $m->where('nome', 'ilike', '%'.$busca.'%'));
            }))
            ->when($filtros['situacao'], fn ($q, $situacao) => $this->aplicarSituacao($q, $situacao))
            ->when($filtros['data_inicio'], fn ($q, $d) => $q->whereDate('recebido_em', '>=', $d))
            ->when($filtros['data_fim'], fn ($q, $d) => $q->whereDate('recebido_em', '<=', $d))
            ->orderByDesc('recebido_em')
            ->orderByDesc('id');
    }

    /**
     * Correcao e entrada com item de quantidade negativa: o legado lancava a
     * baixa assim, em vez de criar um tipo proprio de movimento.
     */
    private function aplicarSituacao(mixed $consulta, string $situacao): mixed
    {
        return match ($situacao) {
            'ativa'     => $consulta->where('cancelado', false),
            'cancelada' => $consulta->where('cancelado', true),
            'correcao'  => $consulta->whereHas('itens', fn ($i) => $i->where('qtd', '<', 0)),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function paraLinha(EntradaAh $e): array
    {
        return [
            'id'            => $e->id,
            'codigo_legado' => $e->codigo_legado,
            'deposito'      => $e->deposito?->nome,
            'sigla'         => $e->deposito?->abreviacao,
            'recebido_em'   => $e->recebido_em?->toDateString(),
            'nota_fiscal'   => $e->nota_fiscal,
            'fonte'         => $e->fonteRecurso?->nome,
            'cancelado'     => $e->cancelado,
            'itens'         => $e->itens_count ?? $e->itens->count(),
            'quantidade'    => (float) ($e->itens_sum_qtd ?? 0),
        ];
    }

    /**
     * Cartoes contam sem aplicar a propria situacao, para nao zerar a
     * comparacao quando o usuario clica em um deles.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, int|float>
     */
    private function estatisticas(array $filtros): array
    {
        $semSituacao = $filtros;
        $semSituacao['situacao'] = null;

        $contar = fn (?string $situacao): int => (int) $this->consulta($semSituacao)
            ->when($situacao, fn ($q, $s) => $this->aplicarSituacao($q, $s))
            ->toBase()
            ->getCountForPagination();

        return [
            'total'      => $contar(null),
            'ativas'     => $contar('ativa'),
            'canceladas' => $contar('cancelada'),
            'correcoes'  => $contar('correcao'),
        ];
    }

    /**
     * @return array<int, array{id: int, nome: string, sigla: string}>
     */
    private function depositosComEntrada(): array
    {
        return DepositoAh::query()
            ->whereHas('entradas')
            ->orderBy('nome')
            ->get(['id', 'nome', 'abreviacao'])
            ->map(static fn (DepositoAh $d): array => [
                'id'    => $d->id,
                'nome'  => $d->nome,
                'sigla' => $d->abreviacao,
            ])
            ->all();
    }

    /**
     * Somente fontes que aparecem em alguma entrada: das 36 cadastradas, boa
     * parte nunca foi usada, e oferece-las produziria consulta vazia.
     *
     * @return array<int, array{id: int, nome: string}>
     */
    private function fontesUsadas(): array
    {
        return FonteRecursoAh::query()
            ->whereHas('entradas')
            ->orderBy('nome')
            ->get(['id', 'nome'])
            ->map(static fn (FonteRecursoAh $f): array => ['id' => $f->id, 'nome' => $f->nome])
            ->all();
    }
}
