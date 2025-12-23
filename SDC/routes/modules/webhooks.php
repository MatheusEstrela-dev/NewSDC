<?php

use App\Http\Controllers\Api\V1\Webhook\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('webhooks')->name('api.v1.webhooks.')->group(function () {
    // Recebimento pode ser público (mantemos compatível com o que já existe)
    Route::post('receive', [WebhookController::class, 'receive'])
        ->middleware('throttle:webhook')
        ->withoutMiddleware('auth:sanctum')
        ->name('receive');

    Route::post('send', [WebhookController::class, 'send'])->name('send');
    Route::post('send-sync', [WebhookController::class, 'sendSync'])->name('send-sync');
    Route::get('logs', [WebhookController::class, 'logs'])->name('logs');
});


