<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthCheckController;

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

// API v1 - modularizada (TASK4) + apiResource (TASK3)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    require __DIR__.'/modules/pae.php';
    require __DIR__.'/modules/rat.php';
    require __DIR__.'/modules/bi.php';
    require __DIR__.'/modules/integrations.php';
    require __DIR__.'/modules/webhooks.php';
    require __DIR__.'/modules/system.php';
    require __DIR__.'/modules/admin.php';
});
