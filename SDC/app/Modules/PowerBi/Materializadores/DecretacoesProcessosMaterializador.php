<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Materializadores;

use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Decretacoes\Services\ProcessoExportBIService;
use App\Modules\PowerBi\Support\CursorDeMaterializacao;
use App\Modules\PowerBi\Support\DefinicaoTabela;
use App\Modules\PowerBi\Support\GravadorBi;
use App\Modules\PowerBi\Support\JanelaDaFonte;
use App\Modules\PowerBi\Support\MaterializadorBase;
use App\Modules\PowerBi\Support\ResultadoExecucao;
use App\Modules\PowerBi\Support\ResultadoGravacao;
use App\Support\Database\ConexaoPowerBi;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * bi_decretacoes.processos: uma linha por (processo, municipio), no formato do
 * ProcessoExportBIService. Incremental por keyset (updated_at, id) do processo,
 * incluindo soft-deleted (coluna `deletado`). Ao regravar um processo, os
 * municipios que deixaram de existir nele viram removido=true.
 *
 * Limite conhecido: mudanca so nas tabelas de danos, sem tocar o processo,
 * nao avanca updated_at; o ciclo com --desde antigo corrige.
 */
final class DecretacoesProcessosMaterializador extends MaterializadorBase
{
    private const TABELA = 'processos';

    private const COLUNAS_DATA = [
        'data_registro', 'data_fato', 'data_decreto_municipal', 'data_publicacao_mg', 'data_vencimento',
    ];

    public function __construct(GravadorBi $gravador, private readonly ProcessoExportBIService $service)
    {
        parent::__construct($gravador);
    }

    public function modulo(): string
    {
        return 'decretacoes';
    }

    public function materializar(JanelaDaFonte $janela, CursorDeMaterializacao $cursor): ResultadoExecucao
    {
        $definicao = $this->definicao(self::TABELA);
        $estado = $cursor->estado(self::TABELA);
        $desde = $janela->inferior($estado);
        $aposId = null;
        $total = new ResultadoGravacao();
        $lote = (int) config('powerbi.lote', 1000);

        do {
            $processos = $this->paginaDaFonte($desde, $aposId, $lote, $janela);
            if ($processos->isEmpty()) {
                break;
            }

            $ultimo = $processos->last();
            $gravacao = $cursor->emTransacao(function () use ($definicao, $processos, $cursor, $ultimo, $janela): ResultadoGravacao {
                $r = $this->gravarPagina($definicao, $processos);
                $cursor->avancar(self::TABELA, Carbon::instance($ultimo->updated_at), (string) $ultimo->id, $janela);

                return $r;
            });

            $total->lidas += $gravacao->lidas;
            $total->gravadas += $gravacao->gravadas;
            $total->removidas += $gravacao->removidas;
            $desde = Carbon::instance($ultimo->updated_at);
            $aposId = (int) $ultimo->id;
        } while ($processos->count() >= $lote);

        // Sem novidade o cursor nao anda, mas o "dados ate" acompanha a janela.
        $cursor->emTransacao(fn () => $cursor->avancar(self::TABELA, null, null, $janela));

        return (new ResultadoExecucao())->registrar(self::TABELA, $total);
    }

    public function linhasAoVivo(string $tabela): iterable
    {
        $lote = (int) config('powerbi.lote', 1000);
        $desde = null;
        $aposId = null;

        do {
            $processos = $this->paginaDaFonte($desde, $aposId, $lote, null);
            foreach ($this->linhasDoLote($processos) as $linha) {
                yield $linha;
            }
            if ($processos->isNotEmpty()) {
                $desde = Carbon::instance($processos->last()->updated_at);
                $aposId = (int) $processos->last()->id;
            }
        } while ($processos->count() >= $lote);
    }

    /** Pares (processo, municipio) na fonte, incluindo soft-deleted, para o Reconciliador. */
    public function chavesDaFonte(string $tabela): iterable
    {
        $lote = (int) config('powerbi.lote', 1000);
        $desde = null;
        $aposId = null;

        do {
            $processos = $this->paginaDaFonte($desde, $aposId, $lote, null);
            foreach ($processos as $p) {
                $municipios = $p->municipios->pluck('id')->unique();
                foreach ($municipios->isEmpty() ? [0] : $municipios->all() as $municipioId) {
                    yield ['id' => (int) $p->id, 'municipio_id' => (int) $municipioId];
                }
            }
            if ($processos->isNotEmpty()) {
                $desde = Carbon::instance($processos->last()->updated_at);
                $aposId = (int) $processos->last()->id;
            }
        } while ($processos->count() >= $lote);
    }

    /**
     * @return Collection<int, Processo>
     */
    private function paginaDaFonte(?Carbon $desde, ?int $aposId, int $lote, ?JanelaDaFonte $janela): Collection
    {
        // updated_at e timestamp sem fuso, gravado no fuso da app: os limites
        // (que chegam em UTC, vindos do PG) precisam ir no mesmo fuso para nao
        // deslocar a janela em horas.
        $fuso = (string) config('app.timezone');
        $consulta = $this->serviceNaFonte()->consultaIncremental($desde?->copy()->setTimezone($fuso), $aposId, $lote);
        if ($janela !== null) {
            $consulta->where('updated_at', '<=', $janela->superior->copy()->setTimezone($fuso));
        }

        return $consulta->get();
    }

    /**
     * @param Collection<int, Processo> $processos
     */
    private function gravarPagina(DefinicaoTabela $definicao, Collection $processos): ResultadoGravacao
    {
        $linhas = $this->linhasDoLote($processos);
        $resultado = $this->gravador->gravar($definicao, $linhas);
        $resultado->removidas += $this->removerMunicipiosSumidos($definicao, $processos, $linhas);

        return $resultado;
    }

    /**
     * Marca removido os pares (processo, municipio) gravados antes e ausentes
     * da leitura atual dos mesmos processos.
     *
     * @param Collection<int, Processo> $processos
     * @param list<array<string, mixed>> $linhas
     */
    private function removerMunicipiosSumidos(DefinicaoTabela $d, Collection $processos, array $linhas): int
    {
        $vigentes = [];
        foreach ($linhas as $l) {
            $vigentes[$l['id'].'|'.$l['municipio_id']] = true;
        }

        $db = ConexaoPowerBi::escrita();
        $sumidos = [];
        foreach ($processos->pluck('id')->chunk(5000) as $grupo) {
            $in = implode(', ', array_fill(0, $grupo->count(), '?'));
            $gravados = $db->select("SELECT id, municipio_id FROM {$d->alvo()} WHERE NOT removido AND id IN ({$in})", $grupo->all());
            foreach ($gravados as $g) {
                if (! isset($vigentes[$g->id.'|'.$g->municipio_id])) {
                    $sumidos[] = [(int) $g->id, (int) $g->municipio_id];
                }
            }
        }

        $removidas = 0;
        foreach (array_chunk($sumidos, 1000) as $grupo) {
            $pares = implode(', ', array_fill(0, count($grupo), '(?, ?)'));
            $removidas += $db->affectingStatement(
                "UPDATE {$d->alvo()} SET removido = true, materializado_em = now()
                 WHERE NOT removido AND (id, municipio_id) IN ({$pares})",
                array_merge(...$grupo)
            );
        }

        return $removidas;
    }

    /**
     * Linhas do formato de export mais origem_atualizado_em, so colunas do catalogo.
     *
     * @param Collection<int, Processo> $processos
     * @return list<array<string, mixed>>
     */
    private function linhasDoLote(Collection $processos): array
    {
        $atualizadoEm = $processos->mapWithKeys(fn (Processo $p): array => [$p->id => $p->updated_at])->all();
        $linhas = [];
        foreach ($this->serviceNaFonte()->normalizarLote($processos, true) as $linha) {
            $linhas[] = $this->mapear($linha, $atualizadoEm[$linha['id']] ?? null);
        }

        return $linhas;
    }

    /** @return array<string, mixed> */
    private function mapear(array $linha, ?DateTimeInterface $atualizadoEm): array
    {
        $colunas = $this->definicao(self::TABELA)->colunas;
        $saida = array_intersect_key($linha, array_flip($colunas));

        foreach (self::COLUNAS_DATA as $c) {
            if (($saida[$c] ?? null) instanceof DateTimeInterface) {
                $saida[$c] = $saida[$c]->format('Y-m-d');
            }
        }
        $saida['origem_atualizado_em'] = $atualizadoEm;

        return $saida;
    }

    private function serviceNaFonte(): ProcessoExportBIService
    {
        return $this->service->naConexao(ConexaoPowerBi::FONTE);
    }
}
