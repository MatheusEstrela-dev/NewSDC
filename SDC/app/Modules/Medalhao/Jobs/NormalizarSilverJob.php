<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Jobs;

use App\Modules\Medalhao\DTOs\PayloadBruto;
use App\Modules\Medalhao\Models\IngestaoBruta;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use App\Modules\Medalhao\Support\ExecucaoDeColeta;
use App\Modules\Medalhao\Support\TabelasGeo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

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
        // Upload de KML nao passa por coleta agendada, entao nao tem execucao.
        public readonly ?int $execucaoId = null,
    ) {
        $this->onQueue('medalhao');
    }

    public function handle(IngestorRegistry $registry, ExecucaoDeColeta $execucoes): void
    {
        // Pelo registry, e nao por ingestor()->grupo(): fonte recebida por
        // upload nao tem ingestor, e perguntar o grupo ao coletor quebrava a
        // normalizacao inteira dela.
        $grupo = $registry->grupo($this->chave);
        $dominio = TabelasGeo::dominioDoGrupo($grupo);

        $bronze = IngestaoBruta::doDominio($dominio)->newQuery()->findOrFail($this->ingestaoId);

        $dtos = $registry->normalizador($this->chave)->normalizar(
            new PayloadBruto($bronze->conteudo_bruto, $bronze->formato, $bronze->meta ?? [])
        );

        $total = $this->persistidor($grupo)->upsertLote($dtos, $this->ingestaoId);

        $bronze->update(['processado_em' => now()]);

        $execucoes->encerrar($dominio, $this->execucaoId, ExecucaoDeColeta::SUCESSO, $total, $bronze->hash_conteudo);

        Log::info('medalhao: silver atualizado', [
            'fonte' => $this->chave,
            'registros' => $total,
        ]);

        // O job de refresh vem de config, nao de um if por dominio: fonte nova
        // se registra sem editar o kernel. Grupo sem entrada nao despacha nada.
        $jobGold = config("medalhao.refresh_gold.{$grupo}");

        if ($jobGold !== null) {
            $jobGold::dispatch();
        }
    }

    /** Esgotadas as tentativas, a execucao registra o erro em vez de ficar aberta. */
    public function failed(Throwable $e): void
    {
        if ($this->execucaoId === null) {
            return;
        }

        $dominio = TabelasGeo::dominioDoGrupo(app(IngestorRegistry::class)->grupo($this->chave));

        app(ExecucaoDeColeta::class)->encerrar(
            $dominio,
            $this->execucaoId,
            ExecucaoDeColeta::FALHA,
            erro: $e,
        );
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
