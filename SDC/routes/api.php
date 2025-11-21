<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Pae\EmpreendimentoController;
use App\Http\Controllers\Api\V1\Rat\ProtocoloController;
use App\Http\Controllers\Api\V1\Integracao\IntegracaoController;
use App\Http\Controllers\Api\V1\PowerBI\TokenController;
use App\Http\Controllers\Api\V1\BI\EntradaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Autenticação (sem middleware auth)
Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'login']);
    Route::post('/logout', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'me'])->middleware('auth:sanctum');
});

// API v1
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // Módulo PAE
    Route::prefix('pae')->name('api.v1.pae.')->group(function () {
        Route::apiResource('empreendimentos', EmpreendimentoController::class);
    });
    
    // Módulo RAT
    Route::prefix('rat')->name('api.v1.rat.')->group(function () {
        Route::apiResource('protocolos', ProtocoloController::class);
    });
    
    // Integração entre Módulos
    Route::prefix('integracao')->name('api.v1.integracao.')->group(function () {
        Route::get('rat/{ratId}/pae', [IntegracaoController::class, 'getPaeByRat'])->name('rat.pae');
        Route::get('pae/{paeId}/rat', [IntegracaoController::class, 'getRatByPae'])->name('pae.rat');
    });
    
    // BI - Dados de Entrada
    Route::prefix('bi')->name('api.v1.bi.')->group(function () {
        Route::get('entrada', [EntradaController::class, 'index'])->name('entrada.index');
        Route::get('entrada/{id}', [EntradaController::class, 'show'])->name('entrada.show');
    });
    
    // Power BI - Gerenciamento de Tokens para múltiplas APIs
    Route::prefix('power-bi')->name('api.v1.power-bi.')->group(function () {
        Route::post('token', [TokenController::class, 'generateToken'])->name('token.generate');
        Route::get('token/{token}', [TokenController::class, 'validateToken'])->name('token.validate');
        Route::get('tokens', [TokenController::class, 'listTokens'])->name('tokens.list');
        
        // Proxy para acessar APIs externas
        Route::match(['get', 'post', 'put', 'patch', 'delete'], 'proxy/{api}/{path}', [\App\Http\Controllers\Api\V1\PowerBI\ProxyController::class, 'proxy'])
            ->where('path', '.*')
            ->name('proxy');
    });
});
