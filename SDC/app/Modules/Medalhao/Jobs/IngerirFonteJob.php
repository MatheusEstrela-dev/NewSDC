<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Jobs;

use App\Modules\Medalhao\Models\IngestaoBruta;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use App\Modules\Medalhao\Support\ExecucaoDeColeta;
use App\Modules\Medalhao\Support\TabelasGeo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class IngerirFonteJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $timeout = 300;

    public int $tries = 3;

    public function __construct(public readonly string $chave)
    {
        $this->onQueue('medalhao');
    }

    public function handle(IngestorRegistry $registry, ExecucaoDeColeta $execucoes): void
    {
        $ingestor = $registry->ingestor($this->chave);
        $dominio = TabelasGeo::dominioDoGrupo($ingestor->grupo());
        $execucaoId = $execucoes->iniciar($dominio, $this->chave);

        try {
            $payload = $ingestor->coletar();

            if (trim($payload->conteudo) === '') {
                Log::info('medalhao: coleta sem conteudo', ['fonte' => $this->chave]);
                $execucoes->encerrar($dominio, $execucaoId, ExecucaoDeColeta::FALHA, mensagem: 'coleta sem conteudo');

                return;
            }

            $hash = $payload->hash();

            // Marca a verificacao ANTES de decidir sobre o conteudo: a fonte foi
            // consultada com sucesso, e isso vale registrar mesmo que nada tenha
            // mudado. Sem esta linha, um coletor saudavel que nao encontra novidade
            // e indistinguivel de um coletor morto -- que era o caso dos sismos,
            // onde "sem evento novo" e a resposta certa na maioria dos ciclos.
            $atualizadas = IngestaoBruta::doDominio($dominio)->newQuery()
                ->where('fonte', $this->chave)
                ->where('hash_conteudo', $hash)
                ->update(['verificado_em' => now()]);

            if ($atualizadas > 0) {
                Log::info('medalhao: conteudo identico ao anterior, ignorado', ['fonte' => $this->chave]);
                $execucoes->encerrar($dominio, $execucaoId, ExecucaoDeColeta::SEM_MUDANCA, hash: $hash);

                return;
            }

            $bronze = IngestaoBruta::doDominio($dominio)->newQuery()->create([
                'fonte' => $this->chave,
                'conteudo_bruto' => $payload->conteudo,
                'formato' => $payload->formato,
                'hash_conteudo' => $hash,
                'meta' => $payload->meta,
                'coletado_em' => now(),
                'verificado_em' => now(),
            ]);
        } catch (Throwable $e) {
            $execucoes->encerrar($dominio, $execucaoId, ExecucaoDeColeta::FALHA, erro: $e);

            throw $e;
        }

        // A execucao fecha no NormalizarSilverJob, que sabe quantos registros
        // foram para o silver.
        NormalizarSilverJob::dispatch((int) $bronze->id, $this->chave, $execucaoId);
    }
}
