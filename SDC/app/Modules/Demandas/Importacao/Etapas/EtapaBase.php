<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao\Etapas;

use App\Modules\Demandas\Importacao\EtapaImportacao;
use App\Modules\Demandas\Importacao\MapaImportacao;
use App\Modules\Demandas\Importacao\MapaLegado;
use App\Modules\Demandas\Importacao\RelatorioEtapa;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

abstract class EtapaBase implements EtapaImportacao
{
    public function __construct(protected readonly MapaImportacao $mapa) {}

    /** Tabela de origem no cedec-demanda. */
    abstract protected function tabelaOrigem(): string;

    /** Tabela de destino no NewSDC, gravada no mapa. */
    abstract protected function tabelaDestino(): string;

    /**
     * Grava UMA linha. Devolve o id de destino, ou string com o motivo da
     * rejeicao. Recebe o id ja mapeado quando e atualizacao.
     */
    abstract protected function gravar(object $linha, ?int $destinoExistente): int|string;

    public function executar(bool $dryRun, ?string $desde, int $lote): RelatorioEtapa
    {
        $relatorio = new RelatorioEtapa($this->nome());

        $this->consulta($desde)->chunkById($lote, function ($linhas) use ($dryRun, $relatorio): void {
            // Um lote = uma transacao no destino. Em dry-run a transacao sempre
            // termina em rollback: o relatorio sai completo e nada fica gravado.
            DB::beginTransaction();
            try {
                foreach ($linhas as $linha) {
                    $this->processar($linha, $relatorio);
                }
                $dryRun ? DB::rollBack() : DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        });

        return $relatorio;
    }

    protected function processar(object $linha, RelatorioEtapa $relatorio): void
    {
        $relatorio->lidos++;
        $id = (string) $linha->id;
        $hash = MapaLegado::hash($linha);
        if ($this->mapa->hash($this->tabelaOrigem(), $id) === $hash) {
            $relatorio->ignorados++;

            return;
        }

        $existente = $this->mapa->alvo($this->tabelaOrigem(), $id);
        $resultado = $this->gravar($linha, $existente);
        if (is_string($resultado)) {
            $relatorio->rejeitar($resultado);
            $this->mapa->registrar($this->tabelaOrigem(), $id, $this->tabelaDestino(), null, $hash, 'rejeitado', $resultado);

            return;
        }

        $existente === null ? $relatorio->importados++ : $relatorio->atualizados++;
        $this->mapa->registrar($this->tabelaOrigem(), $id, $this->tabelaDestino(), $resultado, $hash, 'importado');
    }

    protected function consulta(?string $desde): Builder
    {
        $q = $this->origem()->table($this->tabelaOrigem());
        if ($desde !== null && $this->temUpdatedAt()) {
            $q->where('updated_at', '>=', $desde);
        }

        return $q;
    }

    protected function temUpdatedAt(): bool
    {
        return true;
    }

    protected function origem(): ConnectionInterface
    {
        return DB::connection((string) config('demandas.importacao.conexao'));
    }
}
