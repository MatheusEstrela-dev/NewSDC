<?php

use App\Modules\Treinamento\Controllers\Admin\CertificadoIndexController;
use App\Modules\Treinamento\Controllers\Admin\CertificadoReemitirController;
use App\Modules\Treinamento\Controllers\Admin\InscricaoAprovarController;
use App\Modules\Treinamento\Controllers\Admin\InscricaoIndexController;
use App\Modules\Treinamento\Controllers\Admin\InscricaoReprovarController;
use App\Modules\Treinamento\Controllers\Admin\PresencaManualController;
use App\Modules\Treinamento\Controllers\Admin\PresencaQrController;
use App\Modules\Treinamento\Controllers\Admin\PresencaRosterController;
use App\Modules\Treinamento\Controllers\Admin\PresencaSincronizarController;
use App\Modules\Treinamento\Controllers\CertificadoImprimirController;
use App\Modules\Treinamento\Controllers\InscricaoAutoconfirmarController;
use App\Modules\Treinamento\Controllers\InscricaoSelfStoreController;
use App\Modules\Treinamento\Controllers\TreinamentoBloquearPresencaController;
use App\Modules\Treinamento\Controllers\TreinamentoCreateController;
use App\Modules\Treinamento\Controllers\TreinamentoDeleteController;
use App\Modules\Treinamento\Controllers\TreinamentoEditController;
use App\Modules\Treinamento\Controllers\TreinamentoExportController;
use App\Modules\Treinamento\Controllers\TreinamentoIndexController;
use App\Modules\Treinamento\Controllers\TreinamentoLiberarPresencaController;
use App\Modules\Treinamento\Controllers\TreinamentoPublicarController;
use App\Modules\Treinamento\Controllers\TreinamentoShowController;
use App\Modules\Treinamento\Controllers\TreinamentoStoreController;
use App\Modules\Treinamento\Controllers\TreinamentoTransicionarStatusController;
use App\Modules\Treinamento\Controllers\TreinamentoUpdateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Módulo Treinamento (área interna - servidores da Defesa Civil)
|--------------------------------------------------------------------------
*/

// Ja esta dentro do middleware auth do web.php, entao nao precisa redefinir
Route::prefix('treinamentos')->name('treinamentos.')->group(function () {

    // Portal do Usuario - Visualizar treinamentos
    Route::get('/', TreinamentoIndexController::class)
        ->name('index')
        ->middleware('can:treinamento.cursos.view');
    Route::get('/export', TreinamentoExportController::class)
        ->name('export')
        ->middleware('can:treinamento.cursos.export');
    // Antes do /{id} de proposito: senao o wildcard casaria com "create" primeiro.
    Route::get('/create', TreinamentoCreateController::class)
        ->name('create')
        ->middleware('can:treinamento.cursos.create');
    // Tambem antes do /{id}, pelo mesmo motivo do /create.
    Route::get('/{id}/edit', TreinamentoEditController::class)
        ->name('edit')
        ->middleware('can:treinamento.cursos.edit');
    Route::get('/{id}', TreinamentoShowController::class)
        ->name('show')
        ->middleware('can:treinamento.cursos.view');

    // Auto-inscricao do proprio servidor (nao e acao de admin, so exige o
    // acesso basico ao catalogo).
    Route::post('/{treinamento}/inscrever', InscricaoSelfStoreController::class)
        ->name('inscrever')
        ->middleware('can:treinamento.cursos.view');

    // Autoconfirmacao de presenca (sem QR) - so para treinamentos ONLINE,
    // o proprio servidor confirma a propria presenca.
    Route::post('/inscricoes/{inscricao}/autoconfirmar', InscricaoAutoconfirmarController::class)
        ->name('inscricoes.autoconfirmar')
        ->middleware('can:treinamento.cursos.view');

    // Impressao do certificado (dono da inscricao ou quem tem permissao de
    // download): a autorizacao fina fica no proprio controller.
    Route::get('/certificados/{certificado}/imprimir', CertificadoImprimirController::class)
        ->name('certificados.imprimir');

    // Admin - Gestao de Treinamentos
    Route::middleware('can:treinamento.cursos.create')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::post('/', TreinamentoStoreController::class)->name('store');
        });

    Route::middleware('can:treinamento.cursos.edit')->group(function () {
        Route::put('/{id}', TreinamentoUpdateController::class)->name('update');
        Route::post('/{treinamento}/publicar', TreinamentoPublicarController::class)->name('publicar');
        Route::post('/{treinamento}/status', TreinamentoTransicionarStatusController::class)->name('status');
    });

    Route::delete('/{id}', TreinamentoDeleteController::class)
        ->name('destroy')
        ->middleware('can:treinamento.cursos.delete');

    // Presenca (liberar/bloquear o "portao" de check-in do treinamento)
    Route::middleware('can:treinamento.presencas.registrar')->group(function () {
        Route::post('/{treinamento}/liberar-presenca', TreinamentoLiberarPresencaController::class)->name('liberar-presenca');
        Route::post('/{treinamento}/bloquear-presenca', TreinamentoBloquearPresencaController::class)->name('bloquear-presenca');
        Route::post('/presencas/qr', PresencaQrController::class)->name('presencas.qr');
        Route::post('/presencas/manual', PresencaManualController::class)->name('presencas.manual');
        // RF07 - offline: roster para cache local + sincronizacao em lote.
        Route::get('/{treinamento}/roster', PresencaRosterController::class)->name('roster');
        Route::post('/presencas/sincronizar', PresencaSincronizarController::class)->name('presencas.sincronizar');
    });

    // Inscricoes (gestao - visao do admin sobre os inscritos de um treinamento)
    Route::middleware('can:treinamento.inscricoes.view')->group(function () {
        Route::get('/{treinamento}/inscricoes', InscricaoIndexController::class)->name('inscricoes.index');
    });
    Route::post('/inscricoes/{inscricao}/aprovar', InscricaoAprovarController::class)
        ->name('inscricoes.aprovar')
        ->middleware('can:treinamento.inscricoes.aprovar');
    Route::post('/inscricoes/{inscricao}/reprovar', InscricaoReprovarController::class)
        ->name('inscricoes.reprovar')
        ->middleware('can:treinamento.inscricoes.reprovar');

    // Certificados (visao do admin sobre os certificados de um treinamento)
    Route::middleware('can:treinamento.certificados.view')->group(function () {
        Route::get('/{treinamento}/certificados', CertificadoIndexController::class)->name('certificados.index');
    });
    Route::post('/certificados/{certificado}/reemitir', CertificadoReemitirController::class)
        ->name('certificados.reemitir')
        ->middleware('can:treinamento.certificados.reemitir');
});
