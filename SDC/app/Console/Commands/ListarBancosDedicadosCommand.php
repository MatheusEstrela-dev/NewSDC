<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Lista os bancos dedicados de database.bancos_dedicados.
 *
 * Existe para o entrypoint nao duplicar em shell a regra de "banco ligado".
 * Formato `linhas` imprime `conexao|path-das-migrations`, um por linha.
 *
 * Uso: php artisan db:bancos-dedicados --ativos --formato=linhas
 */
class ListarBancosDedicadosCommand extends Command
{
    protected $signature = 'db:bancos-dedicados
                            {--ativos : so os bancos efetivamente ligados neste ambiente}
                            {--formato=tabela : tabela ou linhas}';

    protected $description = 'Lista os bancos dedicados e se estao ligados neste ambiente';

    public function handle(): int
    {
        $registros = [];
        foreach ((array) config('database.bancos_dedicados', []) as $conexao => $dedicado) {
            $ativo = $this->ativo((string) $conexao, (array) $dedicado);
            if ($this->option('ativos') && ! $ativo) {
                continue;
            }
            $registros[] = [$conexao, (string) $dedicado['migrations'], $ativo ? 'sim' : 'nao'];
        }

        if ($this->option('formato') === 'linhas') {
            foreach ($registros as [$conexao, $migrations]) {
                $this->line("{$conexao}|{$migrations}");
            }

            return self::SUCCESS;
        }

        $this->table(['Conexao', 'Migrations', 'Ativo'], $registros);

        return self::SUCCESS;
    }

    /**
     * Com chave, o banco so existe quando a chave aponta para a conexao dele.
     * Sem chave (ranking) e sempre separado e o liga/desliga e o do proprio
     * modulo, em config/{conexao}.php (`habilitado`).
     */
    private function ativo(string $conexao, array $dedicado): bool
    {
        if (empty($dedicado['chave'])) {
            return (bool) config("{$conexao}.habilitado", true);
        }

        return config("database.{$dedicado['chave']}") === $conexao;
    }
}
