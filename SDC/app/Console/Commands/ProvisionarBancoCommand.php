<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Cria o banco dedicado (e suas extensoes) quando ainda nao existe.
 *
 * Roda no entrypoint antes do migrate: sem o banco, a migration do modulo
 * dedicado nao tem onde rodar. Idempotente, porque todas as replicas passam por
 * aqui no boot. Nao cria role: o usuario da conexao ja precisa existir.
 *
 * Uso: php artisan db:provisionar pgsql_geo --dry-run
 */
class ProvisionarBancoCommand extends Command implements Isolatable
{
    private const CONEXAO_ADMIN = 'provisionar_admin';

    private const CONEXAO_ALVO = 'provisionar_alvo';

    // O Postgres responde isto quando outra replica criou o banco primeiro.
    private const SQLSTATE_BANCO_JA_EXISTE = '42P04';

    protected $signature = 'db:provisionar
                            {conexao : conexao listada em database.bancos_dedicados}
                            {--dry-run : so imprime o que seria feito}';

    protected $description = 'Cria o banco dedicado e suas extensoes no servidor da conexao, se ainda nao existirem';

    public function handle(): int
    {
        $conexao = (string) $this->argument('conexao');
        $dedicado = config("database.bancos_dedicados.{$conexao}");
        $base = config("database.connections.{$conexao}");

        if (! is_array($dedicado) || ! is_array($base)) {
            $this->error("Conexao '{$conexao}' nao esta em database.bancos_dedicados.");

            return self::FAILURE;
        }

        $banco = (string) $base['database'];
        $extensoes = (array) ($dedicado['extensoes'] ?? []);

        if ($this->option('dry-run')) {
            $this->line("[dry-run] CREATE DATABASE \"{$banco}\" se ausente em {$base['host']}:{$base['port']}");
            foreach ($extensoes as $extensao) {
                $this->line("[dry-run] CREATE EXTENSION IF NOT EXISTS \"{$extensao}\" em {$banco}");
            }

            return self::SUCCESS;
        }

        try {
            $this->criarBancoSeAusente($base, $banco);
            $this->criarExtensoes($base, $extensoes);
        } catch (Throwable $e) {
            $this->error("Falha ao provisionar '{$banco}': {$e->getMessage()}");

            return self::FAILURE;
        } finally {
            DB::purge(self::CONEXAO_ADMIN);
            DB::purge(self::CONEXAO_ALVO);
        }

        return self::SUCCESS;
    }

    /**
     * Admin no servidor, no banco de manutencao `postgres`. Conexao runtime e
     * direta: o PgBouncer so conhece o banco critico e nao aceita CREATE DATABASE.
     */
    private function criarBancoSeAusente(array $base, string $banco): void
    {
        $admin = $this->registrar(self::CONEXAO_ADMIN, $base, 'postgres', $this->credenciaisAdmin());
        $existe = $admin->selectOne('SELECT 1 AS ok FROM pg_database WHERE datname = ?', [$banco]);

        if ($existe !== null) {
            $this->info("Banco '{$banco}' ja existe.");

            return;
        }

        $nome = $admin->getQueryGrammar()->wrap($banco);

        try {
            $admin->statement("CREATE DATABASE {$nome} WITH TEMPLATE template0 ENCODING 'UTF8'");
            $this->info("Banco '{$banco}' criado.");
        } catch (QueryException $e) {
            if (! $this->bancoJaExiste($e)) {
                throw $e;
            }
            $this->info("Banco '{$banco}' ja criado por outra replica.");
        }
    }

    private function criarExtensoes(array $base, array $extensoes): void
    {
        if ($extensoes === []) {
            return;
        }

        $alvo = $this->registrar(self::CONEXAO_ALVO, $base, (string) $base['database'], []);

        foreach ($extensoes as $extensao) {
            $nome = $alvo->getQueryGrammar()->wrap((string) $extensao);
            $alvo->statement("CREATE EXTENSION IF NOT EXISTS {$nome}");
            $this->info("Extensao '{$extensao}' garantida.");
        }
    }

    private function registrar(string $nome, array $base, string $banco, array $credenciais): Connection
    {
        config(["database.connections.{$nome}" => array_merge($base, $credenciais, ['database' => $banco])]);

        return DB::connection($nome);
    }

    /**
     * Credencial de admin opcional por modulo (ex.: GEO_DB_ADMIN_USERNAME);
     * sem ela vale a da propria conexao, que no dev e o dono do cluster.
     */
    private function credenciaisAdmin(): array
    {
        $prefixo = strtoupper(preg_replace('/^pgsql_/', '', (string) $this->argument('conexao')));
        $usuario = env("{$prefixo}_DB_ADMIN_USERNAME");
        $senha = env("{$prefixo}_DB_ADMIN_PASSWORD");

        return array_filter([
            'username' => $usuario ?: null,
            'password' => $senha ?: null,
        ], static fn ($valor) => $valor !== null);
    }

    private function bancoJaExiste(QueryException $e): bool
    {
        return ($e->errorInfo[0] ?? $e->getPrevious()?->getCode()) === self::SQLSTATE_BANCO_JA_EXISTE;
    }
}
