<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Models\DepositoAh;
use App\Modules\AjudaHumanitaria\Models\SaldoEstoqueAh;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Consulta do estoque de Ajuda Humanitaria (RN-25).
 *
 * Le a projecao ajuda_h_estoque_saldos, que o ledger mantem. Somente leitura:
 * qualquer escrita no estoque passa por RegistrarMovimentoEstoque, nunca por
 * uma tela de consulta.
 *
 * Saldo zero fica de fora da listagem. Material que zerou em um deposito nao e
 * informacao de estoque, e ruido: o legado tinha 4.510 linhas de saldo das
 * quais apenas 118 tinham quantidade.
 */
class EstoqueAhController extends Controller
{
    private const POR_PAGINA = 25;

    /**
     * Faixas de nivel de estoque usadas pelos cartoes de filtro rapido.
     *
     * ATENCAO: os limites sao um ponto de partida, nao regra de negocio
     * confirmada. Foram escolhidos para separar o conjunto real em tres grupos
     * uteis (55, 24 e 39 itens na carga de 07/08/2026). Quando a CEDEC definir
     * o ponto de reposicao por material, isto vira parametro, nao constante.
     */
    private const NIVEL_CRITICO = 50;

    private const NIVEL_BAIXO = 200;

    public function index(Request $request): Response
    {
        $filtros = $this->filtrosDaRequisicao($request);
        $pagina  = $this->consultaDeSaldos($filtros)->paginate(self::POR_PAGINA)->withQueryString();

        return Inertia::render('AjudaHumanitaria/Estoque/Index', [
            'saldos' => [
                'data' => collect($pagina->items())->map(static fn ($linha): array => [
                    'material_id'   => (int) $linha->material_ah_id,
                    'deposito_id'   => (int) $linha->deposito_id,
                    'material'      => (string) $linha->material_nome,
                    'unidade'       => (string) $linha->material_unidade,
                    'deposito'      => (string) $linha->deposito_nome,
                    'sigla'         => (string) $linha->deposito_sigla,
                    'saldo'         => (float) $linha->saldo,
                    'atualizado_em' => $linha->atualizado_em,
                ])->all(),
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
            'depositos'    => $this->depositosComSaldo(),
            'filtros'      => $filtros,
        ]);
    }

    /**
     * Os cartoes acompanham deposito e busca, mas nao o proprio nivel.
     *
     * Se o nivel entrasse na contagem, clicar em "Critico" zeraria os outros
     * tres cartoes e o usuario perderia justamente a comparacao que o levou a
     * clicar. O cartao selecionado destaca, os demais seguem informando.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, int|float>
     */
    private function estatisticas(array $filtros): array
    {
        $base = SaldoEstoqueAh::query()
            ->where('saldo', '<>', 0)
            ->when($filtros['deposito_id'], fn ($q, $id) => $q->where('deposito_id', $id))
            ->when($filtros['busca'], fn ($q, $busca) => $q->whereExists(
                fn ($sub) => $sub->selectRaw('1')
                    ->from('materiais_ah as m')
                    ->whereColumn('m.id', 'ajuda_h_estoque_saldos.material_ah_id')
                    ->where('m.nome', 'ilike', '%'.$busca.'%')
            ));

        return [
            'itens_com_saldo'  => (clone $base)->count(),
            'nivel_critico'    => (clone $base)->where('saldo', '<', self::NIVEL_CRITICO)->count(),
            'nivel_baixo'      => (clone $base)->where('saldo', '>=', self::NIVEL_CRITICO)
                ->where('saldo', '<', self::NIVEL_BAIXO)->count(),
            'nivel_confortavel' => (clone $base)->where('saldo', '>=', self::NIVEL_BAIXO)->count(),
            'quantidade_total' => (float) ((clone $base)->sum('saldo')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function filtrosDaRequisicao(Request $request): array
    {
        return [
            'deposito_id' => $request->integer('deposito_id') ?: null,
            'busca'       => trim((string) $request->string('busca')) ?: null,
            'nivel'       => $this->nivelValido($request->string('nivel')->toString()),
            // Janela vinda do modal de exportacao. Recai sobre atualizado_em, o
            // instante em que o saldo daquele par mudou pela ultima vez, e nao
            // sobre a data do movimento: a tabela e projecao, nao ledger. Para
            // "o que se movimentou no periodo" a consulta certa e o ledger, que
            // ainda nao tem tela.
            'data_inicio' => $this->dataValida($request->string('data_inicio')->toString()),
            'data_fim'    => $this->dataValida($request->string('data_fim')->toString()),
        ];
    }

    /** Data fora do formato ISO e ignorada em vez de derrubar a consulta. */
    private function dataValida(string $data): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) === 1 ? $data : null;
    }

    /**
     * Consulta unica da tela e do CSV, para que o arquivo nunca divirja da
     * lista que o usuario acabou de ver.
     *
     * @param  array<string, mixed>  $filtros
     */
    private function consultaDeSaldos(array $filtros): Builder
    {
        return SaldoEstoqueAh::query()
            ->join('ajuda_h_depositos as d', 'd.id', '=', 'ajuda_h_estoque_saldos.deposito_id')
            ->join('materiais_ah as m', 'm.id', '=', 'ajuda_h_estoque_saldos.material_ah_id')
            ->where('ajuda_h_estoque_saldos.saldo', '<>', 0)
            ->when($filtros['deposito_id'], fn ($q, $id) => $q->where('d.id', $id))
            ->when($filtros['busca'], fn ($q, $busca) => $q->where('m.nome', 'ilike', '%'.$busca.'%'))
            ->when($filtros['nivel'], fn ($q, $nivel) => $this->aplicarNivel($q, $nivel))
            ->when($filtros['data_inicio'], fn ($q, $data) => $q->whereDate('ajuda_h_estoque_saldos.atualizado_em', '>=', $data))
            ->when($filtros['data_fim'], fn ($q, $data) => $q->whereDate('ajuda_h_estoque_saldos.atualizado_em', '<=', $data))
            ->orderBy('d.nome')
            ->orderByDesc('ajuda_h_estoque_saldos.saldo')
            ->select([
                'ajuda_h_estoque_saldos.material_ah_id',
                'ajuda_h_estoque_saldos.deposito_id',
                'ajuda_h_estoque_saldos.saldo',
                'ajuda_h_estoque_saldos.atualizado_em',
                'd.nome as deposito_nome',
                'd.abreviacao as deposito_sigla',
                'm.nome as material_nome',
                'm.unidade_medida as material_unidade',
            ]);
    }

    /**
     * Exporta em CSV exatamente o recorte que a tela mostra.
     *
     * Usa a mesma montagem de consulta da listagem, entao filtro aplicado na
     * interface vale no arquivo. Sai por streaming e sem paginacao: o conjunto
     * e pequeno (118 linhas hoje), mas cursor() evita carregar tudo em memoria
     * caso o estoque cresca.
     *
     * A permissao e a mesma da consulta: exportar o que ja esta na tela nao
     * amplia acesso.
     */
    public function export(Request $request): StreamedResponse
    {
        $filtros = $this->filtrosDaRequisicao($request);
        $consulta = $this->consultaDeSaldos($filtros);

        $nome = 'estoque-ajuda-humanitaria-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($consulta): void {
            $saida = fopen('php://output', 'wb');

            // BOM para o Excel reconhecer UTF-8 e nao quebrar acentuacao.
            fwrite($saida, "\xEF\xBB\xBF");
            fputcsv($saida, ['Deposito', 'Sigla', 'Material', 'Unidade', 'Saldo', 'Atualizado em'], ';');

            foreach ($consulta->cursor() as $linha) {
                fputcsv($saida, [
                    $linha->deposito_nome,
                    $linha->deposito_sigla,
                    $linha->material_nome,
                    $linha->material_unidade,
                    $linha->saldo,
                    $linha->atualizado_em,
                ], ';');
            }

            fclose($saida);
        }, $nome, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Nivel desconhecido vira null em vez de lista vazia sem explicacao. */
    private function nivelValido(string $nivel): ?string
    {
        return in_array($nivel, ['critico', 'baixo', 'confortavel'], true) ? $nivel : null;
    }

    private function aplicarNivel(mixed $consulta, string $nivel): mixed
    {
        $coluna = 'ajuda_h_estoque_saldos.saldo';

        return match ($nivel) {
            'critico'     => $consulta->where($coluna, '<', self::NIVEL_CRITICO),
            'baixo'       => $consulta->where($coluna, '>=', self::NIVEL_CRITICO)
                ->where($coluna, '<', self::NIVEL_BAIXO),
            'confortavel' => $consulta->where($coluna, '>=', self::NIVEL_BAIXO),
        };
    }

    /**
     * Apenas depositos que tem algo em estoque. Oferecer os 24 no filtro,
     * sendo que a maioria esta zerada, so gera consulta que volta vazia.
     *
     * @return array<int, array{id: int, nome: string, sigla: string}>
     */
    private function depositosComSaldo(): array
    {
        return DepositoAh::query()
            ->whereHas('saldos', fn ($q) => $q->where('saldo', '<>', 0))
            ->orderBy('nome')
            ->get(['id', 'nome', 'abreviacao'])
            ->map(static fn (DepositoAh $deposito): array => [
                'id'    => $deposito->id,
                'nome'  => $deposito->nome,
                'sigla' => $deposito->abreviacao,
            ])
            ->all();
    }
}
