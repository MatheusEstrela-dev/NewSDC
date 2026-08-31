<?php

use App\Modules\PlanCon\Controllers\PlanConController;
use Illuminate\Support\Facades\Route;

Route::prefix('plancon')->name('plancon.')->group(function () {
    Route::get('/', [PlanConController::class, 'index'])->name('index');
    Route::get('/stats', [PlanConController::class, 'stats'])->name('stats');
    Route::get('/municipios/com-plano', [PlanConController::class, 'municipiosComPlano'])->name('municipios.com');
    Route::get('/municipios/sem-plano', [PlanConController::class, 'municipiosSemPlano'])->name('municipios.sem');

    // Envio pelo proprio municipio: o orgao sai do usuario logado, por isso a
    // rota nao recebe municipio. A gravacao e delegada ao servico do COMPDEC.
    Route::post('/planos', [PlanConController::class, 'store'])
        ->name('planos.store')
        ->middleware('can:plancon.upload');

    // Nao usar {plano} como nome de parametro -- Route::model() e global e ja
    // houve colisao entre Compdec e Pmda com esse nome.
    Route::get('/planos/{planoContingencia}/download', [PlanConController::class, 'download'])
        ->name('planos.download')
        ->whereNumber('planoContingencia')
        ->middleware('can:plancon.download');
});
