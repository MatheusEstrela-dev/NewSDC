<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Treinamento EAD - aluno servidor
|--------------------------------------------------------------------------
| Incluido de dentro do grupo prefix('treinamentos')->name('treinamentos.')
| de routes/modules/treinamento.php, antes do wildcard /{id}.
*/

Route::prefix('aluno')->name('aluno.')->middleware('can:treinamento.cursos.view')->group(function () {
    //
});
