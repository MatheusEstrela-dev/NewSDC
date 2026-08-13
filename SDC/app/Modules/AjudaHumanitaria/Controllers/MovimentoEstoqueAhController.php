<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Enums\TipoMovimentoEstoque;
use App\Modules\AjudaHumanitaria\Models\DepositoAh;
use App\Modules\AjudaHumanitaria\Models\MovimentoEstoqueAh;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Extrato do ledger de estoque.
 *
 * A tela que faltava para fechar a auditoria: o saldo de um material em um
 * deposito e a soma dos lancamentos, e ate aqui nao havia como conferir essa
 * conta pela interface.
 *
 * Somente leitura, e nao por limitacao: o ledger e append-only. Corrigir um
 * lancamento e lancar o oposto, o que acontece na operacao que o originou
 * (ver CancelarEntradaMaterial), nunca por edicao de linha.
 */
class MovimentoEstoqueAhController extends Controller
{
    private const POR_PAGINA = 50;

    /**
     * Colunas ordenaveis, mapeadas para a coluna real.
     *
     * Whitelist obrigatoria: sort vem da URL e iria direto para o ORDER BY.
     * Material e deposito vivem em outra tabela e exigiriam join na consulta
     * que tambem serve o CSV.
     */
    private const ORDENACAO_PERMITIDA = [
        'ocorrido'   => 'ocorrido_em',
        'tipo'       => 'tipo',
        'quantidade' => 'quantidade',
    ];

    public function index(Request $request): Response
    {
        $filtros = $this->filtrosDaRequisicao($request);
        $pagina  = $this->consulta($filtros)->paginate(self::POR_PAGINA)->withQueryString();

        return Inertia::render('AjudaHumanitaria/Movimentos/Index', [
            'movimentos' => [
                'data' => collect($pagina->items())
                    ->map(fn (MovimentoEstoqueAh $m): array => $this->paraLinha($m))
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
            'depositos'    => $this->depositosComMovimento(),
            'opcoesTipo'   => $this->tiposEmUso(),
            'filtros'      => $filtros,
            'ordenacao'    => ['sort' => $filtros['sort'] ?? 'ocorrido', 'direction' => $filtros['direction']],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $consulta = $this->consulta($this->filtrosDaRequisicao($request));
        $nome     = 'movimentos-estoque-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($consulta): void {
            $saida = fopen('php://output', 'wb');

            fwrite($saida, "\xEF\xBB\xBF");
            fputcsv($saida, [
                'Data', 'Tipo', 'Material', 'Unidade', 'Deposito',
                'Quantidade', 'Origem', 'Registrado por',
            ], ';');

            foreach ($consulta->cursor() as $m) {
                fputcsv($saida, [
                    $m->ocorrido_em?->format('d/m/Y H:i'),
                    TipoMovimentoEstoque::rotuloDe($m->tipo),
                    $m->material?->nome,
                    $m->material?->unidade_medida,
                    $m->deposito?->nome,
                    (float) $m->quantidade,
                    $this->descricaoDaOrigem($m),
                    $m->registradoPor?->name,
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
        $tipo = $request->string('tipo')->toString();

        return [
            'deposito_id' => $request->integer('deposito_id') ?: null,
            'material_id' => $request->integer('material_id') ?: null,
            // Aceita tipo que nao esta no enum: o ledger e antigo e pode ter
            // vocabulario que o codigo de hoje nao conhece.
            'tipo'        => $tipo !== '' ? $tipo : null,
            'sentido'     => in_array($request->string('sentido')->toString(), ['entrada', 'saida'], true)
                ? $request->string('sentido')->toString()
                : null,
            'busca'       => trim((string) $request->string('busca')) ?: null,
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
        return MovimentoEstoqueAh::query()
            ->with([
                'material:id,nome,unidade_medida',
                'deposito:id,nome,abreviacao',
                'registradoPor:id,name',
            ])
            ->when($filtros['deposito_id'], fn ($q, $id) => $q->where('deposito_id', $id))
            ->when($filtros['material_id'], fn ($q, $id) => $q->where('material_ah_id', $id))
            ->when($filtros['tipo'], fn ($q, $tipo) => $q->where('tipo', $tipo))
            // O sinal da quantidade e o que diz se entrou ou saiu; nao existe
            // coluna separada que possa divergir do numero.
            ->when($filtros['sentido'] === 'entrada', fn ($q) => $q->where('quantidade', '>', 0))
            ->when($filtros['sentido'] === 'saida', fn ($q) => $q->where('quantidade', '<', 0))
            ->when($filtros['busca'], fn ($q, $busca) => $q->whereHas(
                'material',
                fn ($m) => $m->where('nome', 'ilike', '%'.$busca.'%')
            ))
            ->when($filtros['data_inicio'], fn ($q, $d) => $q->whereDate('ocorrido_em', '>=', $d))
            ->when($filtros['data_fim'], fn ($q, $d) => $q->whereDate('ocorrido_em', '<=', $d))
            ->orderBy(
                self::ORDENACAO_PERMITIDA[$filtros['sort'] ?? ''] ?? 'ocorrido_em',
                $filtros['direction'],
            )
            // Desempate por id: varios lancamentos de uma mesma operacao
            // compartilham o instante, e sem isto a paginacao repetiria linha.
            ->orderByDesc('id');
    }

    /**
     * @return array<string, mixed>
     */
    private function paraLinha(MovimentoEstoqueAh $m): array
    {
        return [
            'id'             => $m->id,
            'ocorrido_em'    => $m->ocorrido_em?->toIso8601String(),
            'tipo'           => $m->tipo,
            'tipo_label'     => TipoMovimentoEstoque::rotuloDe($m->tipo),
            'tipo_cor'       => TipoMovimentoEstoque::corDe($m->tipo),
            'material'       => $m->material?->nome,
            'unidade'        => $m->material?->unidade_medida,
            'deposito'       => $m->deposito?->nome,
            'sigla'          => $m->deposito?->abreviacao,
            'quantidade'     => (float) $m->quantidade,
            'origem'         => $this->descricaoDaOrigem($m),
            'origem_url'     => $this->urlDaOrigem($m),
            'registrado_por' => $m->registradoPor?->name,
        ];
    }

    /**
     * De onde veio o lancamento, em texto.
     *
     * origem_tipo e origem_id sao um par (CHECK ajuda_h_mov_origem_ck). Os 118
     * lancamentos da carga apontam para ajuda_h_legado_raw, que nao e tela.
     */
    private function descricaoDaOrigem(MovimentoEstoqueAh $m): ?string
    {
        return match ($m->origem_tipo) {
            null                 => null,
            'entrada'            => 'Entrada #'.$m->origem_id,
            'transferencia'      => 'Transferência #'.$m->origem_id,
            'liberacao'          => 'Liberação #'.$m->origem_id,
            'ajuda_h_legado_raw' => 'Migração do sistema anterior',
            default              => $m->origem_tipo.' #'.$m->origem_id,
        };
    }

    /** Somente origem que tem tela; o resto fica como texto. */
    private function urlDaOrigem(MovimentoEstoqueAh $m): ?string
    {
        return match ($m->origem_tipo) {
            'entrada'       => '/ajuda-humanitaria/entradas/'.$m->origem_id,
            'transferencia' => '/ajuda-humanitaria/transferencias/'.$m->origem_id,
            'liberacao'     => '/ajuda-humanitaria/liberacoes/'.$m->origem_id,
            default         => null,
        };
    }

    /**
     * Os cartoes contam sem aplicar o proprio sentido, para nao zerar a
     * comparacao quando o usuario clica em um deles.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, int|float>
     */
    private function estatisticas(array $filtros): array
    {
        $semSentido = $filtros;
        $semSentido['sentido'] = null;

        $base = fn (): Builder => $this->consulta($semSentido);

        $entradas = (clone $base())->where('quantidade', '>', 0);
        $saidas   = (clone $base())->where('quantidade', '<', 0);

        return [
            'lancamentos' => (int) $base()->toBase()->getCountForPagination(),
            'entradas'    => (int) $entradas->toBase()->getCountForPagination(),
            'saidas'      => (int) $saidas->toBase()->getCountForPagination(),
            // Soma com sinal: e o efeito liquido do recorte sobre o estoque.
            'saldo_liquido' => (float) ($base()->sum('quantidade')),
        ];
    }

    /**
     * @return array<int, array{id: int, nome: string, sigla: string}>
     */
    private function depositosComMovimento(): array
    {
        return DepositoAh::query()
            ->whereExists(fn ($q) => $q->selectRaw('1')
                ->from('ajuda_h_estoque_movimentos as mov')
                ->whereColumn('mov.deposito_id', 'ajuda_h_depositos.id'))
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
     * Tipos que existem no ledger, e nao todos os do enum.
     *
     * Oferecer TRANSF_SAIDA hoje produziria filtro que nunca devolve nada,
     * porque transferencia ainda nao lanca.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function tiposEmUso(): array
    {
        return MovimentoEstoqueAh::query()
            ->select('tipo')
            ->distinct()
            ->orderBy('tipo')
            ->pluck('tipo')
            ->map(static fn (string $tipo): array => [
                'value' => $tipo,
                'label' => TipoMovimentoEstoque::rotuloDe($tipo),
            ])
            ->all();
    }
}
