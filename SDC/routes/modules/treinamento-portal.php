<?php

use App\Modules\Treinamento\Controllers\CertificadoImprimirController;
use App\Modules\Treinamento\Controllers\Portal\AuthenticatedSessionController;
use App\Modules\Treinamento\Controllers\Portal\CatalogoController;
use App\Modules\Treinamento\Controllers\Portal\CertificadoController;
use App\Modules\Treinamento\Controllers\Portal\InscricaoController;
use App\Modules\Treinamento\Controllers\Portal\MinhasInscricoesController;
use App\Modules\Treinamento\Controllers\Portal\PresencaAutoconfirmacaoController;
use App\Modules\Treinamento\Controllers\Portal\RegisterController;
use App\Modules\Treinamento\Controllers\Portal\SalaDeAulaController;
use App\Modules\Treinamento\Controllers\Portal\SalaEntregaStoreController;
use App\Modules\Treinamento\Controllers\Portal\SalaItemArquivoController;
use App\Modules\Treinamento\Controllers\Portal\SalaProgressoController;
use App\Modules\Treinamento\Controllers\Portal\SalaProvaEnviarController;
use App\Modules\Treinamento\Controllers\Portal\SalaProvaIniciarController;
use App\Modules\Treinamento\Controllers\Portal\SalaProvaResponderController;
use App\Modules\Treinamento\Controllers\Portal\SalaProvaShowController;
use App\Modules\Treinamento\Controllers\Portal\VerificarEmailController;
use App\Modules\Treinamento\Controllers\SessaoPulsoController;
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

    // Pulso do player de video: sem ShareCidadaoInertiaData, a resposta e 204 sem corpo.
    Route::post('/sessao/pulso', SessaoPulsoController::class)
        ->middleware(['auth:cidadao', 'throttle:30,1,ead-pulso'])
        ->name('sessao.pulso');

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
        // Sala de aula do EAD (cidadao): o acesso a cada conteudo e decidido no
        // backend pela trilha, nunca pelo front.
        Route::get('/cursos/{slug}', SalaDeAulaController::class)->name('cursos.sala');
        Route::get('/cursos/{slug}/itens/{item}/arquivo', SalaItemArquivoController::class)->name('cursos.itens.arquivo');
        Route::post('/cursos/{slug}/itens/{item}/progresso', SalaProgressoController::class)
            ->middleware('throttle:120,1,ead-progresso')
            ->name('cursos.itens.progresso');
        Route::post('/cursos/{slug}/itens/{item}/entregas', SalaEntregaStoreController::class)
            ->middleware('throttle:10,1,ead-entrega')
            ->name('cursos.itens.entregas');
        Route::prefix('/cursos/{slug}/itens/{item}/prova')->name('cursos.itens.prova')->group(function () {
            Route::get('/', SalaProvaShowController::class);
            Route::post('/tentativas', SalaProvaIniciarController::class)->name('.iniciar');
            Route::put('/tentativas/{tentativa}/respostas', SalaProvaResponderController::class)
                ->middleware('throttle:120,1,ead-prova-resposta')
                ->name('.responder');
            Route::post('/tentativas/{tentativa}/enviar', SalaProvaEnviarController::class)
                ->middleware('throttle:10,1,ead-prova-envio')
                ->name('.enviar');
        });

        Route::get('/certificados', CertificadoController::class)->name('certificados.index');
        Route::get('/certificados/{certificado}/imprimir', CertificadoImprimirController::class)->name('certificados.imprimir');
    });
});
