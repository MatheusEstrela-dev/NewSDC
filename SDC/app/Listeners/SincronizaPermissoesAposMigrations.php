<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Support\Permissoes\SincronizadorDePermissoes;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\NoPendingMigrations;
use Symfony\Component\Console\Input\ArgvInput;
use Throwable;

/**
 * Todo `migrate` termina com o config/permissions.php garantido no banco, sem
 * depender do seeder (o entrypoint so semeia com SEED_MOCK_DATA=true).
 *
 * Escuta tambem NoPendingMigrations: deploy que so mexe no config, sem
 * migration nova, nao dispara MigrationsEnded. Rollback (`down`) nao
 * sincroniza, para nao recriar o que o down acabou de remover.
 *
 * `--pretend` nao grava. MigrationsEnded traz a opcao; NoPendingMigrations
 * nao, entao ali vale a linha de comando do processo. CommandStarting nao
 * serve: o kernel nao o dispara com APP_ENV=testing.
 *
 * Com `migrate --isolated` quem nao pega o lock sai antes do handle do
 * comando e o migrator nao dispara evento nenhum, entao nao sincroniza. A
 * replica que chega depois do lock liberado roda sem pendencias e sincroniza
 * de novo, sem efeito: a sincronizacao e idempotente e segura em paralelo.
 *
 * Modo somente novas: o entrypoint roda migrate a cada boot de container, e
 * reconceder todo par do config desfaria a revogacao feita a mao no
 * Permissionamento. Aqui so ganha concessao a permissao ou o cargo que nasceu
 * nesta execucao; o resto fica pendente para o `permissions:sincronizar` manual.
 *
 * Falha e reportada e engolida: permissao fora de sincronia nao pode
 * derrubar o deploy, e `permissions:sincronizar` refaz depois.
 */
final class SincronizaPermissoesAposMigrations
{
    public function __construct(
        private readonly SincronizadorDePermissoes $sincronizador,
    ) {}

    public function aoTerminarMigrations(MigrationsEnded $evento): void
    {
        if ($evento->method === 'up' && ! ($evento->options['pretend'] ?? false)) {
            $this->sincronizar();
        }
    }

    public function semMigrationsPendentes(NoPendingMigrations $evento): void
    {
        if ($evento->method === 'up' && ! (new ArgvInput())->hasParameterOption('--pretend', true)) {
            $this->sincronizar();
        }
    }

    private function sincronizar(): void
    {
        try {
            if ($this->sincronizador->tabelasExistem()) {
                $this->sincronizador->sincronizar(somenteNovas: true);
            }
        } catch (Throwable $erro) {
            report($erro);
        }
    }
}
