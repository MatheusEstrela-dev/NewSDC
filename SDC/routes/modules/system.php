<?php

use App\Http\Controllers\Api\LogViewerController;
use Illuminate\Support\Facades\Route;

// Log Viewer / Observabilidade (protegido via auth:sanctum no grupo v1)
Route::prefix('logs')->name('api.v1.logs.')->group(function () {
    Route::get('recent', [LogViewerController::class, 'recent'])->name('recent');
    Route::get('metrics', [LogViewerController::class, 'metrics'])->name('metrics');
    Route::get('errors', [LogViewerController::class, 'errors'])->name('errors');
    Route::get('stream', [LogViewerController::class, 'stream'])->name('stream');
});


