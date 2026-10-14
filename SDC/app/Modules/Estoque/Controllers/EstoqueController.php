<?php

declare(strict_types=1);

namespace App\Modules\Estoque\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Estoque\DTOs\EntradaEstoqueDTO;
use App\Modules\Estoque\DTOs\ProdutoDTO;
use App\Modules\Estoque\DTOs\SaidaEstoqueDTO;
use App\Modules\Estoque\Enums\CategoriaEstoque;
use App\Modules\Estoque\Models\Almoxarifado;
use App\Modules\Estoque\Models\Lote;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Services\CadastroEstoqueService;
use App\Modules\Estoque\Services\EstoqueConsultaService;
use App\Modules\Estoque\Services\MovimentacaoEstoqueService;
use App\Modules\Estoque\Support\RegrasEstoque;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tela do modulo /estoque (#32 inventario de donativos, #33 estoque e kits).
 * A mesma regra atende a API v1 (Api\V1\Estoque): os dois lados chamam os
 * mesmos services e validam com RegrasEstoque.
 */
class EstoqueController extends Controller
{
    private const FILTROS = ['busca', 'categoria', 'almoxarifado_id', 'validade', 'status'];

    public function __construct(
        private readonly EstoqueConsultaService $consulta,
        private readonly MovimentacaoEstoqueService $movimentacao,
        private readonly CadastroEstoqueService $cadastro,
    ) {}

    public function index(Request $request): Response
    {
        $filtros = $request->only(self::FILTROS);

        return Inertia::render('Estoque/EstoqueIndex', [
            'activeSection' => (string) ($request->route('section') ?? 'dashboard'),
            // Primeira pagina dos lotes; as seguintes vem por estoque.lotes.lista
            // (scroll infinito), sem recarregar a pagina.
            'lotes'         => $this->consulta->lotes($filtros),
            'statistics'    => $this->consulta->estatisticas(),
            'ocupacao'      => $this->consulta->ocupacao(),
            'alertas'       => $this->consulta->alertas(),
            'kits'          => $this->consulta->kits(),
            'movimentacoes' => $this->consulta->movimentacoes([], 10)->items(),
            'catalogo'      => $this->consulta->catalogo(),
            'filters'       => $filtros,
            'filterOptions' => [
                'categorias'    => CategoriaEstoque::options(),
                'almoxarifados' => Almoxarifado::query()->where('ativo', true)->orderBy('nome')->get(['id', 'nome', 'tipo', 'capacidade'])
                    ->map(fn ($a) => ['value' => $a->id, 'label' => $a->nome, 'tipo' => $a->tipo])->values(),
                'validade'      => [
                    ['value' => 'em_dia', 'label' => 'Em dia'],
                    ['value' => 'vence_30', 'label' => 'Vence em 30 dias'],
                    ['value' => 'vencido', 'label' => 'Vencido'],
                ],
                'status'        => [['value' => 'quarentena', 'label' => 'Em quarentena']],
            ],
            'municipios'    => fn () => Municipio::catalogo(),
        ]);
    }

    /** Proxima pagina da lista de lotes (scroll infinito). */
    public function lotes(Request $request): JsonResponse
    {
        return response()->json($this->consulta->lotes($request->only(self::FILTROS)));
    }

    public function storeProduto(Request $request): RedirectResponse
    {
        $this->cadastro->criarProduto(ProdutoDTO::fromValidated($request->validate(RegrasEstoque::produto())));

        return back()->with('success', 'Produto cadastrado.');
    }

    public function updateProduto(Request $request, Produto $produto): RedirectResponse
    {
        $this->cadastro->atualizarProduto($produto, ProdutoDTO::fromValidated(
            $request->validate(RegrasEstoque::produto($produto->id, parcial: true)),
        ));

        return back()->with('success', 'Produto atualizado.');
    }

    public function destroyProduto(Produto $produto): RedirectResponse
    {
        try {
            $this->cadastro->excluirProduto($produto);
        } catch (\DomainException $e) {
            return back()->withErrors(['estoque' => $e->getMessage()]);
        }

        return back()->with('success', 'Produto excluido.');
    }

    public function entrada(Request $request): RedirectResponse
    {
        try {
            $this->movimentacao->entrada(EntradaEstoqueDTO::fromValidated($request->validate(RegrasEstoque::entrada())), $request->user()?->id);
        } catch (\DomainException $e) {
            return back()->withErrors(['estoque' => $e->getMessage()]);
        }

        return back()->with('success', 'Entrada registrada.');
    }

    public function saida(Request $request): RedirectResponse
    {
        try {
            $resultado = $this->movimentacao->saida(SaidaEstoqueDTO::fromValidated($request->validate(RegrasEstoque::saida())), $request->user()?->id);
        } catch (\DomainException $e) {
            return back()->withErrors(['estoque' => $e->getMessage()]);
        }

        return back()->with('success', "Saida registrada no romaneio {$resultado['romaneio']}.");
    }

    public function storeAlmoxarifado(Request $request): RedirectResponse
    {
        $this->cadastro->criarAlmoxarifado($request->validate(RegrasEstoque::almoxarifado()));

        return back()->with('success', 'Almoxarifado cadastrado.');
    }

    public function storeKit(Request $request): RedirectResponse
    {
        $this->cadastro->criarKit($request->validate(RegrasEstoque::kit()));

        return back()->with('success', 'Kit cadastrado.');
    }

    public function quarentena(Request $request, Lote $lote): RedirectResponse
    {
        $bloqueado = $request->validate(['bloqueado' => ['required', 'boolean']])['bloqueado'];
        $this->cadastro->alternarQuarentena($lote, (bool) $bloqueado);

        return back()->with('success', $bloqueado ? 'Lote em quarentena.' : 'Lote liberado.');
    }

    /** Romaneio de saida: a tela imprime a partir daqui. */
    public function romaneio(string $romaneio): JsonResponse
    {
        $itens = $this->consulta->movimentacoes(['romaneio' => $romaneio], 200)->items();
        abort_if($itens === [], 404);

        return response()->json(['romaneio' => $romaneio, 'itens' => $itens]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $filtros = $request->only(self::FILTROS);
        $filename = 'estoque_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($filtros): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['SKU', 'Produto', 'Categoria', 'Patrimonio', 'Almoxarifado', 'Lote', 'Validade', 'Fornecedor/Doador', 'Endereco', 'Saldo', 'Unidade', 'Status'], ';');

            $pagina = 1;
            do {
                $lote = $this->consulta->lotes($filtros, 500, $pagina++);
                foreach ($lote->items() as $l) {
                    fputcsv($handle, [
                        $l['sku'], $l['nome'], $l['categoria_label'], $l['patrimonio'], $l['almoxarifado'], $l['lote'],
                        $l['validade'] ? date('d/m/Y', strtotime($l['validade'])) : '', $l['fornecedor'], $l['endereco'],
                        $l['saldo'], $l['unidade_base'], $l['status'],
                    ], ';');
                }
            } while ($lote->hasMorePages());

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
