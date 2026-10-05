<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Console;

use App\Modules\Acessos\Enums\CodigoErroDiretorio;
use App\Modules\Acessos\Enums\EstadoOperacaoAd;
use App\Modules\Acessos\Models\OperacaoAd;
use App\Modules\Acessos\Services\EncerraOperacaoComFalha;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Operacoes do AD presas, que nenhum job vai concluir, viram `falhou` com
 * `indisponivel` (mesma trilha do failed() do job):
 * - `enviado` sem progresso alem do limite (worker morreu depois das tentativas,
 *   ou o job sumiu da fila);
 * - `solicitado` alem do limite sem linha na fila (job perdido).
 * `solicitado` com job na fila fica: o worker parado nao expira nada (spec 9.2).
 */
final class ReconciliarOperacoesDiretorioCommand extends Command
{
    protected $signature = 'acessos:diretorio:reconciliar';

    protected $description = 'Encerra como indisponível as operações do AD presas (sem progresso ou sem job na fila).';

    public function handle(EncerraOperacaoComFalha $encerra): int
    {
        $limite = now()->subMinutes((int) config('acessos.diretorio.reconciliar_minutos', 15));

        $candidatas = OperacaoAd::query()
            ->whereIn('estado', [EstadoOperacaoAd::SOLICITADO->value, EstadoOperacaoAd::ENVIADO->value])
            ->where('updated_at', '<', $limite)
            ->orderBy('updated_at')
            ->pluck('id');

        $encerradas = 0;
        foreach ($candidatas as $id) {
            $presa = fn (OperacaoAd $operacao): bool => $this->presa($operacao, $limite);
            if ($encerra->encerrar((string) $id, CodigoErroDiretorio::INDISPONIVEL, $presa)) {
                $encerradas++;
            }
        }

        $this->info(sprintf('Operações do AD encerradas por estarem presas: %d.', $encerradas));

        return self::SUCCESS;
    }

    /** Reavaliada com a linha travada: o worker pode ter avancado no meio. */
    private function presa(OperacaoAd $operacao, CarbonInterface $limite): bool
    {
        if ($operacao->updated_at === null || $operacao->updated_at->gte($limite)) {
            return false;
        }

        return match ($operacao->estado) {
            EstadoOperacaoAd::ENVIADO => true,
            EstadoOperacaoAd::SOLICITADO => ! $this->temJobNaFila((string) $operacao->id),
            default => false,
        };
    }

    /**
     * Procura o uuid no payload da fila `database`. O uuid e validado antes
     * (so hex e hifen, nenhum curinga do LIKE). Fila de outro driver: nao da para
     * saber, entao a operacao nao e tratada como perdida.
     */
    private function temJobNaFila(string $operacaoId): bool
    {
        $conexao = (array) config('queue.connections.'.config('acessos.diretorio.fila.conexao'), []);
        if (($conexao['driver'] ?? null) !== 'database' || ! Str::isUuid($operacaoId)) {
            return true;
        }

        return DB::connection($conexao['connection'] ?? null)
            ->table((string) ($conexao['table'] ?? 'jobs'))
            ->where('payload', 'like', '%'.$operacaoId.'%')
            ->exists();
    }
}
