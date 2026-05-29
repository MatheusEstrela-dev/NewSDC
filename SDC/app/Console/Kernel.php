<?php

namespace App\Console;

use App\Jobs\CleanExpiredPermissions;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Limpa roles e permissions expiradas a cada hora
        $schedule->job(new CleanExpiredPermissions())->hourly();

        // Desativa usuarios inativos ha mais de 6 meses (executa diariamente as 02:00)
        $schedule->command('users:deactivate-inactive')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();

        // COMPDEC: alerta diario sobre anexos legais com vencimento proximo (09:00)
        $schedule->command('compdec:alertar-anexos-vencimento')
            ->dailyAt('09:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Onboarding: desativa contas pending cuja senha provisoria expirou (24h).
        $schedule->command('users:deactivate-expired-pending')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        // Resiliencia: arquiva webhook_events completed com idade > 90 dias.
        // Roda semanalmente (domingo 03:00) para manter tabela operacional pequena.
        $schedule->command('webhooks:archive --days=90 --chunk=500')
            ->weeklyOn(0, '03:00')
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground();

        // Async exports: remove artefatos do disk 'exports' com idade > 7 dias.
        // Limpa tambem result_disk/result_path nos traces associados.
        $schedule->command('exports:cleanup --days=7')
            ->dailyAt('04:00')
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground();

        // RAT: fecha automaticamente protocolos com prazo de edição (48h) expirado.
        $schedule->command('rat:close-expired')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        // Auth: purga pedidos de troca de e-mail concluidos/cancelados/expirados
        // com mais de 30 dias para manter a tabela enxuta.
        $schedule->command('email-change:cleanup-expired')
            ->daily()
            ->onOneServer()
            ->withoutOverlapping()
            ->name('email-change-cleanup');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
