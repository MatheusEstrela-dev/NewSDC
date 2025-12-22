<?php

use App\Http\Controllers\ProfileController;
use App\Modules\Rat\Presentation\Http\Controllers\RatIndexController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    // Redireciona para a página de login como página inicial
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // DEMANDAS - Listagem (placeholder)
    Route::get('/demandas', function () {
        return Inertia::render('DemandasIndex');
    })->name('demandas.index');

    // PAE - Protocolos (listagem)
    Route::get('/pae/protocolo', function () {
        return Inertia::render('PaeProtocolosIndex');
    })->name('pae.protocolos.index');

    Route::get('/pae', function () {
        return Inertia::render('Pae');
    })->name('pae.index');

    // Listagem de RATs
    Route::get('/rat', [RatIndexController::class, 'index'])->name('rat.index');

    // Criar novo RAT
    Route::get('/rat/create', function () {
        return Inertia::render('Rat', [
            'rat' => [
                'id' => null,
                'protocolo' => '',
                'status' => 'rascunho',
                'tem_vistoria' => false,
                'dadosGerais' => [
                    'data_fato' => '',
                    'data_inicio_atividade' => '',
                    'data_termino_atividade' => '',
                    'nat_cobrade_id' => '',
                    'nat_nome_operacao' => '',
                    'local_municipio' => '',
                ],
            ],
            'recursos' => [],
            'envolvidos' => [],
            'vistoria' => [],
            'historyEvents' => [],
            'anexos' => [],
            'lastUpdate' => now()->format('d/m/Y H:i'),
        ]);
    })->name('rat.create');

    // Visualizar/Editar RAT existente
    Route::get('/rat/{id}', function ($id) {
        // TODO: Buscar RAT do banco de dados pelo ID
        return Inertia::render('Rat', [
            'rat' => [
                'id' => $id,
                'protocolo' => 'RAT-2024-001',
                'status' => 'em_andamento',
                'tem_vistoria' => false,
                'dadosGerais' => [
                    'data_fato' => now()->subDays(3)->format('Y-m-d\TH:i'),
                    'data_inicio_atividade' => now()->subDays(3)->format('Y-m-d\TH:i'),
                    'data_termino_atividade' => '',
                    'nat_cobrade_id' => '',
                    'nat_nome_operacao' => '',
                    'local_municipio' => '',
                ],
            ],
            'recursos' => [],
            'envolvidos' => [],
            'vistoria' => [],
            'historyEvents' => [],
            'anexos' => [],
            'lastUpdate' => now()->format('d/m/Y H:i'),
        ]);
    })->name('rat.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Log Viewer - Visualizador de Logs do Sistema
    Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index')->name('logs.index');

    // Health Check Dashboard - Visualizador de Saúde do Sistema
    Route::get('health-dashboard', function () {
        return view('health-dashboard');
    })->name('health.dashboard');
});

require __DIR__.'/auth.php';
