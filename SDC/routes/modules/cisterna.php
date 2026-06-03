<?php

declare(strict_types=1);

use App\Modules\Cisterna\Controllers\CisternaController;
use App\Modules\Cisterna\Models\Cisterna;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modulo CISTERNAS
|--------------------------------------------------------------------------
| Cadastro e acompanhamento das cisternas vinculadas aos municipios.
| Permissoes em config/permissions.php (grupo CISTERNAS).
*/

Route::model('cisterna', Cisterna::class);

// Mantem URL antiga acessivel
Route::redirect('/cisterna', '/cisternas')->name('cisterna.redirect');

Route::middleware(['auth'])
    ->prefix('cisternas')
    ->name('cisternas.')
    ->group(function () {

        Route::middleware('can:cisternas.view')->group(function () {
            Route::get('/', [CisternaController::class, 'index'])->name('index');
            Route::get('/{cisterna}', [CisternaController::class, 'show'])
                ->name('show')
                ->whereNumber('cisterna');
        });

        Route::middleware('can:cisternas.create')->group(function () {
            Route::get('/novo', [CisternaController::class, 'create'])->name('create');
            Route::post('/', [CisternaController::class, 'store'])->name('store');
        });

        Route::middleware('can:cisternas.edit')->group(function () {
            Route::get('/{cisterna}/editar', [CisternaController::class, 'edit'])
                ->name('edit')
                ->whereNumber('cisterna');
            Route::put('/{cisterna}', [CisternaController::class, 'update'])
                ->name('update')
                ->whereNumber('cisterna');
        });

        Route::middleware('can:cisternas.delete')->group(function () {
            Route::delete('/{cisterna}', [CisternaController::class, 'destroy'])
                ->name('destroy')
                ->whereNumber('cisterna');
        });

    });
