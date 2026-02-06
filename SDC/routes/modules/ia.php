<?php

use App\Core\IA\Http\Controllers\ChatController;
use App\Core\IA\Http\Controllers\AIPluginController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('ia')->name('ia.')->group(function () {
    // Chat endpoints
    Route::post('chat', [ChatController::class, 'chat'])->name('chat');
    Route::post('chat/stream', [ChatController::class, 'stream'])->name('chat.stream');

    // Conversations
    Route::get('conversations', [ChatController::class, 'conversations'])->name('conversations');
    Route::get('conversations/{id}/messages', [ChatController::class, 'messages'])->name('conversations.messages');
    Route::delete('conversations/{id}', [ChatController::class, 'deleteConversation'])->name('conversations.delete');

    // Tools info
    Route::get('tools', [ChatController::class, 'tools'])->name('tools');

    // Plugins
    Route::get('plugins', [AIPluginController::class, 'index'])->name('plugins');
    Route::post('execute-plugin', [AIPluginController::class, 'execute'])->name('plugins.execute');
});
