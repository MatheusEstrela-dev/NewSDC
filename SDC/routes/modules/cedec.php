<?php

declare(strict_types=1);

use App\Modules\Cedec\Controllers\ContatoRelatorioController;
use App\Modules\Cedec\Controllers\PrefeituraController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modulo CEDEC - Cadastro estadual de prefeituras
|--------------------------------------------------------------------------
| Permissoes em config/permissions.php, bloco CEDEC.
|
| Armadilha obrigatoria: Route::model() e GLOBAL neste projeto e ja causou 404 no
| Pmda e no PlanCon (ver os comentarios em routes/modules/pmda.php e
| routes/modules/compdec.php). NAO registrar Route::model('municipio', ...) aqui:
| {municipio} ja e usado por routes/modules/cisterna.php sem binder explicito, e o
| binding implicito por type-hint App\Models\Municipio resolve nos dois lugares.
|
| A fase 5 acrescenta o sub-grupo `contatos` DENTRO deste mesmo grupo `cedec.`.
*/
Route::prefix('cedec')->name('cedec.')->group(function () {
    Route::prefix('prefeituras')->name('prefeituras.')->group(function () {
        Route::get('/', [PrefeituraController::class, 'index'])
            ->name('index')->middleware('can:cedec.prefeituras.view');
        Route::get('/{municipio}/edit', [PrefeituraController::class, 'edit'])
            ->name('edit')->middleware('can:cedec.prefeituras.view');
        Route::put('/{municipio}', [PrefeituraController::class, 'update'])
            ->name('update')->middleware('can:cedec.prefeituras.edit');
        Route::post('/{municipio}/foto', [PrefeituraController::class, 'uploadFoto'])
            ->name('foto.upload')->middleware('can:cedec.prefeituras.edit');
        Route::delete('/{municipio}/foto', [PrefeituraController::class, 'removerFoto'])
            ->name('foto.destroy')->middleware('can:cedec.prefeituras.edit');
    });

    // Relatorios de contato. /export nao disputa com rota de parametro porque este
    // sub-grupo nao tem nenhuma; fica antes por convencao, para nao ser capturado se
    // alguem acrescentar /{algo} depois.
    Route::prefix('contatos')->name('contatos.')->group(function () {
        Route::get('/', [ContatoRelatorioController::class, 'index'])
            ->name('index')->middleware('can:cedec.contatos.view');
        Route::get('/export', [ContatoRelatorioController::class, 'export'])
            ->name('export')->middleware('can:cedec.prefeituras.export');
    });
});
