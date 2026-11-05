<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Materializadores;

use App\Http\Controllers\Api\V1\AjudaHumanitaria\LiberacaoApiService;
use App\Http\Controllers\Api\V1\AjudaHumanitaria\PedidoConsolidadoApiService;
use App\Http\Controllers\Api\V1\AjudaHumanitaria\SaldoCestaApiService;
use App\Modules\PowerBi\Support\ConsultaNaFonte;
use App\Modules\PowerBi\Support\GravadorBi;

/**
 * bi_ajuda em estrategia completa nas quatro tabelas: nenhuma tem updated_at
 * confiavel (itens de liberacao nao tem carimbo; liberacoes e pedidos embutem
 * nome de municipio, deposito e totais de itens; saldos sao fotos). O
 * gravador so regrava o que mudou (hash), entao o custo e a leitura.
 */
final class AjudaMaterializador extends MaterializadorCompletoBase
{
    public function __construct(
        GravadorBi $gravador,
        private readonly LiberacaoApiService $liberacoes,
        private readonly PedidoConsolidadoApiService $pedidos,
        private readonly SaldoCestaApiService $saldos,
    ) {
        parent::__construct($gravador);
    }

    public function modulo(): string
    {
        return 'ajuda';
    }

    protected function linhas(string $tabela): iterable
    {
        return match ($tabela) {
            'liberacoes' => $this->lerPorId(
                $this->liberacoes->consultaParaBi(),
                'l.id',
                'liberacao_id',
                fn (object $l): array => $this->liberacoes->linhaParaBi($l)
            ),
            'liberacao_itens' => $this->lerPorId(
                $this->liberacoes->consultaCedec(),
                'i.id',
                'item_id',
                fn (object $l): array => $this->liberacoes->linhaItemParaBi($l)
            ),
            'pedidos_itens' => $this->lerPorId(
                $this->pedidos->consultaItensParaBi(),
                'i.id',
                'id',
                fn (object $l): array => $this->pedidos->linhaItemParaBi($l)
            ),
            'saldos_cesta' => $this->saldosCesta(),
            default => throw new \InvalidArgumentException("Tabela {$tabela} fora do catalogo de ajuda."),
        };
    }

    /** Foto pequena (deposito x material): sem paginacao por chave. */
    private function saldosCesta(): iterable
    {
        $consulta = ConsultaNaFonte::redirecionar($this->saldos->consultaPorMaterial())
            ->orderBy('s.deposito_id')
            ->orderBy('s.material_ah_id');

        foreach ($consulta->cursor() as $linha) {
            yield $this->saldos->linhaParaBi($linha);
        }
    }
}
