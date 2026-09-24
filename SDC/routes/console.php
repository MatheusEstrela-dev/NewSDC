<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Despacho do Transactional Outbox. Sem isto nenhum Domain Event sai de
// outbox_events e nenhum listener roda - o placar congela na ultima projecao.
//
// A cada 30s e nao em loop continuo: um processo residente a mais disputaria
// memoria com as replicas, e o plano admite ate 60s de atraso na atualizacao do
// placar. --once processa um lote e sai; withoutOverlapping impede duas
// execucoes simultaneas se um lote demorar mais que o intervalo, e onOneServer
// garante um unico despachante mesmo com varios schedulers.
if (config('outbox.agendar_despacho', false)) {
    Schedule::command('outbox:dispatch --once --batch='.max(1, (int) config('outbox.lote', 100)))
        // Prazo explicito de 2 min na trava: o padrao do Laravel e 24h. Um
        // despacho morto por SIGKILL (ja aconteceu, por falta de memoria) pode
        // nao liberar a trava, e sem prazo o placar congelaria por um dia.
        ->everyThirtySeconds()->onOneServer()->withoutOverlapping(2);
}

// O rollout controla somente a agenda: comandos de diagnostico continuam disponiveis.
if (config('ranking.habilitado', false)) {
    Schedule::command('ranking:materializar-periodos')->dailyAt('00:05')
        ->timezone('America/Sao_Paulo')->onOneServer()->withoutOverlapping(30);
    // De hora em hora, aos :50. O vinculo so vale a partir da sincronizacao que
    // o abre - nunca se retroage -, entao o intervalo entre rodadas e o tempo
    // em que usuario recem-lotado pontua em apuracao, ou em que quem trocou de
    // orgao ainda credita o antigo. Aos :50 para cair antes do reconcile de
    // hora cheia e, as 00:50, antes do snapshot das 01:00. O trabalho e
    // barato: ~1 mil linhas de origem, quase tudo inalterado ou ignorado.
    Schedule::command('ranking:sincronizar-vinculos')->hourlyAt(50)
        ->onOneServer()->withoutOverlapping(30);
    Schedule::command('ranking:reconcile')->hourly()
        ->onOneServer()->withoutOverlapping(55);
    // 01:15 e nao 01:00: as 01:00 ja rodam o reconcile da hora cheia e o
    // despacho do outbox. Um terceiro Laravel filho no mesmo instante e o que
    // estoura a memoria do scheduler. Continua depois da sincronizacao das
    // 00:50 e do reconcile das 01:00, que e a ordem que o snapshot exige.
    Schedule::command('ranking:snapshot')->dailyAt('01:15')
        ->timezone('America/Sao_Paulo')->onOneServer()->withoutOverlapping(55);
}

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

/*
 * A cada 10 minutos, embora o INMET publique de hora em hora.
 *
 * Isso NAO busca dado que nao existe: a granularidade da fonte e horaria, medido
 * na propria API (HR_MEDICAO vai de 0000 a 2300, um por hora). O que a cadencia
 * muda e o atraso de DETECCAO.
 *
 * De hora em hora: o INMET publica a leitura das 17:00 por volta das 17:05 e nos
 * coletamos as 18:00 -- a tela mostra dado de ate 60 minutos atras. De 10 em 10,
 * vemos as 17:10. Para chuva em Defesa Civil, sair de 60 para 10 minutos de
 * defasagem e operacionalmente relevante.
 *
 * O custo extra e baixo: o dedup por hash do IngerirFonteJob descarta o ciclo
 * antes de gravar quando o payload nao mudou, e a coleta das 61 estacoes leva
 * ~7s com a API quente. Medido: a API nao aplica rate limit (8 chamadas seguidas,
 * todas 200, ~220ms cada).
 *
 * Dez minutos, e nao quinze, para casar com a cadencia do CEMADEN, agendado
 * abaixo.
 */
Schedule::command('medalhao:ingerir inmet')
    ->everyTenMinutes()
    ->onOneServer()
    ->runInBackground();

/*
 * Aqui os dez minutos buscam dado que EXISTE, diferente do INMET: o feed do
 * CEMADEN republica o campo "atualizado" a cada ~10 min, e foi medido avancando
 * em 2 minutos (16:58:37 -> 17:00:44 em 2026-09-02). Cada avanco vira um
 * snapshot novo em silver.leituras_cemaden, e e isso que faz a tela andar em
 * 16:10, 16:20 em vez de 16:00, 17:00.
 *
 * Custo: UMA requisicao por ciclo, contra as 68 do INMET -- o feed e agregado
 * nacional. Quando o feed nao avanca, o dedup por hash da camada Bronze
 * descarta antes de tocar Silver ou Gold.
 */
Schedule::command('medalhao:ingerir cemaden')
    ->everyTenMinutes()
    ->onOneServer()
    ->runInBackground();

/*
 * Validade das camadas de risco.
 *
 * As 05:00, depois do medalhao:rollup das 04:00 e antes do expediente: a
 * camada que venceu a meia-noite tem de estar fora do mapa quando o plantao
 * abrir a tela, e nao no meio da manha.
 *
 * Diario, e nao de hora em hora, porque a granularidade de valido_ate e DATE --
 * varrer de hora em hora reavaliaria a mesma comparacao 24 vezes para mudar de
 * resultado uma vez por dia.
 */
Schedule::command('geoespacial:arquivar-vencidas')
    ->dailyAt('05:00')
    ->onOneServer()
    ->runInBackground();

// Arquiva o Bronze vencido em Parquet e poda o Postgres. A poda so ocorre apos a
// escrita ser verificada — ver RolloverParquetJob.
Schedule::command('medalhao:rollup')
    ->dailyAt('04:00')
    ->onOneServer()
    ->runInBackground();
