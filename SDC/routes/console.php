<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sdc:check-user-inactivity')
    ->dailyAt('02:00')
    ->onOneServer()       // Garante execução única em ambientes com múltiplos containers (requer Redis/Memcached)
    ->runInBackground()   // Executa em background para não bloquear outras tarefas agendadas no mesmo horário
    ->emailOutputOnFailure('admin@sdc.gov.br'); // Opcional: Configurar email do admin no .env futuramente

Schedule::command('pae:verificar-notificacoes')
    ->dailyAt('03:00')
    ->onOneServer()
    ->runInBackground();

// A retencao do inbox de notificacoes NAO fica aqui: ela arquiva em vez de
// apagar, e esta agendada em app/Console/Kernel.php como notificacoes:arquivar,
// ao lado do webhooks:archive que segue a mesma tratativa.

// Pipeline medalhao. Os jobs vao para a fila "medalhao", consumida por processo
// proprio (docker/supervisor/medalhao-worker.conf) — o ETL nao disputa worker
// com notificacao e webhook.
Schedule::command('medalhao:ingerir sismos')
    ->everyFifteenMinutes()
    ->onOneServer()
    ->runInBackground();

// Arquiva o Bronze vencido em Parquet e poda o Postgres. A poda so ocorre apos a
// escrita ser verificada — ver RolloverParquetJob.
Schedule::command('medalhao:rollup')
    ->dailyAt('04:00')
    ->onOneServer()
    ->runInBackground();
