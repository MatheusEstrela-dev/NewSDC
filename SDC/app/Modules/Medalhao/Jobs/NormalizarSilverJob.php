<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Jobs;

use App\Modules\Medalhao\DTOs\PayloadBruto;
use App\Modules\Medalhao\Models\IngestaoBruta;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use App\Modules\Sismos\Jobs\AtualizarGoldSismosJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class NormalizarSilverJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $timeout = 600;

    public int $tries = 3;

    public function __construct(
        public readonly int $ingestaoId,
        public readonly string $chave,
    ) {
        $this->onQueue('medalhao');
    }

    public function handle(IngestorRegistry $registry): void
    {
        $bronze = IngestaoBruta::findOrFail($this->ingestaoId);
        $grupo = $registry->ingestor($this->chave)->grupo();

        $dtos = $registry->normalizador($this->chave)->normalizar(
            new PayloadBruto($bronze->conteudo_bruto, $bronze->formato, $bronze->meta ?? [])
        );

        $total = $this->persistidor($grupo)->upsertLote($dtos, $this->ingestaoId);

        $bronze->update(['processado_em' => now()]);

        Log::info('medalhao: silver atualizado', [
            'fonte' => $this->chave,
            'registros' => $total,
        ]);

        if ($grupo === 'sismos') {
            AtualizarGoldSismosJob::dispatch();
        }
    }

    /**
     * O kernel nao conhece dominio: o mapa grupo -> persistidor vem de config,
     * e o contrato esperado e upsertLote(iterable, ?int): int.
     */
    private function persistidor(string $grupo): object
    {
        $classe = config("medalhao.persistidores.{$grupo}")
            ?? throw new RuntimeException("Sem persistidor configurado para o grupo: {$grupo}");

        return app($classe);
    }
}
