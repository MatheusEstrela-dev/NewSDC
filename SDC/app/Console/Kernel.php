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
