<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Permissoes\RelatorioDeSincronizacao;
use App\Support\Permissoes\SincronizadorDePermissoes;
use Illuminate\Console\Command;

/**
 * Garante no banco o que config/permissions.php declara. Roda sozinho ao fim
 * de todo `migrate` (SincronizaPermissoesAposMigrations); este comando e para
 * conferir com --simular ou reaplicar a mao. So acrescenta, nunca revoga.
 */
final class SincronizarPermissoesCommand extends Command
{
    protected $signature = 'permissions:sincronizar
        {--simular : mostra o que faria sem gravar}';

    protected $description = 'Cria permissoes e cargos do config/permissions.php ausentes no banco e concede os pares que faltam, sem revogar nada';

    public function handle(SincronizadorDePermissoes $sincronizador): int
    {
        $simular = (bool) $this->option('simular');

        $this->components->info($simular
            ? 'Simulacao da sincronizacao de permissoes - nada sera gravado'
            : 'Sincronizacao de permissoes com config/permissions.php');

        $relatorio = $sincronizador->sincronizar($simular);

        $this->renderizarResumo($relatorio);
        $this->renderizarDetalhes($relatorio);
        $this->newLine();

        if ($relatorio->semMudancas()) {
            $this->components->info('Banco ja estava sincronizado com o config.');
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

        $this->components->twoColumnDetail("Permissoes {$verbo}", (string) $relatorio->totalPermissoesCriadas());
        $this->components->twoColumnDetail('Permissoes com colunas vazias completadas', (string) count($relatorio->permissoesCompletadas));
        $this->components->twoColumnDetail("Cargos {$verbo}", (string) $relatorio->totalCargosCriados());
        $this->components->twoColumnDetail('Concessoes novas (cargo -> permissao)', (string) $relatorio->totalConcessoesNovas());
        $this->components->twoColumnDetail('Permissoes excluidas a mao (mantidas excluidas)', (string) count($relatorio->permissoesExcluidas));
    }

    private function renderizarDetalhes(RelatorioDeSincronizacao $relatorio): void
    {
        $this->listar('Permissoes novas', $relatorio->permissoesCriadas);
        $this->listar('Permissoes completadas', $relatorio->permissoesCompletadas);
        $this->listar('Cargos novos', $relatorio->cargosCriados);
        $this->listar('Excluidas a mao, sem restaurar', $relatorio->permissoesExcluidas);

        foreach ($relatorio->concessoesNovas as $cargo => $slugs) {
            $this->listar("Concessoes novas para {$cargo}", $slugs);
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
