<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\Models\Movimentacao;
use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Shared\Support\CsvSeguro;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Planilha do lote no formato que a SEPLAG ja recebia do cedec-demanda: um item
 * por equipamento remanejado (liberacoes nao entram). Toda celula e texto e
 * passa por CsvSeguro: o arquivo sai do sistema e e aberto no Excel de terceiros.
 */
final class PlanilhaRemanejamento
{
    public const MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public const CABECALHO = [
        'Tipo de dispositivo', 'Ramal', 'Patrimônio', 'Número de série', 'Origem',
        'Ponto de rede origem', 'Origem ficará vazia', 'Destino', 'Ponto de rede destino', 'Condição do destino',
    ];

    private const SEM_ESTACAO = 'ESTOQUE';

    private const CONDICAO_PADRAO = 'VAZIA';

    /** @return list<list<string>> */
    public function linhas(Remanejamento $lote): array
    {
        $lote->load([
            'itensRemanejados.equipamento' => static fn ($q) => $q->withTrashed(),
            'itensRemanejados.equipamento.categoria:id,nome',
            'itensRemanejados.pessoa.estacaoOrigem:id,nome,ponto_rede',
            'itensRemanejados.pessoa.estacaoDestino:id,nome,ponto_rede',
        ]);

        return $lote->itensRemanejados->sortBy('id')->map(static function (Movimentacao $item): array {
            $equipamento = $item->equipamento;
            $pessoa = $item->pessoa;

            return [
                // Tipo = categoria (spec 4.5); sem categoria, o nome do equipamento.
                (string) ($equipamento?->categoria?->nome ?? $equipamento?->nome ?? '-'),
                (string) ($equipamento?->ramal ?? ''),
                (string) ($equipamento?->patrimonio ?? ''),
                (string) ($equipamento?->numero_serie ?? ''),
                (string) ($pessoa?->estacaoOrigem?->nome ?? self::SEM_ESTACAO),
                (string) ($pessoa?->estacaoOrigem?->ponto_rede ?? ''),
                $pessoa?->estacao_origem_ficou_vazia === false ? 'NÃO' : 'SIM',
                (string) ($pessoa?->estacaoDestino?->nome ?? self::SEM_ESTACAO),
                (string) ($pessoa?->estacaoDestino?->ponto_rede ?? ''),
                (string) ($pessoa?->condicao_destino ?? self::CONDICAO_PADRAO),
            ];
        })->values()->all();
    }

    public function gerar(Remanejamento $lote): string
    {
        $caminho = tempnam(sys_get_temp_dir(), 'remanejamento_');
        try {
            $writer = new Writer();
            $writer->openToFile($caminho);
            $writer->addRow($this->linha(self::CABECALHO));
            foreach ($this->linhas($lote) as $linha) {
                $writer->addRow($this->linha($linha));
            }
            $writer->close();

            return (string) file_get_contents($caminho);
        } finally {
            if (is_file($caminho)) {
                unlink($caminho);
            }
        }
    }

    public function nomeArquivo(Remanejamento $lote): string
    {
        return 'Remanejamento_'.$lote->created_at->format('Ymd_His').'.xlsx';
    }

    /** @param list<string> $valores */
    private function linha(array $valores): Row
    {
        return new Row(array_map(
            static fn (string $valor): StringCell => new StringCell(CsvSeguro::celula($valor), null),
            $valores,
        ));
    }
}
