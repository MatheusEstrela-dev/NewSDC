<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Permissoes\RelatorioDeSincronizacao;
use App\Support\Permissoes\SincronizadorDePermissoes;
use Illuminate\Console\Command;

/**
 * Garante no banco o que config/permissions.php declara. Ao fim de todo
 * `migrate` roda sozinho no modo somente novas (SincronizaPermissoesAposMigrations);
 * este comando e a acao explicita do administrador: sem --somente-novas
 * reconcede todo par do config que falta, inclusive o revogado a mao.
 * Nunca revoga nada.
 */
final class SincronizarPermissoesCommand extends Command
{
    protected $signature = 'permissions:sincronizar
        {--simular : mostra o que faria sem gravar}
        {--somente-novas : modo automatico do migrate: so concede par de permissao ou cargo criado agora}';

    protected $description = 'Cria permissoes e cargos do config/permissions.php ausentes e concede os pares do config que faltam, '
        .'REVOGADOS A MAO INCLUSIVE (use --somente-novas para nao reconceder). Super-admin recebe toda permissao nao excluida. Nunca revoga.';

    public function handle(SincronizadorDePermissoes $sincronizador): int
    {
        $simular = (bool) $this->option('simular');
        $somenteNovas = (bool) $this->option('somente-novas');

        $this->components->info($simular
            ? 'Simulacao da sincronizacao de permissoes - nada sera gravado'
            : 'Sincronizacao de permissoes com config/permissions.php');

        $relatorio = $sincronizador->sincronizar($simular, $somenteNovas);

        $this->renderizarResumo($relatorio);
        $this->renderizarDetalhes($relatorio);
        $this->newLine();

        if ($relatorio->semMudancas()) {
            $this->components->info('Nada a gravar: banco ja tem o que o config declara neste modo.');
        } elseif ($simular) {
            $this->components->warn('Simulacao concluida: rode sem --simular para gravar.');
        } else {
            $this->components->info('Sincronizacao concluida.');
        }

        return Command::SUCCESS;
    }

    private function renderizarResumo(RelatorioDeSincronizacao $relatorio): void
    {
        $verbo = $relatorio->simulado ? 'a criar' : 'criadas';

        $this->components->twoColumnDetail('Modo', $relatorio->somenteNovas
            ? 'somente novas (o do migrate: nao reconcede revogacao manual)'
            : 'completo (reconcede todo par do config, revogados a mao inclusive)');
        $this->components->twoColumnDetail("Permissoes {$verbo}", (string) $relatorio->totalPermissoesCriadas());
        $this->components->twoColumnDetail('Permissoes com colunas vazias completadas', (string) count($relatorio->permissoesCompletadas));
        $this->components->twoColumnDetail("Cargos {$verbo}", (string) $relatorio->totalCargosCriados());
        $this->components->twoColumnDetail('Cargos sem slug que recebem o slug', (string) count($relatorio->cargosCompletados));
        $this->components->twoColumnDetail('Cargos em conflito (sem concessao)', (string) count($relatorio->cargosEmConflito));
        $this->components->twoColumnDetail('Concessoes novas (cargo -> permissao)', (string) $relatorio->totalConcessoesNovas());
        $this->components->twoColumnDetail('Concessoes pendentes (nao concedidas neste modo)', (string) $relatorio->totalConcessoesPendentes());
        $this->components->twoColumnDetail('Permissoes excluidas a mao (mantidas excluidas)', (string) count($relatorio->permissoesExcluidas));
    }

    private function renderizarDetalhes(RelatorioDeSincronizacao $relatorio): void
    {
        $this->listar('Permissoes novas', $relatorio->permissoesCriadas);
        $this->listar('Permissoes completadas', $relatorio->permissoesCompletadas);
        $this->listar('Cargos novos', $relatorio->cargosCriados);
        $this->listar('Cargos que recebem o slug', $relatorio->cargosCompletados);
        $this->listar('Cargos em conflito', array_map(
            static fn (string $slug, string $motivo): string => "{$slug}: {$motivo}",
            array_keys($relatorio->cargosEmConflito),
            array_values($relatorio->cargosEmConflito),
        ));
        $this->listar('Excluidas a mao, sem restaurar', $relatorio->permissoesExcluidas);

        foreach ($relatorio->concessoesNovas as $cargo => $slugs) {
            $this->listar("Concessoes novas para {$cargo}", $slugs);
        }
        foreach ($relatorio->concessoesPendentes as $cargo => $slugs) {
            $this->listar("Concessoes pendentes para {$cargo}", $slugs);
        }
    }

    /** @param  list<string>  $itens */
    private function listar(string $titulo, array $itens): void
    {
        if ($itens === []) {
            return;
        }

        $this->newLine();
        $this->line("  <fg=yellow>{$titulo}</> (".count($itens).')');
        $this->components->bulletList($itens);
    }
}
