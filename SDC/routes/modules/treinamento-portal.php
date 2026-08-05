<?php

use App\Modules\Treinamento\Controllers\CertificadoImprimirController;
use App\Modules\Treinamento\Controllers\Portal\AuthenticatedSessionController;
use App\Modules\Treinamento\Controllers\Portal\CatalogoController;
use App\Modules\Treinamento\Controllers\Portal\CertificadoController;
use App\Modules\Treinamento\Controllers\Portal\InscricaoController;
use App\Modules\Treinamento\Controllers\Portal\MinhasInscricoesController;
use App\Modules\Treinamento\Controllers\Portal\PresencaAutoconfirmacaoController;
use App\Modules\Treinamento\Controllers\Portal\RegisterController;
use App\Modules\Treinamento\Http\Middleware\ShareCidadaoInertiaData;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Portal de Treinamentos (cidadaos externos, guard "cidadao")
|--------------------------------------------------------------------------
| Fora do grupo Route::middleware('auth') de routes/web.php (esse grupo usa
| o guard "web" por padrao). Continua dentro do grupo de middleware "web"
| global (sessao/CSRF), que ja cobre o arquivo routes/web.php inteiro.
*/

Route::prefix('portal-treinamento')->name('portal.treinamento.')->group(function () {

    // Login e unificado em /login (App\Http\Controllers\Auth\AuthenticatedSessionController
    // decide o guard pelo CPF) - o portal so tem cadastro e as telas pos-login.
    Route::get('/registrar', [RegisterController::class, 'create'])->name('registrar');
    Route::post('/registrar', [RegisterController::class, 'store']);

    Route::middleware(['auth:cidadao', ShareCidadaoInertiaData::class])->group(function () {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('/', [CatalogoController::class, 'index'])->name('catalogo');
        Route::get('/eventos/{slug}', [CatalogoController::class, 'show'])->name('eventos.show');
        Route::post('/eventos/{slug}/inscricoes', [InscricaoController::class, 'store'])
            ->name('inscricoes.store')
            ->middleware('throttle:10,1');

        // Autoconfirmacao de presenca (sem QR) - so para treinamentos ONLINE.
        Route::post('/inscricoes/{inscricao}/presenca', [PresencaAutoconfirmacaoController::class, 'store'])
            ->name('inscricoes.presenca');

        Route::get('/minhas-inscricoes', MinhasInscricoesController::class)->name('inscricoes.index');
        Route::get('/certificados', CertificadoController::class)->name('certificados.index');
        Route::get('/certificados/{certificado}/imprimir', CertificadoImprimirController::class)->name('certificados.imprimir');
    });
});
