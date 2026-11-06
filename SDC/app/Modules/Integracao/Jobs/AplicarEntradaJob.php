<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Jobs;

use App\Modules\Integracao\Support\ProcessadorDeEntrada;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Aplica uma mensagem da caixa de entrada. Leva so o id: o payload fica
 * cifrado no banco, nunca no Redis.
 *
 * Uma tentativa por job: retentativa, backoff e dead-letter sao da caixa
 * (entrada.mensagens), e o integracao:reenfileirar redespacha.
 */
final class AplicarEntradaJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 1;

    /** Abaixo do --timeout=120 do consumidor e do lease_segundos. */
    public int $timeout = 110;

    public function __construct(public readonly string $mensagemId)
    {
        $this->onConnection((string) config('integracao.fila.conexao', 'redis-integracao'));
        $this->onQueue((string) config('integracao.fila.nome', 'integracao'));
    }

    public static function despachar(string $mensagemId): void
    {
        self::dispatch($mensagemId);
    }

    public function handle(ProcessadorDeEntrada $processador): void
    {
        $processador->processar($this->mensagemId);
    }
}
