<?php

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
