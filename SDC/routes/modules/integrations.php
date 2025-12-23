<?php

use App\Http\Controllers\Api\V1\Integracao\IntegracaoController;
use App\Http\Controllers\Api\V1\Integration\DynamicIntegrationController;
use App\Http\Controllers\Api\V1\PowerBI\TokenController;
use Illuminate\Support\Facades\Route;

// Integração entre módulos (RAT <-> PAE)
Route::prefix('integracao')->name('api.v1.integracao.')->group(function () {
    Route::get('rat/{ratId}/pae', [IntegracaoController::class, 'getPaeByRat'])->name('rat.pae');
    Route::get('pae/{paeId}/rat', [IntegracaoController::class, 'getRatByPae'])->name('pae.rat');
});

// Power BI - tokens e proxy
Route::prefix('power-bi')->name('api.v1.power-bi.')->group(function () {
    Route::post('token', [TokenController::class, 'generateToken'])->name('token.generate');
    Route::get('token/{token}', [TokenController::class, 'validateToken'])->name('token.validate');
    Route::get('tokens', [TokenController::class, 'listTokens'])->name('tokens.list');

    Route::match(['get', 'post', 'put', 'patch', 'delete'], 'proxy/{api}/{path}', [\App\Http\Controllers\Api\V1\PowerBI\ProxyController::class, 'proxy'])
        ->where('path', '.*')
        ->name('proxy');
});

// Hub de Integração Dinâmica
Route::prefix('integration')->name('api.v1.integration.')->group(function () {
    Route::post('execute', [DynamicIntegrationController::class, 'execute'])->name('execute');
    Route::get('status/{integrationId}', [DynamicIntegrationController::class, 'status'])->name('status');
    Route::get('templates', [DynamicIntegrationController::class, 'templates'])->name('templates');
});


