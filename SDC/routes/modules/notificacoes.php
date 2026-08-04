<?php

declare(strict_types=1);

use App\Http\Controllers\NotificationPreferencesController;
use App\Modules\Notificacoes\Controllers\NotificacaoInboxController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inbox de notificacoes
|--------------------------------------------------------------------------
|
| Rotas web (sessao), nao API: o consumidor e o painel do sino dentro do app
| Inertia, que ja viaja com o cookie de sessao.
|
| O throttle de inbox e generoso porque no modo polling cada aba consulta a cada
| 30s e a resposta costuma ser um 304 vazio. As rotas de escrita ficam mais
| apertadas: sao acoes de clique, nao de loop.
|
*/

Route::middleware('auth')->prefix('notificacoes')->name('notificacoes.')->group(function () {
    Route::get('/inbox', [NotificacaoInboxController::class, 'index'])
        ->middleware('throttle:120,1')
        ->name('inbox');

    Route::post('/lidas', [NotificacaoInboxController::class, 'lidas'])
        ->middleware('throttle:60,1')
        ->name('lidas');

    Route::post('/todas-lidas', [NotificacaoInboxController::class, 'todasLidas'])
        ->middleware('throttle:30,1')
        ->name('todas-lidas');

    /*
    | Preferencias por sessao.
    |
    | As mesmas acoes existem em /api/v1/notification-preferences (mesmo controller,
    | sem duplicar regra), mas aquele caminho depende de auth:sanctum reconhecer o
    | dominio como stateful. Em localhost:8000 nao reconhecia, porque a lista de
    | sanctum.stateful compara com a porta e so continha 127.0.0.1:8000 -- e a UI
    | falhava silenciosamente. Como o painel e o modal vivem dentro do app Inertia,
    | que ja viaja com cookie de sessao, a rota web e o caminho correto para eles.
    | A rota de API permanece para clientes com token.
    */
    Route::get('/preferencias', [NotificationPreferencesController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('preferencias.index');

    Route::put('/preferencias', [NotificationPreferencesController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('preferencias.update');

    // Depois das rotas fixas, senao "lidas" e "todas-lidas" cairiam aqui.
    Route::post('/{notificacao}/lida', [NotificacaoInboxController::class, 'lida'])
        ->middleware('throttle:60,1')
        ->name('lida');

    Route::get('/', [NotificacaoInboxController::class, 'historico'])->name('historico');
});
