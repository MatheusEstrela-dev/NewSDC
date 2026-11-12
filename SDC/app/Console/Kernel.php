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

        // Retencao unica das notificacoes e dos logs tecnicos (config/retencao.php):
        // apaga o que passou de 90 dias, em lotes curtos na conexao de log.
        // Notificacao nao lida nunca e apagada. Substitui webhooks:archive e
        // notificacoes:arquivar.
        $schedule->command('log:podar')
            ->dailyAt('03:30')
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground()
            ->name('log-podar');

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

        // Portal de Treinamentos: descarta cadastro de cidadao que nunca
        // confirmou o e-mail. Higiene da tabela - quem tenta se cadastrar de
        // novo com o mesmo CPF/e-mail ja sobrescreve o pendente no ato.
        $schedule->command('treinamento:limpar-cidadaos-nao-verificados')
            ->daily()
            ->onOneServer()
            ->withoutOverlapping()
            ->name('treinamento-limpar-cidadaos-nao-verificados');

        // Escala de plantao: avisa quem tem turno comecando dentro da janela de
        // config('plantao.escala.lembrete_minutos_antes').
        //
        // A cada 15 minutos, e nao de hora em hora, para que a antecedencia
        // sentida seja proxima da configurada: com passo horario, um turno das
        // 16h avisado com janela de 2h chegaria em qualquer ponto entre 14h e
        // 15h. onOneServer + withoutOverlapping evitam duas execucoes
        // concorrentes -- e, ainda assim, o comando marca lembrete_enviado_em
        // por UPDATE condicional, porque agendamento nao e garantia de exclusao
        // mutua e um aviso repetido ensina o usuario a ignorar o sino.
        $schedule->command('plantao:lembrar-escala')
            ->everyFifteenMinutes()
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground()
            ->name('plantao-lembrar-escala');

        // Reserva de viatura: solta a agenda presa por quem reservou e nao
        // apareceu. Mesmo passo de 15 minutos do lembrete acima, e pela mesma
        // razao -- com passo horario, a viatura ficaria bloqueada por ate uma
        // hora depois do prazo, e quem esta na porta querendo a chave nao tem
        // como saber que a reserva ja morreu. So mexe em AGENDADA: reserva com
        // chave retirada e problema de check-out, nao de relogio.
        $schedule->command('plantao:expirar-reservas')
            ->everyFifteenMinutes()
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground()
            ->name('plantao-expirar-reservas');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        // Comandos que vivem junto do proprio dominio, e nao em Console/Commands.
        // Usar load() e nao $this->commands([...]): dentro deste metodo, que ja se
        // chama commands(), aquela chamada seria recursao infinita.
        $this->load(base_path('app/Modules/Notificacoes/Console'));
        $this->load(base_path('app/Modules/Plantao/Console'));
        $this->load(base_path('app/Modules/Treinamento/Console'));

        require base_path('routes/console.php');
    }
}
