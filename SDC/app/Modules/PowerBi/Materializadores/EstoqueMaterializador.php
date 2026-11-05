<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Materializadores;

use App\Modules\Estoque\Models\Lote;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Services\EstoqueConsultaService;
use App\Modules\PowerBi\Support\CursorDeMaterializacao;
use App\Modules\PowerBi\Support\GravadorBi;
use App\Modules\PowerBi\Support\JanelaDaFonte;
use App\Modules\PowerBi\Support\ResultadoExecucao;
use App\Support\Database\ConexaoPowerBi;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * bi_estoque. Produtos e lotes em estrategia completa: o saldo do produto e
 * derivado dos lotes e o status do lote depende da data de hoje, entao nenhum
 * dos dois e incremental por updated_at. Movimentacoes sao so insercao: cursor
 * por id, limitado a created_at ate o limite superior da janela (um id baixo
 * ainda nao confirmado nao e pulado por um commit tardio na margem).
 *
 * LGPD: movimentacoes saem sem usuario, documento e texto livre de destino.
 */
final class EstoqueMaterializador extends MaterializadorCompletoBase
{
    private const MOVIMENTACOES = 'movimentacoes';

    public function __construct(GravadorBi $gravador, private readonly EstoqueConsultaService $consulta)
    {
        parent::__construct($gravador);
    }

    public function modulo(): string
    {
        return 'estoque';
    }

    public function materializar(JanelaDaFonte $janela, CursorDeMaterializacao $cursor): ResultadoExecucao
    {
        $resultado = parent::materializar($janela, $cursor);
        $definicao = $this->definicao(self::MOVIMENTACOES);
        $estado = $cursor->estado(self::MOVIMENTACOES);
        $ultimo = ['id' => null, 'em' => null];

        $gravacao = $cursor->emTransacao(function () use ($definicao, $janela, $cursor, $estado, &$ultimo) {
            $r = $this->gravador->gravar(
                $definicao,
                $this->movimentacoes($janela, $estado->id, $janela->inferior($estado), $ultimo)
            );
            $cursor->avancar(
                self::MOVIMENTACOES,
                $ultimo['em'],
                $ultimo['id'] !== null ? (string) $ultimo['id'] : null,
                $janela
            );

            return $r;
        });

        return $resultado->registrar(self::MOVIMENTACOES, $gravacao);
    }

    public function chavesDaFonte(string $tabela): iterable
    {
        if ($tabela !== self::MOVIMENTACOES) {
            return parent::chavesDaFonte($tabela);
        }

        return $this->idsDeMovimentacoes(JanelaDaFonte::obter());
    }

    protected function linhas(string $tabela): iterable
    {
        return match ($tabela) {
            'produtos' => $this->produtos(),
            'lotes' => $this->lotes(),
            self::MOVIMENTACOES => $this->movimentacoes(JanelaDaFonte::obter(), null, null),
            default => throw new \InvalidArgumentException("Tabela {$tabela} fora do catalogo de estoque."),
        };
    }

    /** @return iterable<array<string, mixed>> */
    private function produtos(): iterable
    {
        $consulta = $this->consulta->consultaProdutos([], ConexaoPowerBi::FONTE)->reorder();

        foreach ($consulta->lazyById((int) config('powerbi.lote', 1000), 'estoque_produtos.id', 'id') as $p) {
            /** @var Produto $p */
            yield $this->consulta->linhaParaBiDeProduto($p);
        }
    }

    /** @return iterable<array<string, mixed>> */
    private function lotes(): iterable
    {
        $consulta = $this->consulta->consultaLotes([], ConexaoPowerBi::FONTE)->reorder();

        foreach ($consulta->lazyById((int) config('powerbi.lote', 1000), 'estoque_lotes.id', 'id') as $l) {
            /** @var Lote $l */
            yield $this->consulta->linhaParaBiDeLote($l);
        }
    }

    /**
     * Movimentacoes com id acima do cursor (ou criadas desde $desde, no
     * --desde) e criadas ate o limite superior da janela. Preenche $ultimo
     * com o id e a data da ultima linha lida.
     *
     * @param array{id: int|null, em: Carbon|null} $ultimo
     * @return iterable<array<string, mixed>>
     */
    private function movimentacoes(JanelaDaFonte $janela, ?string $apos, ?Carbon $desde, array &$ultimo = []): iterable
    {
        $consulta = $this->baseMovimentacoes($janela);
        if ($apos !== null) {
            $consulta->where('id', '>', (int) $apos);
        } elseif ($desde !== null) {
            $consulta->where('created_at', '>=', $this->naTimezoneDoApp($desde));
        }

        foreach ($consulta->lazyById((int) config('powerbi.lote', 1000), 'id') as $m) {
            $linha = $this->consulta->linhaParaBiDeMovimentacao($m);
            $ultimo = ['id' => (int) $m->id, 'em' => Carbon::parse($m->created_at)];

            yield $linha;
        }
    }

    /** @return iterable<array{id: int}> */
    private function idsDeMovimentacoes(JanelaDaFonte $janela): iterable
    {
        foreach ($this->baseMovimentacoes($janela)->select('id')->lazyById((int) config('powerbi.lote', 1000), 'id') as $m) {
            yield ['id' => (int) $m->id];
        }
    }

    private function baseMovimentacoes(JanelaDaFonte $janela): Builder
    {
        return ConexaoPowerBi::fonte()->table('estoque_movimentacoes')
            ->where('created_at', '<=', $this->naTimezoneDoApp($janela->superior));
    }

    /** created_at e timestamp sem fuso, gravado na timezone do app. */
    private function naTimezoneDoApp(Carbon $momento): Carbon
    {
        return $momento->copy()->setTimezone((string) config('app.timezone'));
    }
}
