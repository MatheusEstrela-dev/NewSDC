<?php

use App\Modules\Compdec\Presentation\Http\Controllers\OrgaoCreateController;
use App\Modules\Compdec\Presentation\Http\Controllers\OrgaoDeleteController;
use App\Modules\Compdec\Presentation\Http\Controllers\OrgaoIndexController;
use App\Modules\Compdec\Presentation\Http\Controllers\OrgaoShowController;
use App\Modules\Compdec\Presentation\Http\Controllers\OrgaoUpdateController;
use App\Modules\Compdec\Presentation\Http\Controllers\VincularUsuarioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Módulo Compdec (Órgãos e Competências)
|--------------------------------------------------------------------------
*/

// Já está dentro do middleware auth do web.php, então não precisa redefinir
Route::prefix('compdec')->name('compdec.')->group(function () {

    // Visualização (todos usuários autenticados)
    Route::get('/orgaos', OrgaoIndexController::class)->name('index');
    Route::get('/orgaos/{id}', OrgaoShowController::class)
        ->name('show')
        ->where('id', '[0-9]+');

    // Gestão (apenas usuários com permissão compdec.manage)
    Route::middleware('can:compdec.manage')->group(function () {
        // CRUD
        Route::get('/orgaos/novo', [OrgaoCreateController::class, 'create'])
            ->name('create');

        Route::post('/orgaos', [OrgaoCreateController::class, 'store'])
            ->name('store');

        Route::get('/orgaos/{id}/editar', [OrgaoUpdateController::class, 'edit'])
            ->name('edit')
            ->where('id', '[0-9]+');

        Route::put('/orgaos/{id}', [OrgaoUpdateController::class, 'update'])
            ->name('update')
            ->where('id', '[0-9]+');

        Route::delete('/orgaos/{id}', OrgaoDeleteController::class)
            ->name('destroy')
            ->where('id', '[0-9]+');

        // Vincular usuário
        Route::post('/orgaos/{id}/usuarios', VincularUsuarioController::class)
            ->name('usuarios.vincular')
            ->where('id', '[0-9]+');
    });
});
