<?php

use App\Modules\Treinamento\Controllers\Admin\CertificadoCancelarController;
use App\Modules\Treinamento\Controllers\CapaController;
use App\Modules\Treinamento\Controllers\Catalogo\CatalogoIndexController;
use App\Modules\Treinamento\Controllers\Catalogo\CatalogoStoreController;
use App\Modules\Treinamento\Controllers\Catalogo\CatalogoUpdateController;
use App\Modules\Treinamento\Controllers\Edital\ConsultaEditalController;
use App\Modules\Treinamento\Controllers\Edital\ConsultaEditalExportController;
use App\Modules\Treinamento\Controllers\Admin\CertificadoIndexController;
use App\Modules\Treinamento\Controllers\Admin\CertificadoReemitirController;
use App\Modules\Treinamento\Controllers\Admin\InscricaoAprovarController;
use App\Modules\Treinamento\Controllers\Admin\InscricaoIndexController;
use App\Modules\Treinamento\Controllers\Admin\InscricaoReprovarController;
use App\Modules\Treinamento\Controllers\Admin\LiberacaoInscricaoStoreController;
use App\Modules\Treinamento\Controllers\Admin\PresencaManualController;
use App\Modules\Treinamento\Controllers\Admin\PresencaQrController;
use App\Modules\Treinamento\Controllers\Admin\PresencaRosterController;
use App\Modules\Treinamento\Controllers\Admin\PresencaSincronizarController;
use App\Modules\Treinamento\Controllers\CertificadoImprimirController;
use App\Modules\Treinamento\Controllers\IndicadoresController;
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
use App\Modules\Treinamento\Controllers\Categoria\CapaDeCategoriaDestroyController;
use App\Modules\Treinamento\Controllers\Categoria\CapaDeCategoriaStoreController;
use App\Modules\Treinamento\Controllers\TreinamentoStoreController;
use App\Modules\Treinamento\Controllers\TreinamentoTransicionarStatusController;
use App\Modules\Treinamento\Controllers\TreinamentoUpdateController;
use App\Modules\Treinamento\Controllers\ValidarParticipanteConsultarController;
use App\Modules\Treinamento\Controllers\ValidarParticipanteController;
use App\Modules\Treinamento\Controllers\Versoes\NovaVersaoStoreController;
use App\Modules\Treinamento\Controllers\CertificadoInstrutor\CertificadoInstrutorImprimirController;
use App\Modules\Treinamento\Controllers\CertificadoInstrutor\CertificadoInstrutorStoreController;
use App\Modules\Treinamento\Controllers\CertificadoInstrutor\MeuCertificadoInstrutorController;
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
    // Curso EAD: area do aluno servidor e area do professor (inclui a designacao
    // de professores pelo admin). Antes do /{id} pelo mesmo motivo do /create.
    // Capa do curso: quem ve a lista de cursos (aluno, professor ou admin) ve a imagem.
    Route::get('/{treinamento}/capa', CapaController::class)
        ->middleware('permission:treinamento.cursos.view|treinamento.professor.acessar|treinamento.cursos.edit')
        ->whereNumber('treinamento')
        ->name('capa');
    // Capa das pastas de categoria da vitrine: so quem administra cursos troca.
    // A imagem em si e servida pela rota publica treinamentos.categorias.capa (web.php).
    Route::post('/categorias/{categoria}/capa', CapaDeCategoriaStoreController::class)
        ->middleware('can:treinamento.cursos.edit')
        ->name('categorias.capa.store');
    Route::delete('/categorias/{categoria}/capa', CapaDeCategoriaDestroyController::class)
        ->middleware('can:treinamento.cursos.edit')
        ->name('categorias.capa.destroy');

    require __DIR__ . '/treinamento-aluno.php';
    require __DIR__ . '/treinamento-professor.php';

    // Catalogo de cursos (o curso acima das turmas): quem edita cursos cuida do
    // catalogo. Antes do /{id}, como as demais rotas de nome fixo.
    Route::middleware('can:treinamento.cursos.edit')->prefix('catalogo')->name('catalogo.')->group(function () {
        Route::get('/', CatalogoIndexController::class)->name('index');
        Route::post('/', CatalogoStoreController::class)->name('store');
        Route::put('/{curso}', CatalogoUpdateController::class)->whereNumber('curso')->name('update');
    });

    // Consulta para edital: quem (e qual municipio) concluiu determinado(s)
    // curso(s). Mesmo publico do painel de indicadores.
    Route::middleware('can:treinamento.indicadores.view')->group(function () {
        Route::get('/consulta-edital', ConsultaEditalController::class)->name('consulta-edital');
        Route::get('/consulta-edital/exportar', ConsultaEditalExportController::class)->name('consulta-edital.exportar');
    });

    // Liberacao de CPF bloqueado pela regra das 3 inscricoes no mesmo curso.
    Route::post('/inscricoes/liberacoes', LiberacaoInscricaoStoreController::class)
        ->name('inscricoes.liberar')
        ->middleware('can:treinamento.inscricoes.manage');

    // Painel de indicadores: antes do /{id}, senao "indicadores" cairia no show.
    Route::get('/indicadores', IndicadoresController::class)
        ->name('indicadores')
        ->middleware('can:treinamento.indicadores.view');

    // Validador de credencial por QR Code (equipe da CEDEC); antes do /{id}.
    Route::get('/validar-participante', ValidarParticipanteController::class)
        ->name('validar-participante')
        ->middleware('can:treinamento.participantes.validar');
    Route::get('/validar-participante/{token}', ValidarParticipanteConsultarController::class)
        ->name('validar-participante.consultar')
        ->middleware('can:treinamento.participantes.validar');

    // Antes do /{id} de proposito: senao o wildcard casaria com "create" primeiro.
    Route::get('/create', TreinamentoCreateController::class)
        ->name('create')
        ->middleware('can:treinamento.cursos.create');
    // Tambem antes do /{id}, pelo mesmo motivo do /create.
    // Issue #86: o formulario do curso e configuracao ESTRUTURAL -> exige
    // tambem `treinamento.cursos.configurar`. Admin e manager ja recebem os
    // dois slugs no config, entao ninguem perde acesso de forma silenciosa.
    Route::get('/{id}/edit', TreinamentoEditController::class)
        ->name('edit')
        ->middleware(['can:treinamento.cursos.edit', 'can:treinamento.cursos.configurar']);
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

    // Certificado de instrutor: o professor abre o proprio (a primeira abertura
    // emite) e o administrador emite para professor ou, no presencial, por nome.
    Route::get('/certificados-instrutor/{certificadoInstrutor}/imprimir', CertificadoInstrutorImprimirController::class)
        ->name('certificados-instrutor.imprimir');
    Route::get('/{treinamento}/meu-certificado-instrutor', MeuCertificadoInstrutorController::class)
        ->name('certificados-instrutor.meu');
    Route::post('/{treinamento}/certificados-instrutor', CertificadoInstrutorStoreController::class)
        ->name('certificados-instrutor.store')
        ->middleware('can:treinamento.certificados.download');

    // Admin - Gestao de Treinamentos
    Route::middleware('can:treinamento.cursos.create')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::post('/', TreinamentoStoreController::class)->name('store');
        });

    Route::middleware('can:treinamento.cursos.edit')->group(function () {
        // Gravacao do formulario do curso: mesma exigencia do /edit.
        Route::put('/{id}', TreinamentoUpdateController::class)
            ->name('update')
            ->middleware('can:treinamento.cursos.configurar');
        Route::post('/{treinamento}/publicar', TreinamentoPublicarController::class)->name('publicar');
        // Proxima versao do curso EAD (rascunho copiado da vigente).
        Route::post('/{treinamento}/versoes', NovaVersaoStoreController::class)->name('versoes.store');
        // Issue #86: o destino vem no corpo. `transicao.status.curso` exige
        // `treinamento.cursos.cancelar` so quando o destino e CANCELADO; as
        // demais transicoes continuam com `treinamento.cursos.edit`.
        Route::post('/{treinamento}/status', TreinamentoTransicionarStatusController::class)
            ->name('status')
            ->middleware('transicao.status.curso');
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
    Route::post('/certificados/{certificado}/cancelar', CertificadoCancelarController::class)
        ->name('certificados.cancelar')
        ->middleware('can:treinamento.certificados.cancelar');
});
