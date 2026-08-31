<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Domain\Estoque\SaldoInsuficiente;
use App\Modules\AjudaHumanitaria\Models\DepositoAh;
use App\Modules\AjudaHumanitaria\Models\EntradaAh;
use App\Modules\AjudaHumanitaria\Models\FonteRecursoAh;
use App\Modules\AjudaHumanitaria\Models\FornecedorAh;
use App\Modules\AjudaHumanitaria\Models\MaterialAh;
use App\Modules\AjudaHumanitaria\Requests\StoreEntradaAhRequest;
use App\Modules\AjudaHumanitaria\Services\CancelarEntradaMaterial;
use App\Modules\AjudaHumanitaria\Services\RegistrarEntradaMaterial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Entradas de material nos depositos.
 *
 * Consulta do historico migrado, mais o primeiro caminho de escrita do estoque
 * pelo sistema novo. Registrar entrada nao e gravar linha: tem de lancar no
 * ledger junto, e por isso a operacao vive em RegistrarEntradaMaterial, nao
 * aqui.
 */
class EntradaAhController extends Controller
{
    private const POR_PAGINA = 25;

    /**
     * Colunas ordenaveis, mapeadas para a coluna real.
     *
     * Quantidade fica de fora: e soma dos itens, calculada por withSum, e
     * ordenar por ela exigiria subconsulta na mesma query que serve o CSV.
     */
    private const ORDENACAO_PERMITIDA = [
        'codigo'      => 'codigo_legado',
        'recebido'    => 'recebido_em',
        'nota_fiscal' => 'nota_fiscal',
    ];

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
            'ordenacao'    => ['sort' => $filtros['sort'] ?? 'recebido', 'direction' => $filtros['direction']],
            // Listas do formulario de nova entrada. Vao nulas para quem so
            // consulta: sao quatro consultas que nao servem a essa tela.
            'formulario'   => $request->user()?->can('humanitaria.estoque.movimentar')
                ? $this->opcoesDoFormulario()
                : null,
        ]);
    }

    /**
     * Registra o recebimento e lanca no ledger, em transacao unica.
     */
    public function store(StoreEntradaAhRequest $request, RegistrarEntradaMaterial $servico): RedirectResponse
    {
        $entrada = $servico->registrar($request->validated(), $request->user()?->id);

        return redirect()
            ->route('ajuda-humanitaria.entradas.show', $entrada->id)
            ->with('success', 'Entrada registrada e saldo atualizado.');
    }

    public function show(Request $request, int $id, CancelarEntradaMaterial $cancelamento): Response
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
            // A tela precisa distinguir "nao pode cancelar" de "nao tem
            // permissao": a primeira e regra de dominio e vale explicar.
            'podeCancelar' => $request->user()?->can('humanitaria.estoque.movimentar')
                && $cancelamento->podeCancelar($entrada),
        ]);
    }

    /**
     * Cancela a entrada com lancamento compensatorio no ledger.
     */
    public function cancelar(Request $request, int $id, CancelarEntradaMaterial $servico): RedirectResponse
    {
        $entrada = EntradaAh::findOrFail($id);
        $motivo  = trim((string) $request->string('motivo')) ?: null;

        try {
            $servico->cancelar($entrada, $request->user()?->id, $motivo);
        } catch (SaldoInsuficiente) {
            // Mensagem de dominio, e nao a do dominio de estoque: aqui o que
            // aconteceu e que o material recebido ja nao esta mais la.
            return back()->with(
                'error',
                'O material desta entrada já saiu do depósito, e o cancelamento deixaria o saldo negativo. '
                .'Registre a correção pelo movimento correspondente.',
            );
        } catch (RuntimeException $erro) {
            return back()->with('error', $erro->getMessage());
        }

        return back()->with('success', 'Entrada cancelada e saldo estornado.');
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
            // Desempate por id para que a paginacao seja estavel.
            ->orderBy(
                self::ORDENACAO_PERMITIDA[$filtros['sort'] ?? ''] ?? 'recebido_em',
                $filtros['direction'],
            )
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
     * Tudo que o formulario de nova entrada precisa escolher.
     *
     * Diferente das listas de filtro, aqui entram os cadastros inteiros: um
     * deposito recem-criado ainda nao tem entrada, e filtrar por uso deixaria
     * o usuario sem conseguir lancar nele.
     *
     * @return array<string, mixed>
     */
    private function opcoesDoFormulario(): array
    {
        return [
            'depositos' => DepositoAh::query()
                ->orderBy('nome')
                ->get(['id', 'nome', 'abreviacao'])
                ->map(static fn (DepositoAh $d): array => [
                    'id'    => $d->id,
                    'nome'  => $d->nome,
                    'sigla' => $d->abreviacao,
                ])
                ->all(),

            // Somente o que esta disponivel para pedido: material fora da lista
            // saiu de circulacao, e receber nele reabriria o item pela porta
            // dos fundos.
            'materiais' => MaterialAh::query()
                ->disponiveisParaPedido()
                ->orderBy('nome')
                ->get(['id', 'nome', 'unidade_medida'])
                ->map(static fn (MaterialAh $m): array => [
                    'id'      => $m->id,
                    'nome'    => $m->nome,
                    'unidade' => $m->unidade_medida,
                ])
                ->all(),

            'fontes' => FonteRecursoAh::query()
                ->orderBy('nome')
                ->get(['id', 'nome'])
                ->map(static fn (FonteRecursoAh $f): array => ['id' => $f->id, 'nome' => $f->nome])
                ->all(),

            'fornecedores' => FornecedorAh::query()
                ->orderBy('nome')
                ->get(['id', 'nome'])
                ->map(static fn (FornecedorAh $f): array => ['id' => $f->id, 'nome' => $f->nome])
                ->all(),
        ];
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
