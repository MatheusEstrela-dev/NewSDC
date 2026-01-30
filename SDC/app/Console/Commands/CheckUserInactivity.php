<?php

namespace App\Console\Commands;

use App\Actions\Auth\DeactivateInactiveUsers;
use Illuminate\Console\Command;

class CheckUserInactivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sdc:check-user-inactivity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica e desativa usuários sem atualização cadastral há 6 meses';

    /**
     * Execute the console command.
     */
    public function handle(DeactivateInactiveUsers $action): void
    {
        $this->info('Iniciando verificação de inatividade de usuários...');

        $count = $action->execute();

        if ($count > 0) {
            $this->info("Sucesso! $count usuários foram desativados.");
        } else {
            $this->info('Nenhum usuário inativo encontrado para desativação.');
        }
    }
}
