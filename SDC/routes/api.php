<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AjudaHumanitaria\EstoqueApiController as AhEstoqueApiController;
use App\Http\Controllers\Api\V1\AjudaHumanitaria\LiberacaoApiController as AhLiberacaoApiController;
use App\Http\Controllers\Api\V1\Cisterna\BeneficiarioApiController as CisternaBeneficiarioApiController;
use App\Http\Controllers\Api\V1\Pae\EmpreendimentoController;
use App\Http\Controllers\Api\V1\Pae\NotificacaoController;
use App\Http\Controllers\Api\V1\Rat\HistoricoController as RatHistoricoApiController;
use App\Http\Controllers\Api\V1\Rat\OcorrenciaController as RatOcorrenciaApiController;
use App\Http\Controllers\Api\V1\Rat\ProtocoloController;
use App\Http\Controllers\Api\V1\Integracao\IntegracaoController;
use App\Http\Controllers\Api\V1\PowerBI\TokenController;
use App\Http\Controllers\Api\V1\BI\EntradaController;
use App\Http\Controllers\Api\V1\Webhook\WebhookController;
use App\Http\Controllers\Api\V1\Integration\DynamicIntegrationController;
use App\Http\Controllers\Api\HealthCheckController;
use App\Http\Controllers\Api\LogViewerController;
use App\Http\Controllers\Api\V1\LogViewerController as LogViewerV1Controller;
use App\Http\Controllers\Api\RatNovoController;
use App\Http\Controllers\Api\RatAuditController;
use App\Http\Controllers\Api\V1\Decretacoes\DecretacoesApiController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\NotificationPreferencesController;
use App\Http\Controllers\ActivityFeedController;

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

Route::middleware(['auth:sanctum', \App\Http\Middleware\CheckUserActive::class])->get('/user', function (Request $request) {
    return $request->user();
});

// ============================================================================
// GLOBAL SEARCH (API - para uso externo/sanctum)
// ============================================================================

Route::middleware(['auth:sanctum', \App\Http\Middleware\CheckUserActive::class])
    ->get('/global-search', [GlobalSearchController::class, 'search'])
    ->middleware('throttle:30,1')
    ->name('api.global.search');

// ============================================================================
// MONITORING & HEALTH CHECK (Rotas Públicas)
// ============================================================================

// Health Checks (sem autenticação - para load balancers)
Route::middleware('statement_timeout:2000')->group(function () {
    Route::get('/health', [HealthCheckController::class, 'basic'])->name('health.basic');
    // Metricas Prometheus para scrape pelo monitoramento (sem auth, ACL via IP allowlist no proxy).
    Route::get('/metrics', \App\Http\Controllers\Api\MetricsController::class)->name('metrics');
});
Route::middleware(['auth:sanctum', \App\Http\Middleware\CheckUserActive::class, 'can:system.logs.view', 'statement_timeout:2000'])->group(function () {
    Route::get('/health/detailed', [HealthCheckController::class, 'detailed'])->name('health.detailed');
    Route::get('/health/metrics', [HealthCheckController::class, 'metrics'])->name('health.metrics');
});

// ============================================================================
// AUTHENTICATION & AUTHORIZATION (Bearer Token)
// ============================================================================

// Public auth routes (no authentication required)
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('register', [\App\Http\Controllers\Api\AuthController::class, 'register'])->middleware('throttle:register')->name('register');
    Route::post('login', [\App\Http\Controllers\Api\AuthController::class, 'login'])->middleware('throttle:login')->name('login');
});

// Protected auth routes (authentication required)
Route::prefix('auth')->middleware(['auth:sanctum', \App\Http\Middleware\CheckUserActive::class])->name('auth.')->group(function () {
    Route::post('logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->name('logout');
    Route::post('logout-all', [\App\Http\Controllers\Api\AuthController::class, 'logoutAll'])->name('logout-all');
    Route::get('me', [\App\Http\Controllers\Api\AuthController::class, 'me'])->name('me');
    Route::post('refresh', [\App\Http\Controllers\Api\AuthController::class, 'refresh'])->name('refresh');
    Route::get('tokens', [\App\Http\Controllers\Api\AuthController::class, 'tokens'])->name('tokens');
    Route::delete('tokens/{tokenId}', [\App\Http\Controllers\Api\AuthController::class, 'revokeToken'])->name('tokens.revoke');
});

// Legacy V1 auth routes (mantido para compatibilidade)
Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/logout', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'logout'])->middleware(['auth:sanctum', \App\Http\Middleware\CheckUserActive::class]);
    Route::get('/me', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'me'])->middleware(['auth:sanctum', \App\Http\Middleware\CheckUserActive::class]);
});

// API v1
// Protecao de conexao DB: AcquireConnectionSlot/Backpressure foram retirados do
// grupo 'api' global de proposito. Sob Swoole hooks-off a concorrencia real e
// limitada por OCTANE_WORKERS (1 conexao pgsql/worker), e o guardrail do
// entrypoint garante workers x instancias <= max_connections -- entao o PG nao
// satura por conexao sem precisar do semaforo. Re-adicionar o slot aqui seria
// inerte (limit=DB_MAX_CONCURRENT=100 nunca e atingido com ~10-12 workers) e so
// somaria round-trips Redis por request. Se um dia for preciso SHEDDING explicito
// (503) em vez de fila no worker, reduza DB_MAX_CONCURRENT abaixo do total de
// workers E aplique 'backpressure'+'acquire_slot' neste grupo.
Route::prefix('v1')->middleware(['auth:sanctum', \App\Http\Middleware\CheckUserActive::class, 'statement_timeout:10000'])->group(function () {

    // Activity Feed
    Route::get('activity-feed', [ActivityFeedController::class, 'index'])->name('api.v1.activity-feed');

    // Traces: status de requests assincronos despachados via AsynchronousResponse.
    Route::prefix('traces')->name('api.v1.traces.')->group(function () {
        Route::get('{traceId}', [\App\Http\Controllers\Api\V1\TraceController::class, 'show'])
            ->name('show')
            ->whereUuid('traceId');
        Route::get('{traceId}/download', [\App\Http\Controllers\Api\V1\TraceController::class, 'download'])
            ->name('download')
            ->whereUuid('traceId');
    });

    // Módulo PAE
    Route::prefix('pae')->name('api.v1.pae.')->group(function () {
        Route::get('empreendimentos', [EmpreendimentoController::class, 'index'])
            ->name('empreendimentos.index')
            ->middleware('can:pae.empreendimentos.view');
        Route::post('empreendimentos', [EmpreendimentoController::class, 'store'])
            ->name('empreendimentos.store')
            ->middleware('can:pae.empreendimentos.create');
        Route::get('empreendimentos/{id}', [EmpreendimentoController::class, 'show'])
            ->name('empreendimentos.show')
            ->whereNumber('id')
            ->middleware('can:pae.empreendimentos.view');
        Route::match(['put', 'patch'], 'empreendimentos/{id}', [EmpreendimentoController::class, 'update'])
            ->name('empreendimentos.update')
            ->whereNumber('id')
            ->middleware('can:pae.empreendimentos.edit');
        Route::delete('empreendimentos/{id}', [EmpreendimentoController::class, 'destroy'])
            ->name('empreendimentos.destroy')
            ->whereNumber('id')
            ->middleware('can:pae.empreendimentos.delete');

        Route::get('protocolos/{paeProtocolo}/notificacoes', [NotificacaoController::class, 'index'])
            ->name('protocolos.notificacoes.index')
            ->middleware('can:pae.protocolos.view');

        Route::post('protocolos/{paeProtocolo}/notificacoes', [NotificacaoController::class, 'store'])
            ->name('protocolos.notificacoes.store')
            ->middleware('can:pae.protocolos.edit');

        Route::post('notificacoes/{paeNotificacao}/devolutiva', [NotificacaoController::class, 'devolutiva'])
            ->name('notificacoes.devolutiva')
            ->middleware('can:pae.protocolos.edit');
    });

    // Modulo Ajuda Humanitaria: fornecimento de dados, somente leitura.
    // Paridade de contrato com os endpoints publicos do legado, agora sob token.
    Route::prefix('ajuda-humanitaria')->name('api.v1.ajuda-humanitaria.')->group(function () {
        Route::get('liberacoes', [AhLiberacaoApiController::class, 'index'])
            ->name('liberacoes.index')
            ->middleware(['can:humanitaria.saldo.view', 'throttle:60,1']);

        // Throttle mais apertado: este endpoint nao tem filtro obrigatorio e,
        // com a carga de itens completa, e a consulta mais pesada do modulo.
        Route::get('liberacoes/cedec', [AhLiberacaoApiController::class, 'cedec'])
            ->name('liberacoes.cedec')
            ->middleware(['can:humanitaria.saldo.view', 'throttle:30,1']);

        Route::get('estoque/saldo-cesta', [AhEstoqueApiController::class, 'saldoCesta'])
            ->name('estoque.saldo-cesta')
            ->middleware(['can:humanitaria.saldo.view', 'throttle:60,1']);
    });

    // Modulo Cisterna: fornecimento de dados, somente leitura.
    //
    // O recorte territorial nao cabe em middleware: depende do usuario dono do
    // token e, no `show`, da instancia do registro. `can:` cobre a permissao;
    // PerfilCisterna cobre o territorio nas listagens, e a policy no detalhe.
    Route::prefix('cisternas')->name('api.v1.cisternas.')->group(function (): void {

        Route::prefix('beneficiarios')->name('beneficiarios.')->group(function (): void {
            Route::get('/', [CisternaBeneficiarioApiController::class, 'index'])
                ->name('index')
                ->middleware('can:cisternas.beneficiarios.view');

            // Antes do /{beneficiario}: sem isto "export" casa com o parametro
            // e o whereNumber devolveria 404 em vez de servir o CSV.
            Route::get('/export', [CisternaBeneficiarioApiController::class, 'export'])
                ->name('export')
                ->middleware('can:cisternas.beneficiarios.export');

            // Sem `can:`: a policy view() checa a permissao E o territorio, e um
            // can: antes dela daria 403 sem distinguir os dois motivos.
            Route::get('/{beneficiario}', [CisternaBeneficiarioApiController::class, 'show'])
                ->name('show')
                ->whereNumber('beneficiario');
        });
    });

    // Módulo RAT — Protocolos (stub removido — rotas reais abaixo com auth dual)

    // Módulo RAT — Ocorrências (nova estrutura polimórfica, acesso mobile/API)
    // Requer permissão: rat.api.access
    Route::prefix('rat/ocorrencias')->name('api.v1.rat.ocorrencias.')->middleware('can:rat.api.access')->group(function () {
        Route::get('/',            [RatOcorrenciaApiController::class, 'index'])  ->name('index');
        Route::post('/',           [RatOcorrenciaApiController::class, 'store'])  ->name('store');
        Route::get('/{id}',        [RatOcorrenciaApiController::class, 'show'])   ->name('show');
        Route::put('/{id}',        [RatOcorrenciaApiController::class, 'update']) ->name('update');
        Route::patch('/{id}/finalizar', [RatOcorrenciaApiController::class, 'finalize'])->name('finalize');
        Route::delete('/{id}',     [RatOcorrenciaApiController::class, 'destroy'])->name('destroy');
    });

    // Módulo RAT — Histórico de ocorrência (timeline)
    Route::prefix('rat/ocorrencias/{id}/historico')->name('api.v1.rat.historico.')->middleware('can:rat.historico.view')->group(function () {
        Route::get('/',        [RatHistoricoApiController::class, 'index']) ->name('index');
        Route::get('/recent',  [RatHistoricoApiController::class, 'recent'])->name('recent');
    });

    // Módulo RAT — Nova Estrutura (RatOcorrencia + relatos polimórficos)
    Route::prefix('rat-novo')->name('api.v1.rat-novo.')->group(function () {
        Route::get('/',            [RatNovoController::class, 'index'])   ->name('index');
        Route::post('/',           [RatNovoController::class, 'store'])   ->name('store');
        Route::get('/{id}',        [RatNovoController::class, 'show'])    ->name('show');
        Route::put('/{id}',        [RatNovoController::class, 'update'])  ->name('update');
        Route::delete('/{id}',     [RatNovoController::class, 'destroy']) ->name('destroy');
        Route::get('/{id}/power-bi', [RatNovoController::class, 'powerBiData'])->name('power-bi');
    });

    // Módulo RAT — Auditoria
    Route::prefix('rat-audit')->name('api.v1.rat-audit.')->group(function () {
        Route::get('/', [RatAuditController::class, 'index'])->name('index');
        Route::get('/{id}', [RatAuditController::class, 'show'])->name('show');
    });

    // Integração entre Módulos
    Route::prefix('integracao')->name('api.v1.integracao.')->group(function () {
        Route::get('rat/{ratId}/pae', [IntegracaoController::class, 'getPaeByRat'])->name('rat.pae');
        Route::get('pae/{paeId}/rat', [IntegracaoController::class, 'getRatByPae'])->name('pae.rat');
    });

    // BI - Dados de Entrada (autenticacao Sanctum padrao)
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
    Route::prefix('webhooks')->name('api.v1.webhooks.')->middleware('statement_timeout:15000')->group(function () {

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

    // Preferencias de notificacao do usuario
    Route::get('notification-preferences', [NotificationPreferencesController::class, 'index']);
    Route::put('notification-preferences', [NotificationPreferencesController::class, 'update']);

    // Integracoes pessoais do usuario (Telegram, futuro WhatsApp).
    // Cada user gerencia suas proprias conexoes em Configuracoes > Integracoes.
    Route::prefix('integrations/telegram')->name('api.v1.integrations.telegram.')->group(function () {
        Route::post('connect', [\App\Http\Controllers\Api\V1\Integrations\TelegramController::class, 'connect'])->name('connect');
        Route::get('status',   [\App\Http\Controllers\Api\V1\Integrations\TelegramController::class, 'status'])->name('status');
        Route::delete('{id}',  [\App\Http\Controllers\Api\V1\Integrations\TelegramController::class, 'disconnect'])
            ->whereNumber('id')
            ->name('disconnect');
    });

    // (Logs movidos para fora do auth:sanctum)
});

// Webhook publico do bot Telegram (sem auth do app — secret no path).
// Configurar no Telegram via setWebhook apos deploy.
Route::post('integrations/telegram/webhook/{secret}', [\App\Http\Controllers\Api\V1\Integrations\TelegramWebhookController::class, 'handle'])
    ->name('integrations.telegram.webhook');

// Log Viewer - Sistema avancado de visualizacao de logs
// Protegido por autenticacao e permissao de logs.
Route::prefix('v1/logs')->name('api.v1.logs.')->middleware([
    'auth:sanctum',
    \App\Http\Middleware\CheckUserActive::class,
    'can:system.logs.view',
    'throttle:30,1',
])->group(function () {

    // Rota de teste para forcar erros (apenas em dev/local)
    if (app()->environment('local', 'development')) {
        Route::get('test-error', function () {
            $type = request()->query('type', 'exception');

            switch ($type) {
                case 'sql':
                    \Illuminate\Support\Facades\DB::select('SELECT * FROM tabela_inexistente_xyz');
                    break;
                case 'division':
                    $x = 1 / 0;
                    break;
                case 'null':
                    throw new \Error('Simulated null dereference: ' . now()->toIso8601String());
                case 'custom':
                    throw new \Exception('Erro de teste customizado: ' . now()->toIso8601String());
                default:
                    throw new \RuntimeException('Erro de teste padrao: ' . now()->toIso8601String());
            }

            return response()->json(['error' => 'Nao deveria chegar aqui']);
        })->name('test-error');
    }

    // Buscar logs com filtros avançados (data, tipo, nível, busca)
    Route::get('/', [LogViewerV1Controller::class, 'index'])->name('index');

    // Estatísticas agregadas dos logs (ambas as rotas funcionam)
    Route::get('statistics', [LogViewerV1Controller::class, 'statistics'])->name('statistics');
    Route::get('stats', [LogViewerV1Controller::class, 'statistics'])->name('stats');

    // Listar arquivos de log
    Route::get('files', [LogViewerV1Controller::class, 'files'])->name('files');

    // Download de arquivo de log
    Route::get('download/{filename}', [LogViewerV1Controller::class, 'download'])->name('download');

    // Logs recentes do Redis (tempo real)
    Route::get('recent', [LogViewerV1Controller::class, 'recent'])->name('recent');

    // Limpar logs antigos
    Route::delete('clean', [LogViewerV1Controller::class, 'clean'])->name('clean');

    // Níveis e tipos disponíveis
    Route::get('levels', [LogViewerV1Controller::class, 'levels'])->name('levels');

    // Canais disponíveis (alias para compatibilidade)
    Route::get('channels', [LogViewerV1Controller::class, 'levels'])->name('channels');

    // Camadas disponíveis
    Route::get('layers', [LogViewerV1Controller::class, 'layers'])->name('layers');

    // Stream de logs em tempo real (SSE) - legado
    Route::get('stream', [LogViewerController::class, 'stream'])->name('stream');
});

// ============================================================================
// DECRETACOES API — Autenticacao dupla (Sanctum + Power BI token)
// ============================================================================

// Rotas de leitura — limite alto (pro: 1000 creditos/min)
Route::prefix('v1/decretacoes')
    ->name('api.v1.decretacoes.')
    ->middleware([
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \App\Http\Middleware\Backpressure::class,
        \App\Http\Middleware\AcquireConnectionSlot::class,
        'decretacoes.api.auth',
        'api-rate-limiter:pro',
    ])
    ->group(function () {
        Route::get('/',                      [DecretacoesApiController::class, 'index'])->name('index');
        Route::get('/export/power-bi',       [DecretacoesApiController::class, 'exportPowerBI'])->name('export.power-bi');
        Route::get('/export/power-bi/async', [DecretacoesApiController::class, 'exportPowerBIAsync'])->name('export.power-bi.async');

        // Polling de trace assincrono no mesmo grupo (suporta triple auth via
        // DecretacoesApiAuth: session/Sanctum/PowerBI token). Permite que o
        // cliente PowerBI consulte status e baixe artefato do export async.
        Route::get('/traces/{traceId}',          [\App\Http\Controllers\Api\V1\TraceController::class, 'show'])
            ->name('traces.show')
            ->whereUuid('traceId');
        Route::get('/traces/{traceId}/download', [\App\Http\Controllers\Api\V1\TraceController::class, 'download'])
            ->name('traces.download')
            ->whereUuid('traceId');

        Route::get('/{id}',                  [DecretacoesApiController::class, 'show'])->name('show');
    });

// Rota de escrita — limite restrito (default: 300 creditos/min, protege contra abuso)
Route::prefix('v1/decretacoes')
    ->middleware([
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \App\Http\Middleware\Backpressure::class,
        \App\Http\Middleware\AcquireConnectionSlot::class,
        'decretacoes.api.auth',
        'api-rate-limiter:default',
    ])
    ->group(function () {
        Route::post('/receive', [DecretacoesApiController::class, 'receive'])->name('api.v1.decretacoes.receive');
    });

// ============================================================================
// RAT API — Autenticacao dupla (Sanctum + Power BI token)
// ============================================================================

// Rotas de leitura — limite alto (pro: 1000 creditos/min)
Route::prefix('v1/rat')
    ->name('api.v1.rat.')
    ->middleware([
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \App\Http\Middleware\Backpressure::class,
        \App\Http\Middleware\AcquireConnectionSlot::class,
        'decretacoes.api.auth',
        'api-rate-limiter:pro',
    ])
    ->group(function () {
        Route::get('protocolos',               [\App\Http\Controllers\Api\V1\Rat\ProtocoloController::class, 'index'])->name('protocolos.index');
        Route::get('protocolos/export/power-bi', [\App\Http\Controllers\Api\V1\Rat\ProtocoloController::class, 'exportPowerBI'])->name('protocolos.export.powerbi');
        Route::get('protocolos/{id}',          [\App\Http\Controllers\Api\V1\Rat\ProtocoloController::class, 'show'])->name('protocolos.show');
    });

// Rota de escrita — limite restrito (default: 300 creditos/min)
Route::prefix('v1/rat')
    ->name('api.v1.rat.')
    ->middleware([
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \App\Http\Middleware\Backpressure::class,
        \App\Http\Middleware\AcquireConnectionSlot::class,
        'decretacoes.api.auth',
        'api-rate-limiter:default',
    ])
    ->group(function () {
        Route::post('protocolos', [\App\Http\Controllers\Api\V1\Rat\ProtocoloController::class, 'receive'])->name('protocolos.receive');
    });

// ============================================================================
// TDAP API (PowerBI / BI integration) - Fase 5
// ============================================================================
Route::prefix('v1/tdap')
    ->name('api.v1.tdap.')
    ->middleware([
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \App\Http\Middleware\Backpressure::class,
        \App\Http\Middleware\AcquireConnectionSlot::class,
        'decretacoes.api.auth',
        'api-rate-limiter:pro',
    ])
    ->group(function () {
        Route::get('cronogramas', [\App\Http\Controllers\Api\V1\Tdap\TdapApiController::class, 'cronogramas'])
            ->name('cronogramas');
    });

// ============================================================================
// IA INTEGRATION CORE
// ============================================================================

Route::prefix('ai')->middleware('auth:sanctum')->group(function () {
    Route::post('chat', [\App\Core\IA\Http\Controllers\ChatController::class, 'chat']);
    Route::post('chat/stream', [\App\Core\IA\Http\Controllers\ChatController::class, 'stream']);
    Route::get('conversations', [\App\Core\IA\Http\Controllers\ChatController::class, 'conversations']);
    Route::get('conversations/{id}/messages', [\App\Core\IA\Http\Controllers\ChatController::class, 'messages']);
    Route::delete('conversations/{id}', [\App\Core\IA\Http\Controllers\ChatController::class, 'deleteConversation']);
    Route::get('tools', [\App\Core\IA\Http\Controllers\ChatController::class, 'tools']);
});

Route::prefix('ia')->middleware('auth:sanctum')->name('ia.')->group(function () {
    Route::get('plugins', [\App\Core\IA\Http\Controllers\AIPluginController::class, 'index'])->name('plugins.index');
    Route::post('execute-plugin', [\App\Core\IA\Http\Controllers\AIPluginController::class, 'execute'])->name('execute-plugin');
});

// ============================================================================
// DEV ONLY - AI Routes without authentication (remove in production)
// ============================================================================

if (app()->environment('local', 'development')) {
    Route::prefix('ai/dev')->group(function () {
        Route::post('chat', [\App\Core\IA\Http\Controllers\ChatController::class, 'chat']);
        Route::post('chat/stream', [\App\Core\IA\Http\Controllers\ChatController::class, 'stream']);
        Route::get('conversations', [\App\Core\IA\Http\Controllers\ChatController::class, 'conversations']);
        Route::get('conversations/{id}/messages', [\App\Core\IA\Http\Controllers\ChatController::class, 'messages']);
        Route::delete('conversations/{id}', [\App\Core\IA\Http\Controllers\ChatController::class, 'deleteConversation']);
        Route::get('status', function () {
            $driver = app(\App\Core\IA\AIService::class);
            $ollamaDriver = $driver->getDriver();

            return response()->json([
                'driver' => $ollamaDriver->getDriverName(),
                'model' => $ollamaDriver->getModel(),
                'available' => method_exists($ollamaDriver, 'isAvailable')
                    ? $ollamaDriver->isAvailable()
                    : true,
            ]);
        });
    });
}

// ============================================================================
// >>> TEMP DTO TEST — REMOVER APOS VALIDACAO (regra de ouro #6/#10) <<<
// Exercita Request -> validated() -> RatDadosGeraisDTO -> RatWriteService -> BD
// sem auth/CSRF. POST /api/_dev/rat-dados-gerais
// ============================================================================
if (app()->environment('local', 'development')) {
    Route::post('_dev/rat-dados-gerais', function (\App\Modules\Rat\Http\Requests\RatDadosGeraisRequest $request) {
        $validated = $request->validated();
        $dto       = \App\Modules\Rat\DTOs\RatDadosGeraisDTO::fromArray($validated);

        $ocorrencia = \App\Modules\Rat\Models\RatOcorrencia::create(['status' => 0]);
        $model      = app(\App\Modules\Rat\Services\RatWriteService::class)
            ->saveDadosGerais($ocorrencia->id, $dto);

        return response()->json([
            'etapa_1_validated'  => $validated,
            'etapa_2_dto_array'  => $dto->toArray(),
            'etapa_3_persistido' => $model->fresh(),
            'ocorrencia_id'      => $ocorrencia->id,
        ], 201);
    });
}
