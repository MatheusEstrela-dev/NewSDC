<?php

use App\Modules\Treinamento\Controllers\VideoArquivoDevController;
use App\Modules\Treinamento\Controllers\VideoAuthController;
use Illuminate\Support\Facades\Route;

// Entrega de video EAD, no grupo de middleware "video" (ver Kernel): o Caddy
// chama /videos/auth (forward_auth) antes de servir cada arquivo HLS. Sem
// middleware "auth" de proposito: sem login a resposta e 403, nunca redirect.
// A rota de arquivo so existe para dev local.
Route::get('/videos/auth', VideoAuthController::class)
    ->name('videos.auth');
Route::get('/videos/{videoId}/{versao}/{arquivo}', VideoArquivoDevController::class)
    ->whereNumber('videoId')
    ->where('versao', '[A-Za-z0-9_\-]{1,40}')
    ->where('arquivo', '[A-Za-z0-9_\-]+(/[A-Za-z0-9_\-]+)*\.(m3u8|ts)')
    ->name('videos.arquivo-dev');
