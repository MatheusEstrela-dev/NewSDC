<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Pae\EmpreendimentoController;
use App\Http\Controllers\Api\V1\Rat\ProtocoloController;
use App\Http\Controllers\Api\V1\Integracao\IntegracaoController;
use App\Http\Controllers\Api\V1\PowerBI\TokenController;
use App\Http\Controllers\Api\V1\BI\EntradaController;
use App\Http\Controllers\Api\V1\Webhook\WebhookController;
use App\Http\Controllers\Api\V1\Integration\DynamicIntegrationController;
use App\Http\Controllers\Api\HealthCheckController;
use App\Http\Controllers\Api\LogViewerController;

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

// ============================================================================
// MONITORING & HEALTH CHECK (Rotas Públicas)
// ============================================================================

// Health Checks (sem autenticação - para load balancers)
Route::get('/health', [HealthCheckController::class, 'basic'])->name('health.basic');
Route::get('/health/detailed', [HealthCheckController::class, 'detailed'])->name('health.detailed');
Route::get('/health/metrics', [HealthCheckController::class, 'metrics'])->name('health.metrics');

// ============================================================================
// AUTHENTICATION & AUTHORIZATION (Bearer Token)
// ============================================================================

// Public auth routes (no authentication required)
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('register', [\App\Http\Controllers\Api\AuthController::class, 'register'])->name('register');
    Route::post('login', [\App\Http\Controllers\Api\AuthController::class, 'login'])->name('login');
});

// Protected auth routes (authentication required)
Route::prefix('auth')->middleware('auth:sanctum')->name('auth.')->group(function () {
    Route::post('logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->name('logout');
    Route::post('logout-all', [\App\Http\Controllers\Api\AuthController::class, 'logoutAll'])->name('logout-all');
    Route::get('me', [\App\Http\Controllers\Api\AuthController::class, 'me'])->name('me');
    Route::post('refresh', [\App\Http\Controllers\Api\AuthController::class, 'refresh'])->name('refresh');
    Route::get('tokens', [\App\Http\Controllers\Api\AuthController::class, 'tokens'])->name('tokens');
    Route::delete('tokens/{tokenId}', [\App\Http\Controllers\Api\AuthController::class, 'revokeToken'])->name('tokens.revoke');
});

// Legacy V1 auth routes (mantido para compatibilidade)
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

    // Webhooks - Sistema de alta performance para 100k+ usuários
    Route::prefix('webhooks')->name('api.v1.webhooks.')->group(function () {

        // Receber webhooks (com rate limiting tier webhook)
        Route::post('receive', [WebhookController::class, 'receive'])
            ->middleware('throttle:webhook')
            ->name('receive')
            ->withoutMiddleware('auth:sanctum'); // Permite webhooks externos

        // Enviar webhooks (assíncrono via filas)
        Route::post('send', [WebhookController::class, 'send'])
            ->middleware('throttle:enterprise')
            ->name('send');

        // Enviar webhooks síncronos (apenas para testes/emergências)
        Route::post('send-sync', [WebhookController::class, 'sendSync'])
            ->middleware('throttle:premium')
            ->name('send-sync');
    });

    // Hub de Integração Dinâmica - Plug-and-Play com sistemas externos
    Route::prefix('integration')->name('api.v1.integration.')->group(function () {

        // Executar integração (síncrona ou assíncrona)
        Route::post('execute', [DynamicIntegrationController::class, 'execute'])
            ->middleware('throttle:enterprise')
            ->name('execute');

        // Verificar status de integração assíncrona
        Route::get('status/{integrationId}', [DynamicIntegrationController::class, 'status'])
            ->middleware('throttle:default')
            ->name('status');

        // Listar templates pré-configurados
        Route::get('templates', [DynamicIntegrationController::class, 'templates'])
            ->middleware('throttle:default')
            ->name('templates');
    });

    // Log Viewer - Visualização de logs em tempo real
    Route::prefix('logs')->name('api.v1.logs.')->group(function () {

        // Logs recentes
        Route::get('recent', [LogViewerController::class, 'recent'])
            ->middleware('throttle:default')
            ->name('recent');

        // Métricas de logs
        Route::get('metrics', [LogViewerController::class, 'metrics'])
            ->middleware('throttle:default')
            ->name('metrics');

        // Logs de erros
        Route::get('errors', [LogViewerController::class, 'errors'])
            ->middleware('throttle:default')
            ->name('errors');

        // Stream de logs em tempo real (SSE)
        Route::get('stream', [LogViewerController::class, 'stream'])
            ->middleware('throttle:premium')
            ->name('stream');
    });
});
