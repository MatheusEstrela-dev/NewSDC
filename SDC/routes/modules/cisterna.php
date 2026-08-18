<?php

declare(strict_types=1);

use App\Modules\Cisterna\Controllers\BeneficiarioController;
use App\Modules\Cisterna\Controllers\ComunidadeController;
use App\Modules\Cisterna\Controllers\LoteController;
use App\Modules\Cisterna\Controllers\NotificacaoFiscalizacaoController;
use App\Modules\Cisterna\Controllers\OrdemServicoController;
use App\Modules\Cisterna\Controllers\QrCodeController;
use App\Modules\Cisterna\Controllers\VistoriaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modulo CISTERNA
|--------------------------------------------------------------------------
| Cadastro de beneficiario do Projeto Cisterna e fiscalizacao da instalacao
| em tres etapas (fornecedor -> COMPDEC -> CEDEC).
|
| Permissoes em config/permissions.php, grupo CISTERNAS.
| Autorizacao por policy: as rotas nao usam middleware `can:`, porque o
| recorte territorial do perfil COMPDEC depende da instancia do model.
|
| Este arquivo e incluido de dentro do grupo Route::middleware('auth') de
| routes/web.php. A ficha publica do QR Code sai desse grupo com
| withoutMiddleware('auth').
|
| Rotas removidas do legado, deliberadamente:
|  - cisterna/relatorio/ranqueamento — apontava para metodo inexistente
|  - /adicionar-permissoes-compdec — concedia permissao em massa via GET
|  - cisterna/check_duplicated_qrcode — a constraint UNIQUE responde
|  - cisterna/valida_cedec — terminava em dd($request)
*/

// Ficha publica lida pelo QR Code colado na cisterna: sem autenticacao,
// como no legado.
Route::get('cisternas/qrcode/{numeroInstalacao}', [QrCodeController::class, 'ficha'])
    ->withoutMiddleware(['auth'])
    ->name('cisternas.qrcode.ficha')
    ->whereNumber('numeroInstalacao');

// Mantem a URL antiga acessivel.
Route::redirect('/cisterna', '/cisternas/beneficiarios')->name('cisterna.redirect');
Route::redirect('/cisternas', '/cisternas/beneficiarios')->name('cisternas.redirect');

Route::middleware(['auth'])->prefix('cisternas')->name('cisternas.')->group(function (): void {

    /* Beneficiarios */
    Route::prefix('beneficiarios')->name('beneficiarios.')->group(function (): void {
        Route::get('/', [BeneficiarioController::class, 'index'])->name('index');
        Route::get('/exportar', [BeneficiarioController::class, 'export'])->name('export');
        Route::get('/novo', [BeneficiarioController::class, 'create'])->name('create');
        Route::post('/', [BeneficiarioController::class, 'store'])->name('store');
        Route::post('/acao-em-massa', [BeneficiarioController::class, 'acaoEmMassa'])->name('acao-em-massa');
        Route::get('/{beneficiario}', [BeneficiarioController::class, 'show'])
            ->name('show')->whereNumber('beneficiario');
        Route::get('/{beneficiario}/editar', [BeneficiarioController::class, 'edit'])
            ->name('edit')->whereNumber('beneficiario');
        Route::put('/{beneficiario}', [BeneficiarioController::class, 'update'])
            ->name('update')->whereNumber('beneficiario');
        Route::delete('/{beneficiario}', [BeneficiarioController::class, 'destroy'])
            ->name('destroy')->whereNumber('beneficiario');
    });

    /* Vistorias */
    Route::prefix('vistorias')->name('vistorias.')->group(function (): void {
        Route::get('/beneficiario/{beneficiario}', [VistoriaController::class, 'index'])
            ->name('index')->whereNumber('beneficiario');
        Route::post('/', [VistoriaController::class, 'store'])->name('store');
        Route::get('/{vistoria}', [VistoriaController::class, 'show'])
            ->name('show')->whereNumber('vistoria');
        Route::put('/{vistoria}', [VistoriaController::class, 'update'])
            ->name('update')->whereNumber('vistoria');
        Route::post('/{vistoria}/concluir', [VistoriaController::class, 'concluir'])
            ->name('concluir')->whereNumber('vistoria');
        Route::delete('/{vistoria}', [VistoriaController::class, 'destroy'])
            ->name('destroy')->whereNumber('vistoria');
    });

    /* Comunidades */
    Route::prefix('comunidades')->name('comunidades.')->group(function (): void {
        Route::get('/', [ComunidadeController::class, 'index'])->name('index');
        Route::get('/municipio/{municipio}', [ComunidadeController::class, 'doMunicipio'])
            ->name('do-municipio')->whereNumber('municipio');
        Route::post('/', [ComunidadeController::class, 'store'])->name('store');
        Route::put('/{comunidade}', [ComunidadeController::class, 'update'])
            ->name('update')->whereNumber('comunidade');
        Route::delete('/{comunidade}', [ComunidadeController::class, 'destroy'])
            ->name('destroy')->whereNumber('comunidade');
    });

    /* Lotes */
    Route::prefix('lotes')->name('lotes.')->group(function (): void {
        Route::get('/', [LoteController::class, 'index'])->name('index');
        Route::post('/', [LoteController::class, 'store'])->name('store');
        Route::put('/{lote}', [LoteController::class, 'update'])
            ->name('update')->whereNumber('lote');
        Route::delete('/{lote}', [LoteController::class, 'destroy'])
            ->name('destroy')->whereNumber('lote');
    });

    /* Ordens de servico */
    Route::prefix('ordens-servico')->name('ordens-servico.')->group(function (): void {
        Route::get('/', [OrdemServicoController::class, 'index'])->name('index');
        Route::get('/lote/{lote}', [OrdemServicoController::class, 'doLote'])
            ->name('do-lote')->whereNumber('lote');
        Route::post('/', [OrdemServicoController::class, 'store'])->name('store');
        Route::get('/{ordemServico}/timeline', [OrdemServicoController::class, 'timeline'])
            ->name('timeline')->whereNumber('ordemServico');
        Route::put('/{ordemServico}', [OrdemServicoController::class, 'update'])
            ->name('update')->whereNumber('ordemServico');
        Route::delete('/{ordemServico}', [OrdemServicoController::class, 'destroy'])
            ->name('destroy')->whereNumber('ordemServico');
    });

    /* Notificacoes de fiscalizacao */
    Route::prefix('notificacoes')->name('notificacoes.')->group(function (): void {
        Route::get('/', [NotificacaoFiscalizacaoController::class, 'index'])->name('index');
        Route::post('/', [NotificacaoFiscalizacaoController::class, 'store'])->name('store');
        Route::put('/{notificacao}', [NotificacaoFiscalizacaoController::class, 'update'])
            ->name('update')->whereNumber('notificacao');
        Route::post('/{notificacao}/responder', [NotificacaoFiscalizacaoController::class, 'responder'])
            ->name('responder')->whereNumber('notificacao');
        Route::delete('/{notificacao}', [NotificacaoFiscalizacaoController::class, 'destroy'])
            ->name('destroy')->whereNumber('notificacao');
    });

    /* QR Code autenticado */
    Route::prefix('qrcode')->name('qrcode.')->group(function (): void {
        Route::get('/vistoria/{vistoria}/pdf', [QrCodeController::class, 'pdfIndividual'])
            ->name('pdf-individual')->whereNumber('vistoria');
        Route::post('/pdf-em-lote', [QrCodeController::class, 'pdfEmLote'])->name('pdf-em-lote');
        Route::get('/folhas-vazias', [QrCodeController::class, 'folhasVazias'])->name('folhas-vazias');
    });
});
