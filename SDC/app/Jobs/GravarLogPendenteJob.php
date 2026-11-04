<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Support\Database\ConexaoDeLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Retentativa de um log que falhou na conexao de log (pool esgotado, timeout).
 *
 * Despachado por ConexaoDeLog quando a escrita apos o commit falha: o log nunca
 * derruba o modulo e a entrega fica "pelo menos uma vez". Grava cru, direto na
 * tabela, sem passar de novo pela trait UsaConexaoDeLog -- a falha aqui precisa
 * estourar para a fila tentar outra vez, e nao virar outro job.
 *
 * insertOrIgnore: se a escrita original chegou a gravar antes de falhar, a
 * linha com a mesma chave nao duplica.
 *
 * Fila 'auditoria', com consumidor proprio (ver RecordActivityLog).
 */
class GravarLogPendenteJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 5;

    /**
     * @param  list<array<string, mixed>>  $linhas
     */
    public function __construct(
        public readonly string $tabela,
        public readonly array $linhas,
    ) {
        $this->onQueue('auditoria');
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [5, 30, 120, 600];
    }

    public function handle(): void
    {
        ConexaoDeLog::tabela($this->tabela)->insertOrIgnore($this->linhas);
    }
}
