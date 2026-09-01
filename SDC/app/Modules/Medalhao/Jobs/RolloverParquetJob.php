<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Jobs;

use App\Modules\Medalhao\Contracts\ArquivadorBronze;
use App\Modules\Medalhao\Models\IngestaoBruta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class RolloverParquetJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $timeout = 900;

    // Sem retry: se a escrita falhou, o Bronze esta intacto e a proxima execucao
    // agendada tenta de novo. Retry automatico aqui so multiplicaria I/O.
    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue('medalhao');
    }

    public function handle(ArquivadorBronze $arquivador): void
    {
        $corte = Carbon::now()->subDays((int) config('medalhao.retencao_dias'));

        $grupos = IngestaoBruta::query()
            ->where('coletado_em', '<', $corte)
            ->selectRaw('fonte, date(coletado_em) AS dia')
            ->groupBy('fonte')
            ->groupByRaw('date(coletado_em)')
            ->get();

        foreach ($grupos as $grupo) {
            $dia = Carbon::parse($grupo->dia);

            $registros = IngestaoBruta::query()
                ->where('fonte', $grupo->fonte)
                ->whereRaw('date(coletado_em) = ?', [$dia->toDateString()])
                ->orderBy('id')
                ->get();

            if ($registros->isEmpty()) {
                continue;
            }

            // Sem 'fonte': ela e a chave da particao no caminho, e repeti-la
            // dentro do arquivo quebra a leitura do dataset no pyarrow. Ver o
            // schema() do FlowParquetArquivador.
            $linhas = $registros->map(static fn (IngestaoBruta $r): array => [
                'id' => (int) $r->id,
                'conteudo_bruto' => (string) $r->conteudo_bruto,
                'formato' => (string) $r->formato,
                'hash_conteudo' => (string) $r->hash_conteudo,
                'meta' => json_encode($r->meta ?? [], JSON_UNESCAPED_UNICODE),
                'coletado_em' => (string) $r->coletado_em?->toIso8601String(),
                'processado_em' => (string) $r->processado_em?->toIso8601String(),
            ])->all();

            // Escreve e verifica ANTES de podar. Se qualquer coisa falhar, a
            // excecao sobe e o Bronze permanece intacto — nunca se apaga bruto
            // cujo arquivo nao foi confirmado.
            $caminho = $arquivador->arquivar($grupo->fonte, $dia, $linhas);

            IngestaoBruta::query()->whereIn('id', $registros->pluck('id'))->delete();

            Log::info('medalhao: bronze arquivado em parquet', [
                'fonte' => $grupo->fonte,
                'dia' => $dia->toDateString(),
                'registros' => $registros->count(),
                'arquivo' => $caminho,
            ]);
        }
    }
}
