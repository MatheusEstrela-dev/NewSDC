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
