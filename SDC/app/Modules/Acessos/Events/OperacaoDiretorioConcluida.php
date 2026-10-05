<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * A operacao no AD chegou a um estado final (confirmado ou falhou). Leva so o
 * id (serializavel para listener em fila): quem escuta rele a operacao.
 * Disparado no worker, depois do commit, sempre por notificar().
 */
final class OperacaoDiretorioConcluida
{
    use Dispatchable;

    public function __construct(public readonly string $operacaoId) {}

    /**
     * Aviso pos-commit: a operacao ja esta final e nao depende de quem escuta.
     * Falha de um ouvinte (ou do enfileiramento dele) nunca volta para o job
     * do AD; fica no log so a classe e a origem, nunca a mensagem.
     */
    public static function notificar(string $operacaoId): void
    {
        try {
            self::dispatch($operacaoId);
        } catch (Throwable $erro) {
            Log::warning('acessos.diretorio.notificacao_falhou', [
                'operacao_id' => $operacaoId,
                'excecao' => $erro::class,
                'origem' => basename($erro->getFile()).':'.$erro->getLine(),
            ]);
        }
    }
}
