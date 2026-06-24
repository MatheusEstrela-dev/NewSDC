<?php

use App\Modules\Pmda\Controllers\ComunidadeController;
use App\Modules\Pmda\Controllers\PlanoPontoController;
use App\Modules\Pmda\Controllers\PmdaPlanoController;
use App\Modules\Pmda\Controllers\RepresentanteController;
use App\Modules\Pmda\Models\PmdaComunidade;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Pmda\Models\PmdaRepresentante;
use Illuminate\Support\Facades\Route;

Route::model('plano', PmdaPlano::class);
Route::model('comunidade', PmdaComunidade::class);
Route::model('representante', PmdaRepresentante::class);

Route::prefix('pmda')->name('pmda.')->group(function () {
    Route::prefix('planos')->name('planos.')->group(function () {
        Route::get('/', [PmdaPlanoController::class, 'index'])
            ->name('index')->middleware('can:pmda.planos.view');
        Route::post('/', [PmdaPlanoController::class, 'store'])
            ->name('store')->middleware('can:pmda.planos.create');
        Route::get('/{plano}/edit', [PmdaPlanoController::class, 'edit'])
            ->name('edit')->middleware('can:pmda.planos.edit');
        Route::put('/{plano}', [PmdaPlanoController::class, 'update'])
            ->name('update')->middleware('can:pmda.planos.edit');
        Route::post('/{plano}/copiar', [PmdaPlanoController::class, 'copiar'])
            ->name('copiar')->middleware('can:pmda.planos.copiar');

        // Comunidades do plano
        Route::post('/{plano}/comunidades', [ComunidadeController::class, 'store'])
            ->name('comunidades.store')->middleware('can:pmda.comunidades.create');

        // Pontos de captacao do plano
        Route::post('/{plano}/pontos', [PlanoPontoController::class, 'store'])
            ->name('pontos.store')->middleware('can:pmda.pontos.create');
        Route::delete('/{plano}/pontos/{ponto}', [PlanoPontoController::class, 'destroy'])
            ->name('pontos.destroy')->middleware('can:pmda.pontos.delete');
    });

    Route::delete('/comunidades/{comunidade}', [ComunidadeController::class, 'destroy'])
        ->name('comunidades.destroy')->middleware('can:pmda.comunidades.delete');

    // Representantes da comunidade
    Route::post('/comunidades/{comunidade}/representantes', [RepresentanteController::class, 'store'])
        ->name('representantes.store')->middleware('can:pmda.representantes.create');
    Route::put('/representantes/{representante}', [RepresentanteController::class, 'update'])
        ->name('representantes.update')->middleware('can:pmda.representantes.edit');
    Route::delete('/representantes/{representante}', [RepresentanteController::class, 'destroy'])
        ->name('representantes.destroy')->middleware('can:pmda.representantes.delete');
});
