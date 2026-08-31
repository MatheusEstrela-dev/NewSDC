<?php

use App\Modules\Treinamento\Controllers\CertificadoImprimirController;
use App\Modules\Treinamento\Controllers\Portal\AuthenticatedSessionController;
use App\Modules\Treinamento\Controllers\Portal\CatalogoController;
use App\Modules\Treinamento\Controllers\Portal\CertificadoController;
use App\Modules\Treinamento\Controllers\Portal\InscricaoController;
use App\Modules\Treinamento\Controllers\Portal\MinhasInscricoesController;
use App\Modules\Treinamento\Controllers\Portal\PresencaAutoconfirmacaoController;
use App\Modules\Treinamento\Controllers\Portal\RegisterController;
use App\Modules\Treinamento\Controllers\Portal\VerificarEmailController;
use App\Modules\Treinamento\Http\Middleware\DisableDebugbarOnPortal;
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

Route::prefix('portal-treinamento')->name('portal.treinamento.')->middleware(DisableDebugbarOnPortal::class)->group(function () {

    // Login e unificado em /login (App\Http\Controllers\Auth\AuthenticatedSessionController
    // decide o guard pelo CPF) - o portal so tem cadastro e as telas pos-login.
    Route::get('/registrar', [RegisterController::class, 'create'])->name('registrar');
    // Duas camadas de rate limit, mesma logica do login web (ver routes/auth.php):
    // o limite que o cidadao legitimo encosta e o de dentro do
    // RegisterCidadaoRequest, que lanca ValidationException -> mensagem discreta
    // inline no formulario. Este 'portal-registro' e so o teto de abuso, bem mais
    // alto, pensado em NAT institucional. O antigo 'throttle:register' era 3/min
    // por IP e dividia o bucket com o /register interno (ja removido).
    Route::post('/registrar', [RegisterController::class, 'store'])
        ->middleware('throttle:portal-registro');

    // Confirmacao do codigo de 6 numeros enviado no cadastro. FORA do grupo
    // 'auth:cidadao' de proposito: quem esta aqui ainda nao autenticou, e esse e
    // justamente o gate. A conta em verificacao vem da session, nao da URL (ver
    // VerificarEmailController::SESSION_KEY), entao nao ha id para adulterar.
    Route::get('/verificar-email', [VerificarEmailController::class, 'create'])
        ->name('verificar-email');
    Route::post('/verificar-email', [VerificarEmailController::class, 'store'])
        ->name('verificar-email.store')
        ->middleware('throttle:portal-registro');
    Route::post('/verificar-email/reenviar', [VerificarEmailController::class, 'resend'])
        ->name('verificar-email.reenviar')
        ->middleware('throttle:portal-registro');

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
