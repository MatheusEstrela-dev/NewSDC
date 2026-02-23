<?php

use Illuminate\Support\Facades\Route;
use App\Core\IA\Http\Controllers\ChatController;
use App\Core\IA\Http\Controllers\AIPluginController;

/*
|--------------------------------------------------------------------------
| AI Module API Routes
|--------------------------------------------------------------------------
|
| Registre estas rotas no RouteServiceProvider ou routes/api.php:
|
| Route::prefix('ai')->middleware('auth:sanctum')->group(
|     base_path('core/IA/Routes/api.php')
| );
|
*/

Route::prefix('ai')->middleware('auth:sanctum')->group(function () {
    // Chat endpoints
    Route::post('chat', [ChatController::class, 'chat']);
    Route::post('chat/stream', [ChatController::class, 'stream']);

    // Conversations
    Route::get('conversations', [ChatController::class, 'conversations']);
    Route::get('conversations/{id}/messages', [ChatController::class, 'messages']);
    Route::delete('conversations/{id}', [ChatController::class, 'deleteConversation']);

    // Tools info
    Route::get('tools', [ChatController::class, 'tools']);

    // Legacy plugins API
    Route::get('plugins', [AIPluginController::class, 'index']);
    Route::post('execute-plugin', [AIPluginController::class, 'execute']);
});
