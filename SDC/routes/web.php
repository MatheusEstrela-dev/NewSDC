<?php

use App\Http\Controllers\ProfileController;
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

    Route::get('/pae', function () {
        return Inertia::render('Pae');
    })->name('pae.index');

    Route::get('/rat', function () {
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
            'lastUpdate' => now()->format('d/m/Y H:i'),
        ]);
    })->name('rat.index');

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
