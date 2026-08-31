<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Models\MaterialAh;
use App\Modules\AjudaHumanitaria\Requests\SalvarMaterialAhRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Catalogo de material de ajuda humanitaria (RN-07).
 *
 * Diferente das demais telas do estoque migrado, esta escreve: o catalogo e
 * cadastro, nao movimento. Criar material nao mexe em saldo, e por isso nao
 * passa pelo ledger.
 *
 * Material com historico nao e excluido, apenas marcado como indisponivel
 * para pedido: o nome aparece em entradas, liberacoes e transferencias
 * antigas, e apagar a linha deixaria esse historico sem legenda.
 */
class MaterialAhController extends Controller
{
    private const POR_PAGINA = 25;

    /**
     * Colunas ordenaveis, mapeadas para a coluna real.
     *
     * Whitelist obrigatoria: sort vem da URL e iria direto para o ORDER BY.
     * O saldo total fica de fora porque e soma vinda de withSum.
     */
    private const ORDENACAO_PERMITIDA = [
        'nome'         => 'nome',
        'unidade'      => 'unidade_medida',
        'codigo'       => 'codigo_legado',
        'disponivel'   => 'disponivel_para_pedido',
    ];

    /** Recortes que os cartoes oferecem como filtro rapido. */
    private const SITUACOES = ['disponivel', 'indisponivel', 'com_saldo', 'sem_saldo'];

    /**
     * Tabelas que apontam para materiais_ah com ON DELETE RESTRICT.
     *
     * A lista existe para transformar a violacao de chave estrangeira em
     * mensagem antes de tentar o DELETE: o banco devolveria SQLSTATE 23503, e
     * o usuario veria erro de servidor no lugar da explicacao.
     */
    private const VINCULOS = [
        'ajuda_h_estoque_saldos'      => 'saldo em depósito',
        'ajuda_h_estoque_movimentos'  => 'movimento de estoque',
        'ajuda_h_entrada_itens'       => 'entrada de material',
        'ajuda_h_liberacao_itens'     => 'liberação',
        'ajuda_h_transferencia_itens' => 'transferência',
    ];

    public function index(Request $request): Response
    {
        $filtros = $this->filtrosDaRequisicao($request);
        $pagina  = $this->consulta($filtros)->paginate(self::POR_PAGINA)->withQueryString();

        return Inertia::render('AjudaHumanitaria/Materiais/Index', [
            'materiais' => [
                'data' => collect($pagina->items())
                    ->map(fn (MaterialAh $m): array => $this->paraLinha($m))
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
            'unidades'     => $this->unidadesUsadas(),
            'filtros'      => $filtros,
            'ordenacao'    => ['sort' => $filtros['sort'] ?? 'nome', 'direction' => $filtros['direction']],
        ]);
    }

    public function store(SalvarMaterialAhRequest $request): RedirectResponse
    {
        MaterialAh::create($request->validated());

        return back()->with('success', 'Material cadastrado.');
    }

    public function update(SalvarMaterialAhRequest $request, int $id): RedirectResponse
    {
        MaterialAh::findOrFail($id)->update($request->validated());

        return back()->with('success', 'Material atualizado.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $material = MaterialAh::findOrFail($id);
        $vinculo  = $this->primeiroVinculo($id);

        if ($vinculo !== null) {
            return back()->with(
                'error',
                'Este material tem '.$vinculo.' registrado e não pode ser excluído. '
                .'Marque como indisponível para pedido para tirá-lo da lista.',
            );
        }

        $material->delete();

        return back()->with('success', 'Material excluído.');
    }

    public function export(Request $request): StreamedResponse
    {
        $consulta = $this->consulta($this->filtrosDaRequisicao($request));
        $nome     = 'materiais-ajuda-humanitaria-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($consulta): void {
            $saida = fopen('php://output', 'wb');

            fwrite($saida, "\xEF\xBB\xBF");
            fputcsv($saida, [
                'Codigo legado', 'Material', 'Descricao', 'Unidade',
                'Disponivel para pedido', 'Saldo total', 'Depositos com saldo',
            ], ';');

            foreach ($consulta->cursor() as $m) {
                fputcsv($saida, [
                    $m->codigo_legado,
                    $m->nome,
                    $m->descricao,
                    $m->unidade_medida,
                    $m->disponivel_para_pedido ? 'Sim' : 'Nao',
                    (float) ($m->saldo_total ?? 0),
                    $m->depositos_com_saldo ?? 0,
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
        $unidade  = trim((string) $request->string('unidade'));

        return [
            'busca'     => trim((string) $request->string('busca')) ?: null,
            'unidade'   => $unidade !== '' ? $unidade : null,
            'situacao'  => in_array($situacao, self::SITUACOES, true) ? $situacao : null,
            'sort'      => array_key_exists($request->string('sort')->toString(), self::ORDENACAO_PERMITIDA)
                ? $request->string('sort')->toString()
                : null,
            // Catalogo abre em ordem alfabetica, e nao cronologica como as
            // listagens de movimento.
            'direction' => strtolower((string) $request->input('direction')) === 'desc' ? 'desc' : 'asc',
        ];
    }

    /**
     * Consulta unica da listagem e do CSV, para que o arquivo nunca divirja da
     * lista que o usuario acabou de ver.
     *
     * @param  array<string, mixed>  $filtros
     */
    private function consulta(array $filtros): Builder
    {
        return MaterialAh::query()
            // Saldo zero nao conta como deposito com saldo: o par
            // (material, deposito) continua na projecao depois de zerar.
            ->withSum(['saldos as saldo_total' => fn ($q) => $q->where('saldo', '<>', 0)], 'saldo')
            ->withCount(['saldos as depositos_com_saldo' => fn ($q) => $q->where('saldo', '<>', 0)])
            ->when($filtros['busca'], fn ($q, $busca) => $q->where(function ($sub) use ($busca): void {
                $sub->where('nome', 'ilike', '%'.$busca.'%')
                    ->orWhere('descricao', 'ilike', '%'.$busca.'%')
                    ->orWhere('codigo_legado', 'ilike', '%'.$busca.'%');
            }))
            ->when($filtros['unidade'], fn ($q, $unidade) => $q->where('unidade_medida', $unidade))
            ->when($filtros['situacao'], fn ($q, $situacao) => $this->aplicarSituacao($q, $situacao))
            ->orderBy(
                self::ORDENACAO_PERMITIDA[$filtros['sort'] ?? ''] ?? 'nome',
                $filtros['direction'],
            )
            // Desempate por id para que a paginacao seja estavel.
            ->orderBy('id');
    }

    private function aplicarSituacao(mixed $consulta, string $situacao): mixed
    {
        return match ($situacao) {
            'disponivel'   => $consulta->where('disponivel_para_pedido', true),
            'indisponivel' => $consulta->where('disponivel_para_pedido', false),
            'com_saldo'    => $consulta->whereHas('saldos', fn ($q) => $q->where('saldo', '<>', 0)),
            // Ofertado no pedido sem nada em deposito: e o recorte acionavel do
            // catalogo, porque o municipio pede o que o CEDEC nao tem.
            'sem_saldo'    => $consulta->where('disponivel_para_pedido', true)
                ->whereDoesntHave('saldos', fn ($q) => $q->where('saldo', '<>', 0)),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function paraLinha(MaterialAh $m): array
    {
        return [
            'id'                     => $m->id,
            'nome'                   => $m->nome,
            'descricao'              => $m->descricao,
            'unidade_medida'         => $m->unidade_medida,
            'disponivel_para_pedido' => $m->disponivel_para_pedido,
            'codigo_legado'          => $m->codigo_legado,
            'saldo_total'            => (float) ($m->saldo_total ?? 0),
            'depositos_com_saldo'    => (int) ($m->depositos_com_saldo ?? 0),
        ];
    }

    /**
     * Cartoes contam sem aplicar a propria situacao, para nao zerar a
     * comparacao quando o usuario clica em um deles.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, int>
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
            'total'         => $contar(null),
            'disponiveis'   => $contar('disponivel'),
            'indisponiveis' => $contar('indisponivel'),
            'com_saldo'     => $contar('com_saldo'),
            'sem_saldo'     => $contar('sem_saldo'),
        ];
    }

    /**
     * Unidades ja em uso, para o filtro e para a sugestao do formulario.
     *
     * Sai do proprio catalogo em vez de uma lista fixa: o legado trouxe "UN",
     * "Metro" e "Unitario", e uma lista em codigo esconderia as duas ultimas.
     *
     * @return array<int, string>
     */
    private function unidadesUsadas(): array
    {
        return MaterialAh::query()
            ->select('unidade_medida')
            ->distinct()
            ->orderBy('unidade_medida')
            ->pluck('unidade_medida')
            ->all();
    }

    /**
     * Primeiro vinculo encontrado, ou null se o material nunca foi usado.
     *
     * Para na primeira tabela que responde: a mensagem cita um exemplo, nao a
     * lista inteira.
     */
    private function primeiroVinculo(int $materialId): ?string
    {
        foreach (self::VINCULOS as $tabela => $rotulo) {
            $existe = DB::table($tabela)->where('material_ah_id', $materialId)->exists();

            if ($existe) {
                return $rotulo;
            }
        }

        return null;
    }
}
