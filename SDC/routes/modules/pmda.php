<?php

use App\Modules\Compdec\Models\CompdecAnexo;
use App\Modules\Compdec\Models\CompdecEquipe;
use App\Modules\Pmda\Controllers\CompdecAnexoController;
use App\Modules\Pmda\Controllers\CompdecEquipeController;
use App\Modules\Pmda\Controllers\CompdecFichaController;
use App\Modules\Pmda\Controllers\CompdecMembroController;
use App\Modules\Pmda\Controllers\ComunidadeController;
use App\Modules\Pmda\Controllers\ComunidadeSolicitacaoController;
use App\Modules\Pmda\Controllers\PlanoPontoController;
use App\Modules\Pmda\Controllers\PmdaAnaliseController;
use App\Modules\Pmda\Controllers\PmdaPlanoController;
use App\Modules\Pmda\Controllers\RepresentanteController;
use App\Modules\Pmda\Models\ComunidadeSolicitacao;
use App\Modules\Pmda\Models\PmdaComunidade;
use App\Modules\Pmda\Models\PmdaCompdecMembro;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Pmda\Models\PmdaRepresentante;
use Illuminate\Support\Facades\Route;

/*
| Route::model() e GLOBAL, nao vale so para as rotas deste arquivo. `comunidade`
| saiu daqui porque o Cisterna tem /cisternas/comunidades/{comunidade} (update e
| destroy), e o binder explicito vence o implicito no SubstituteBindings: as duas
| rotas resolviam contra PmdaComunidade e devolviam 404.
|
| Nao fazia falta: destroy() e o store() de representantes type-hintam
| PmdaComunidade, e o binding implicito ja resolve.
|
| `plano` saiu pelo mesmo motivo: compdec.php amarrava o mesmo nome a
| CompdecPlanoContingencia, e como pmda.php e carregado depois em web.php o
| binder daqui vencia e as 6 rotas {plano} do Compdec devolviam 404. Os 26
| handlers de plano daqui type-hintam PmdaPlano, entao o implicito resolve.
|
| `anexo` e `equipe` ficam: compdec.php amarra os MESMOS nomes as MESMAS classes,
| entao a duplicidade e inofensiva -- quem vencer resolve igual.
|
| Ao acrescentar Route::model() aqui, confira se o nome do parametro e exclusivo
| deste modulo.
*/
Route::model('anexo', CompdecAnexo::class);
Route::model('equipe', CompdecEquipe::class);
Route::model('solicitacao', ComunidadeSolicitacao::class);
Route::model('representante', PmdaRepresentante::class);
Route::model('membro', PmdaCompdecMembro::class);

Route::prefix('pmda')->name('pmda.')->group(function () {
    Route::prefix('planos')->name('planos.')->group(function () {
        Route::get('/', [PmdaPlanoController::class, 'index'])
            ->name('index')->middleware('can:pmda.planos.view');
        Route::get('/export', [PmdaPlanoController::class, 'export'])
            ->name('export')->middleware('can:pmda.planos.export');
        // `can:create,PmdaPlano` e nao `can:pmda.planos.create`: a PmdaPlanoPolicy
        // soma a permissao ao perfil (so COMPDEC com municipio cria -- issue #56).
        Route::get('/create', [PmdaPlanoController::class, 'create'])
            ->name('create')->middleware('can:create,'.PmdaPlano::class);
        Route::post('/', [PmdaPlanoController::class, 'store'])
            ->name('store')->middleware('can:create,'.PmdaPlano::class);
        Route::get('/{plano}/edit', [PmdaPlanoController::class, 'edit'])
            ->name('edit')->middleware('can:update,plano');
        // Continuacao da criacao (contexto Create) apos o 1o POST do Novo PMDA
        Route::get('/{plano}/continuar', [PmdaPlanoController::class, 'continuar'])
            ->name('continuar')->middleware(['can:pmda.planos.create', 'can:territorio,plano']);
        Route::put('/{plano}', [PmdaPlanoController::class, 'update'])
            ->name('update')->middleware('can:update,plano');
        Route::delete('/{plano}', [PmdaPlanoController::class, 'destroy'])
            ->name('destroy')->middleware('can:delete,plano');
        Route::post('/{plano}/copiar', [PmdaPlanoController::class, 'copiar'])
            ->name('copiar')->middleware(['can:pmda.planos.copiar', 'can:territorio,plano']);
        // Ficha COMPDEC (JSON) para impressao
        Route::get('/{plano}/ficha', [PmdaPlanoController::class, 'ficha'])
            ->name('ficha')->middleware('can:view,plano');
        // Serie historica / situacao geral (JSON) estilo PAE
        Route::get('/{plano}/historico', [PmdaPlanoController::class, 'historico'])
            ->name('historico')->middleware('can:view,plano');

        // Analise CEDEC: decisoes sobre o PMDA em analise
        Route::post('/{plano}/aprovar', [PmdaAnaliseController::class, 'aprovar'])
            ->name('aprovar')->middleware('can:pmda.analise.aprovar');
        Route::post('/{plano}/arquivar', [PmdaAnaliseController::class, 'arquivar'])
            ->name('arquivar')->middleware('can:pmda.analise.arquivar');
        Route::post('/{plano}/pedir-alteracao', [PmdaAnaliseController::class, 'pedirAlteracao'])
            ->name('pedir-alteracao')->middleware('can:pmda.analise.pedir_alteracao');

        // Etapa 7: anexos (PDF) e envio para analise
        Route::post('/{plano}/anexos', [PmdaPlanoController::class, 'storeAnexo'])
            ->name('anexos.store')->middleware(['can:pmda.anexos.create', 'can:territorio,plano']);
        Route::post('/{plano}/enviar', [PmdaPlanoController::class, 'enviar'])
            ->name('enviar')->middleware(['can:pmda.analise.enviar', 'can:territorio,plano']);

        // Etapa 3: equipe COMPDEC (membros)
        Route::post('/{plano}/membros', [CompdecMembroController::class, 'store'])
            ->name('membros.store')->middleware(['can:pmda.planos.edit', 'can:territorio,plano']);

        // Etapa 3: ficha cadastral do COMPDEC (registro mestre do municipio)
        Route::put('/{plano}/compdec-ficha', [CompdecFichaController::class, 'update'])
            ->name('compdec.update')->middleware(['can:pmda.planos.edit', 'can:territorio,plano']);
        Route::post('/{plano}/compdec-ficha/foto', [CompdecFichaController::class, 'uploadFoto'])
            ->name('compdec.foto.upload')->middleware(['can:pmda.planos.edit', 'can:territorio,plano']);
        Route::delete('/{plano}/compdec-ficha/foto', [CompdecFichaController::class, 'removerFoto'])
            ->name('compdec.foto.destroy')->middleware(['can:pmda.planos.edit', 'can:territorio,plano']);

        // Etapa 3: documentos (Leis e Decretos) do COMPDEC
        Route::post('/{plano}/compdec-ficha/anexos', [CompdecAnexoController::class, 'store'])
            ->name('compdec.anexos.store')->middleware(['can:pmda.planos.edit', 'can:territorio,plano']);
        Route::delete('/{plano}/compdec-ficha/anexos/{anexo}', [CompdecAnexoController::class, 'destroy'])
            ->name('compdec.anexos.destroy')->middleware(['can:pmda.planos.edit', 'can:territorio,plano']);
        Route::get('/{plano}/compdec-ficha/anexos/{anexo}/download', [CompdecAnexoController::class, 'download'])
            ->name('compdec.anexos.download')->middleware(['can:pmda.planos.edit', 'can:territorio,plano']);

        // Etapa 3: Editar Equipe COMPDEC (membros do orgao - ativos e inativos)
        Route::post('/{plano}/compdec-ficha/equipe', [CompdecEquipeController::class, 'store'])
            ->name('compdec.equipe.store')->middleware(['can:pmda.planos.edit', 'can:territorio,plano']);
        Route::put('/{plano}/compdec-ficha/equipe/{equipe}', [CompdecEquipeController::class, 'update'])
            ->name('compdec.equipe.update')->middleware(['can:pmda.planos.edit', 'can:territorio,plano']);
        Route::delete('/{plano}/compdec-ficha/equipe/{equipe}', [CompdecEquipeController::class, 'destroy'])
            ->name('compdec.equipe.destroy')->middleware(['can:pmda.planos.edit', 'can:territorio,plano']);

        // Comunidades do plano
        Route::post('/{plano}/comunidades', [ComunidadeController::class, 'store'])
            ->name('comunidades.store')->middleware(['can:pmda.comunidades.create', 'can:territorio,plano']);

        // Municipio solicita o cadastro de uma comunidade ainda inexistente
        Route::post('/{plano}/comunidades/solicitar', [ComunidadeSolicitacaoController::class, 'store'])
            ->name('comunidades.solicitar')->middleware(['can:pmda.comunidades.solicitar', 'can:territorio,plano']);

        // Pontos de captacao do plano
        Route::post('/{plano}/pontos', [PlanoPontoController::class, 'store'])
            ->name('pontos.store')->middleware(['can:pmda.pontos.create', 'can:territorio,plano']);
        Route::delete('/{plano}/pontos/{ponto}', [PlanoPontoController::class, 'destroy'])
            ->name('pontos.destroy')->middleware(['can:pmda.pontos.delete', 'can:territorio,plano']);
    });

    Route::delete('/comunidades/{comunidade}', [ComunidadeController::class, 'destroy'])
        ->name('comunidades.destroy')->middleware('can:pmda.comunidades.delete');

    // Central de Analises CEDEC (tela dividida): PMDA em analise + solicitacoes de comunidade
    Route::get('/analises', [PmdaAnaliseController::class, 'index'])
        ->name('analises.index')->middleware('can:pmda.analise.view');

    // Fila CEDEC: acoes sobre solicitacoes de inclusao de comunidade (painel direito)
    Route::post('/solicitacoes/{solicitacao}/aprovar', [ComunidadeSolicitacaoController::class, 'aprovar'])
        ->name('solicitacoes.aprovar')->middleware('can:pmda.comunidades.aprovar');
    Route::post('/solicitacoes/{solicitacao}/rejeitar', [ComunidadeSolicitacaoController::class, 'rejeitar'])
        ->name('solicitacoes.rejeitar')->middleware('can:pmda.comunidades.aprovar');

    // Membros COMPDEC
    Route::delete('/membros/{membro}', [CompdecMembroController::class, 'destroy'])
        ->name('membros.destroy')->middleware('can:pmda.planos.edit');

    // Representantes da comunidade
    Route::post('/comunidades/{comunidade}/representantes', [RepresentanteController::class, 'store'])
        ->name('representantes.store')->middleware('can:pmda.representantes.create');
    Route::put('/representantes/{representante}', [RepresentanteController::class, 'update'])
        ->name('representantes.update')->middleware('can:pmda.representantes.edit');
    Route::delete('/representantes/{representante}', [RepresentanteController::class, 'destroy'])
        ->name('representantes.destroy')->middleware('can:pmda.representantes.delete');
});
