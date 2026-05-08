<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Compdec\Services\AnexoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Roda diariamente para detectar anexos legais com data_validade
 * dentro da janela de alerta (config: compdec.anexo_alerta_vencimento_dias).
 *
 * Loga warning no canal compdec-perf com lista de anexos afetados,
 * agrupados por orgao. Notificacoes por email/Slack ficam para iteracao
 * futura (extensao deste command via observers ou queue jobs).
 */
class AlertarAnexosProximosVencimentoCommand extends Command
{
    protected $signature = 'compdec:alertar-anexos-vencimento {--dias= : Janela em dias (default config)}';

    protected $description = 'Alerta sobre anexos legais COMPDEC com vencimento proximo';

    public function handle(AnexoService $service): int
    {
        $dias = $this->option('dias');
        $diasJanela = $dias !== null ? (int) $dias : (int) config('compdec.anexo_alerta_vencimento_dias', 30);

        $anexos = $service->listarProximosDoVencimento($diasJanela);

        if ($anexos->isEmpty()) {
            $this->info("Nenhum anexo legal proximo do vencimento (janela: {$diasJanela} dias).");

            return self::SUCCESS;
        }

        $this->warn("Encontrados {$anexos->count()} anexo(s) com vencimento em ate {$diasJanela} dias:");
        $this->table(
            ['ID', 'Orgao', 'Tipo', 'Titulo', 'Vence em'],
            $anexos->map(fn ($a): array => [
                $a->id,
                $a->orgao?->codigo ?? '?',
                $a->tipo?->value ?? '?',
                $a->titulo,
                $a->data_validade?->toDateString() ?? '?',
            ])->all(),
        );

        Log::channel('compdec-perf')->warning('Anexos proximos do vencimento', [
            'janela_dias' => $diasJanela,
            'total' => $anexos->count(),
            'detalhes' => $anexos->map(fn ($a): array => [
                'id' => $a->id,
                'orgao_id' => $a->orgao_id,
                'orgao_codigo' => $a->orgao?->codigo,
                'tipo' => $a->tipo?->value,
                'titulo' => $a->titulo,
                'data_validade' => $a->data_validade?->toDateString(),
            ])->all(),
        ]);

        return self::SUCCESS;
    }
}
