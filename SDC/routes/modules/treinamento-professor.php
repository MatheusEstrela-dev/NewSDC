<?php

use App\Modules\Treinamento\Controllers\Admin\ProfessorCandidatosController;
use App\Modules\Treinamento\Controllers\Admin\ProfessorDestroyController;
use App\Modules\Treinamento\Controllers\Admin\ProfessorStoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Treinamento EAD - area do professor e designacao de professores
|--------------------------------------------------------------------------
| Incluido de dentro do grupo prefix('treinamentos')->name('treinamentos.')
| de routes/modules/treinamento.php, antes do wildcard /{id}.
*/

Route::prefix('professor')->name('professor.')->middleware('can:treinamento.professor.acessar')->group(function () {
    //
});

// Designacao de professores (administracao do curso).
Route::middleware('can:treinamento.cursos.edit')->group(function () {
    Route::get('/professores/candidatos', ProfessorCandidatosController::class)->name('professores.candidatos');
    Route::post('/{treinamento}/professores', ProfessorStoreController::class)->name('professores.store');
    Route::delete('/{treinamento}/professores/{userId}', ProfessorDestroyController::class)->name('professores.destroy');
});
