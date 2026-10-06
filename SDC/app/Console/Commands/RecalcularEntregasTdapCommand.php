<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Tdap\Models\CronoCaminhao;
use App\Modules\Tdap\Services\CronoCaminhaoService;
use App\Modules\Tdap\Services\HistoricoService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Realinha `tdap_crono_caminhoes.agua_entregue`/`vr_total` com as viagens.
 *
 * As colunas sao derivadas (CronoCaminhaoService::entregaCalculada), mas parte
 * do acervo veio do legado com valor proprio: em out/2026, 246 de 906 alocacoes
 * de cronogramas ativos divergiam -- inclusive com m3 gravado e nenhuma viagem
 * aprovada. A listagem de Cronogramas le a coluna; o dashboard soma as
 * viagens; os dois numeros nao batiam.
 *
 * Sem --aplicar so mostra o que mudaria. Com --aplicar grava e deixa um evento
 * `cronograma.entregas_recalculadas` no historico de cada cronograma, com o
 * antes e o depois de cada alocacao -- o valor do legado nao se perde.
 */
class RecalcularEntregasTdapCommand extends Command
{
    protected $signature = 'tdap:recalcular-entregas
                            {--aplicar : Grava a correcao (sem a opcao, apenas simula)}
                            {--cronograma= : Restringe a um cronograma (id)}';

    protected $description = 'Recalcula agua_entregue e vr_total das alocacoes do TDAP a partir das viagens aprovadas.';

    /** Abaixo de meio centavo/meio litro e arredondamento, nao divergencia. */
    private const TOLERANCIA = 0.005;

    public function handle(CronoCaminhaoService $alocacoes, HistoricoService $historico): int
    {
        $divergentes = $this->divergentes($alocacoes);

        if ($divergentes->isEmpty()) {
            $this->info('Nenhuma alocacao divergente.');

            return self::SUCCESS;
        }

        $this->table(
            ['Alocacao', 'Cronograma', 'm3 gravado', 'm3 calculado', 'R$ gravado', 'R$ calculado'],
            $divergentes->map(fn (array $d) => [
                $d['alocacao']->id,
                $d['alocacao']->cronograma?->numero ?? $d['alocacao']->cronograma_id,
                $d['antes']['agua_entregue'],
                $d['depois']['agua_entregue'],
                $d['antes']['vr_total'],
                $d['depois']['vr_total'],
            ])->all(),
        );

        $this->line(sprintf(
            '%d alocacao(oes) divergente(s): %s m3 gravados contra %s m3 calculados.',
            $divergentes->count(),
            number_format($divergentes->sum('antes.agua_entregue'), 2, ',', '.'),
            number_format($divergentes->sum('depois.agua_entregue'), 2, ',', '.'),
        ));

        if (! $this->option('aplicar')) {
            $this->warn('Simulacao: nada foi gravado. Rode com --aplicar para corrigir.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($divergentes, $historico): void {
            foreach ($divergentes->groupBy(fn (array $d) => $d['alocacao']->cronograma_id) as $doCronograma) {
                // saveQuietly: em massa, um aviso de tempo real por alocacao
                // seriam centenas; o historico abaixo, um por cronograma, ja
                // avisa o dashboard (DashboardTempoRealObserver).
                foreach ($doCronograma as $d) {
                    $d['alocacao']->forceFill($d['depois'])->saveQuietly();
                }

                $cronograma = $doCronograma->first()['alocacao']->cronograma;

                if ($cronograma !== null) {
                    $historico->registrar(
                        tipoEvento: 'cronograma.entregas_recalculadas',
                        entity: $cronograma,
                        obs: "Água entregue de {$doCronograma->count()} alocação(ões) recalculada pelas viagens aprovadas.",
                        payload: ['alocacoes' => $doCronograma->map(fn (array $d) => [
                            'crono_caminhao_id' => $d['alocacao']->id,
                            'antes'             => $d['antes'],
                            'depois'            => $d['depois'],
                        ])->values()->all()],
                    );
                }
            }
        });

        $this->info('Correcao aplicada.');

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, array{alocacao: CronoCaminhao, antes: array{agua_entregue: float, vr_total: float}, depois: array{agua_entregue: float, vr_total: float}}>
     */
    private function divergentes(CronoCaminhaoService $alocacoes): Collection
    {
        return CronoCaminhao::query()
            ->with([...CronoCaminhaoService::relacoesDaEntrega(), 'cronograma:id,numero,lote_id'])
            ->withCount('viagensValidadas')
            // Cronograma excluido fica como esta: sem ele o lote some e o R$ zeraria.
            ->whereHas('cronograma')
            ->when($this->option('cronograma'), fn ($q, $id) => $q->where('cronograma_id', (int) $id))
            ->orderBy('id')
            ->lazy()
            ->map(fn (CronoCaminhao $cc) => [
                'alocacao' => $cc,
                'antes'    => ['agua_entregue' => (float) $cc->agua_entregue, 'vr_total' => (float) $cc->vr_total],
                'depois'   => $alocacoes->entregaCalculada($cc),
            ])
            ->filter(fn (array $d) => abs($d['antes']['agua_entregue'] - $d['depois']['agua_entregue']) >= self::TOLERANCIA
                || abs($d['antes']['vr_total'] - $d['depois']['vr_total']) >= self::TOLERANCIA)
            ->values()
            ->collect();
    }
}
