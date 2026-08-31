<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Console;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

/**
 * Gera o par de chaves VAPID usado pelo canal de Web Push.
 *
 * Roda UMA vez por ambiente. Trocar o par depois invalida todas as inscricoes
 * ja gravadas: os navegadores continuam inscritos com a chave publica antiga e o
 * push service passa a recusar o envio. Por isso o comando nao escreve no .env
 * sozinho -- ele imprime e o operador cola, com a chance de perceber que ja
 * existe chave configurada.
 *
 * Uso: php artisan notificacoes:vapid
 */
class GerarChavesVapidCommand extends Command
{
    protected $signature = 'notificacoes:vapid';

    protected $description = 'Gera o par de chaves VAPID para o canal de Web Push';

    public function handle(): int
    {
        if (config('webpush.vapid.public_key')) {
            $this->warn('Ja existe VAPID_PUBLIC_KEY configurada neste ambiente.');
            $this->line('Gerar um par novo derruba todas as inscricoes de push existentes.');

            if (!$this->confirm('Gerar mesmo assim?', false)) {
                return self::SUCCESS;
            }
        }

        $chaves = VAPID::createVapidKeys();

        $this->info('Chaves geradas. Copie para o .env:');
        $this->newLine();
        $this->line('VAPID_SUBJECT=mailto:sdc@defesacivil.mg.gov.br');
        $this->line('VAPID_PUBLIC_KEY='.$chaves['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$chaves['privateKey']);
        $this->newLine();
        $this->comment('A chave privada nunca sai do servidor. A publica vai para o navegador.');

        return self::SUCCESS;
    }
}
