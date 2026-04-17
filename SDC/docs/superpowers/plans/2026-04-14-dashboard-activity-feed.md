# Dashboard Activity Feed — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Conectar o bloco "Últimas Movimentações" (TimelineWidget) a dados reais do AuditLog, com polling 60s ou WebSocket Reverb configurável pelo usuário, e persistir as preferências de notificação por módulo no banco.

**Architecture:** `ActivityFeedService` consulta o `AuditLog` existente (ações próprias + ações de terceiros em registros atribuídos ao usuário). `NotificationPreferencesController` persiste os toggles do SettingsModal em `user_notification_preferences`. Fase 2 instala Laravel Reverb e adiciona `UserActivityEvent` broadcastado por observers nos módulos PAE, Demandas e Decretações.

**Tech Stack:** Laravel 12, Inertia.js v1, Vue 3, `app/Models/AuditLog.php`, `App\Modules\Pae\Models\PaeProtocolo` (`analista_atual_id`), `App\Modules\Demandas\Models\Task` (`atribuido_para_id`), `window.axios` (já configurado com CSRF + withCredentials), Laravel Reverb, laravel-echo, pusher-js.

---

## File Map

| Ação | Arquivo | Responsabilidade |
|------|---------|-----------------|
| Criar | `database/migrations/2026_04_14_000001_create_user_notification_preferences_table.php` | tabela de preferências por módulo |
| Criar | `database/migrations/2026_04_14_000002_add_notification_update_mode_to_users_table.php` | coluna update_mode em users |
| Criar | `app/Models/UserNotificationPreference.php` | model das preferências |
| Criar | `app/Http/Controllers/NotificationPreferencesController.php` | GET/PUT preferências |
| Criar | `app/Modules/Dashboard/Services/ActivityFeedService.php` | query + formatação do feed |
| Criar | `app/Http/Controllers/ActivityFeedController.php` | endpoint GET /api/v1/activity-feed |
| Criar | `resources/js/composables/useActivityFeed.js` | polling + realtime branch |
| Modificar | `resources/js/Components/Dashboard/Widgets/TimelineWidget.vue` | substituir mock por composable |
| Modificar | `resources/js/Components/Organisms/Settings/SettingsModal.vue` | load/save API + update_mode radio |
| Modificar | `routes/api.php` | adicionar 3 rotas |
| Criar | `app/Events/UserActivityEvent.php` | broadcast ao canal user.{id} |
| Modificar | `resources/js/bootstrap.js` | configurar Echo para Reverb |
| Modificar | `docker/docker-compose.yml` | serviço reverb |
| Criar | `app/Modules/Pae/Observers/PaeProtocoloObserver.php` | dispara UserActivityEvent no updated |
| Criar | `app/Modules/Demandas/Observers/TaskObserver.php` | dispara UserActivityEvent no updated |
| Modificar | `app/Modules/Pae/PaeServiceProvider.php` (ou ServiceProvider equivalente) | registrar PaeProtocoloObserver |
| Modificar | `app/Modules/Demandas/DemandasServiceProvider.php` | registrar TaskObserver |

---

## Task 1: Migrations

**Files:**
- Criar: `database/migrations/2026_04_14_000001_create_user_notification_preferences_table.php`
- Criar: `database/migrations/2026_04_14_000002_add_notification_update_mode_to_users_table.php`

- [ ] **Step 1.1: Criar migration user_notification_preferences**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('module', ['rat', 'pae', 'meteorologia', 'demandas', 'decretacoes']);
            $table->boolean('canal_sistema')->default(true);
            $table->boolean('canal_email')->default(false);
            $table->boolean('canal_push')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
```

- [ ] **Step 1.2: Criar migration update_mode em users**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('notification_update_mode', ['polling', 'realtime'])
                  ->default('polling')
                  ->after('updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_update_mode');
        });
    }
};
```

- [ ] **Step 1.3: Rodar as migrations**

```bash
cd "c:/Users/x24679188/Documents/Github/NewSDC/SDC"
# Via Docker:
make migrate
# Ou localmente (se o DB estiver acessível):
"c:/Users/x24679188/Documents/Github/NewSDC/php.exe" artisan migrate
```

Esperado: `Migrating: 2026_04_14_000001_create_user_notification_preferences_table` e `2026_04_14_000002_add_notification_update_mode_to_users_table` — ambos `Migrated`.

- [ ] **Step 1.4: Commit**

```bash
git add database/migrations/2026_04_14_000001_* database/migrations/2026_04_14_000002_*
git commit -m "feat(activity-feed): migrations user_notification_preferences + update_mode"
```

---

## Task 2: UserNotificationPreference Model

**Files:**
- Criar: `app/Models/UserNotificationPreference.php`

- [ ] **Step 2.1: Criar o model**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'module',
        'canal_sistema',
        'canal_email',
        'canal_push',
    ];

    protected $casts = [
        'canal_sistema' => 'boolean',
        'canal_email'   => 'boolean',
        'canal_push'    => 'boolean',
    ];

    public const MODULES = ['rat', 'pae', 'meteorologia', 'demandas', 'decretacoes'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retorna as preferencias do usuario, criando defaults se nao existirem.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function forUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        $existing = static::where('user_id', $userId)->get()->keyBy('module');

        foreach (self::MODULES as $module) {
            if (!$existing->has($module)) {
                $existing->put($module, static::create([
                    'user_id'       => $userId,
                    'module'        => $module,
                    'canal_sistema' => true,
                    'canal_email'   => false,
                    'canal_push'    => false,
                ]));
            }
        }

        return static::where('user_id', $userId)->get();
    }
}
```

- [ ] **Step 2.2: Adicionar `notification_update_mode` ao fillable do User**

Modificar `app/Models/User.php` — adicionar `'notification_update_mode'` no array `$fillable`:

```php
protected $fillable = [
    'name',
    'email',
    'cpf',
    'active',
    'status',
    'password',
    'orgao_principal_id',
    'last_login_at',
    'last_login_ip',
    'user_agent',
    'created_by',
    'updated_by',
    'notification_update_mode',  // ADICIONAR esta linha
];
```

- [ ] **Step 2.3: Validar sintaxe**

```bash
"c:/Users/x24679188/Documents/Github/NewSDC/php.exe" -l app/Models/UserNotificationPreference.php
"c:/Users/x24679188/Documents/Github/NewSDC/php.exe" -l app/Models/User.php
```

Esperado: `No syntax errors detected` em ambos.

- [ ] **Step 2.4: Commit**

```bash
git add app/Models/UserNotificationPreference.php app/Models/User.php
git commit -m "feat(activity-feed): UserNotificationPreference model + User.fillable"
```

---

## Task 3: NotificationPreferencesController + Rotas

**Files:**
- Criar: `app/Http/Controllers/NotificationPreferencesController.php`
- Modificar: `routes/api.php`

- [ ] **Step 3.1: Criar o controller**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserNotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferencesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $prefs = UserNotificationPreference::forUser($request->user()->id);

        return response()->json([
            'modules'     => $prefs->map(fn ($p) => [
                'module'        => $p->module,
                'canal_sistema' => $p->canal_sistema,
                'canal_email'   => $p->canal_email,
                'canal_push'    => $p->canal_push,
            ])->values(),
            'update_mode' => $request->user()->notification_update_mode ?? 'polling',
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'modules'              => 'required|array',
            'modules.*.module'     => 'required|string|in:rat,pae,meteorologia,demandas,decretacoes',
            'modules.*.canal_sistema' => 'required|boolean',
            'modules.*.canal_email'   => 'required|boolean',
            'modules.*.canal_push'    => 'required|boolean',
            'update_mode'          => 'sometimes|string|in:polling,realtime',
        ]);

        $userId = $request->user()->id;

        foreach ($validated['modules'] as $mod) {
            UserNotificationPreference::updateOrCreate(
                ['user_id' => $userId, 'module' => $mod['module']],
                [
                    'canal_sistema' => $mod['canal_sistema'],
                    'canal_email'   => $mod['canal_email'],
                    'canal_push'    => $mod['canal_push'],
                ]
            );
        }

        if (isset($validated['update_mode'])) {
            $request->user()->update(['notification_update_mode' => $validated['update_mode']]);
        }

        return response()->json(['message' => 'Preferencias salvas com sucesso.']);
    }
}
```

- [ ] **Step 3.2: Adicionar rotas em `routes/api.php`**

Dentro do bloco `Route::prefix('v1')->middleware('auth:sanctum')->group(function () {`, adicionar após as rotas PAE existentes (linha ~88):

```php
// Dashboard — Feed de Atividades
Route::get('activity-feed', [\App\Http\Controllers\ActivityFeedController::class, 'index'])
    ->name('api.v1.activity-feed.index');

// Dashboard — Preferencias de Notificacao
Route::get('notification-preferences',  [\App\Http\Controllers\NotificationPreferencesController::class, 'index'])
    ->name('api.v1.notification-preferences.index');
Route::put('notification-preferences',  [\App\Http\Controllers\NotificationPreferencesController::class, 'update'])
    ->name('api.v1.notification-preferences.update');
```

- [ ] **Step 3.3: Validar sintaxe**

```bash
"c:/Users/x24679188/Documents/Github/NewSDC/php.exe" -l app/Http/Controllers/NotificationPreferencesController.php
"c:/Users/x24679188/Documents/Github/NewSDC/php.exe" artisan route:list --name=notification-preferences 2>&1 | head -10
```

Esperado: sem erros de sintaxe, e as 2 rotas aparecem na listagem.

- [ ] **Step 3.4: Commit**

```bash
git add app/Http/Controllers/NotificationPreferencesController.php routes/api.php
git commit -m "feat(activity-feed): NotificationPreferencesController + rotas GET/PUT"
```

---

## Task 4: ActivityFeedService

**Files:**
- Criar: `app/Modules/Dashboard/Services/ActivityFeedService.php`

- [ ] **Step 4.1: Criar o service**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Models\AuditLog;
use App\Models\UserNotificationPreference;
use App\Modules\Demandas\Models\Task;
use App\Modules\Pae\Models\PaeProtocolo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ActivityFeedService
{
    private const TABLE_MODULE = [
        'rat_ocorrencias'       => 'rat',
        'pae_protocolos'        => 'pae',
        'tasks'                 => 'demandas',
        'dec_entrada_processos' => 'decretacoes',
    ];

    private const MODULE_LABEL = [
        'rat'        => 'RAT',
        'pae'        => 'PAE',
        'demandas'   => 'Demanda',
        'decretacoes'=> 'Decretacao',
    ];

    public function getFeed(int $userId, int $limit = 7): array
    {
        $enabledModules = UserNotificationPreference::where('user_id', $userId)
            ->where('canal_sistema', true)
            ->pluck('module')
            ->toArray();

        // Se nao houver preferencias salvas ainda, mostrar tudo
        if (empty($enabledModules)) {
            $enabledModules = array_values(self::TABLE_MODULE);
        }

        $enabledTables = array_keys(array_filter(
            self::TABLE_MODULE,
            fn ($module) => in_array($module, $enabledModules, true)
        ));

        $ownActions = $this->queryOwnActions($userId, $enabledTables);
        $assignedActions = $this->queryAssignedActions($userId, $enabledTables);

        return $ownActions
            ->merge($assignedActions)
            ->unique('id')
            ->sortByDesc('created_at')
            ->take($limit)
            ->map(fn (AuditLog $log) => $this->formatItem($log))
            ->values()
            ->toArray();
    }

    private function queryOwnActions(int $userId, array $tables): Collection
    {
        if (empty($tables)) return collect();

        return AuditLog::where('user_id', $userId)
            ->whereIn('table_name', $tables)
            ->whereNotIn('event', [AuditLog::EVENT_LOGIN, AuditLog::EVENT_LOGOUT])
            ->latest('created_at')
            ->limit(20)
            ->get();
    }

    private function queryAssignedActions(int $userId, array $tables): Collection
    {
        $result = collect();

        // PAE atribuido ao usuario — movimentacoes de outros
        if (in_array('pae_protocolos', $tables, true)) {
            $paeIds = PaeProtocolo::where('analista_atual_id', $userId)->pluck('id');

            if ($paeIds->isNotEmpty()) {
                $logs = AuditLog::where('table_name', 'pae_protocolos')
                    ->whereIn('row_id', $paeIds)
                    ->where('user_id', '!=', $userId)
                    ->whereNotIn('event', [AuditLog::EVENT_LOGIN, AuditLog::EVENT_LOGOUT])
                    ->latest('created_at')
                    ->limit(10)
                    ->get();

                $result = $result->merge($logs);
            }
        }

        // Tasks atribuidas ao usuario — movimentacoes de outros
        if (in_array('tasks', $tables, true)) {
            $taskIds = Task::where('atribuido_para_id', $userId)->pluck('id');

            if ($taskIds->isNotEmpty()) {
                $logs = AuditLog::where('table_name', 'tasks')
                    ->whereIn('row_id', $taskIds)
                    ->where('user_id', '!=', $userId)
                    ->whereNotIn('event', [AuditLog::EVENT_LOGIN, AuditLog::EVENT_LOGOUT])
                    ->latest('created_at')
                    ->limit(10)
                    ->get();

                $result = $result->merge($logs);
            }
        }

        return $result;
    }

    private function formatItem(AuditLog $log): array
    {
        $module = self::TABLE_MODULE[$log->table_name] ?? 'sistema';
        $label  = self::MODULE_LABEL[$module] ?? 'Sistema';

        return [
            'id'        => $log->id,
            'type'      => $this->resolveType($log),
            'municipio' => $label,
            'acao'      => $this->resolveAcao($log, $label),
            'data'      => $this->tempoRelativo($log->created_at),
            'protocolo' => $this->resolveReferencia($log),
        ];
    }

    private function resolveType(AuditLog $log): string
    {
        if ($log->event === AuditLog::EVENT_INSERT) {
            return 'new_process';
        }

        if ($log->table_name === 'rat_ocorrencias') {
            return 'alert';
        }

        $novos = $log->new_values ?? [];

        if (
            isset($novos['reconhecimento']) &&
            str_starts_with((string) $novos['reconhecimento'], 'Reconhecido')
        ) {
            return 'approval';
        }

        if (isset($novos['status']) && in_array($novos['status'], ['aprovado', 'concluida'], true)) {
            return 'approval';
        }

        return 'analysis';
    }

    private function resolveAcao(AuditLog $log, string $label): string
    {
        $verbs = [
            AuditLog::EVENT_INSERT => 'criado',
            AuditLog::EVENT_UPDATE => 'atualizado',
            AuditLog::EVENT_DELETE => 'removido',
        ];

        $verb = $verbs[$log->event] ?? 'modificado';

        $novos = $log->new_values ?? [];
        $titulo = $novos['titulo'] ?? $novos['num_protocolo'] ?? $novos['n_protocolo_fide'] ?? null;

        if ($titulo) {
            return "{$label} {$verb}: {$titulo}";
        }

        return "{$label} #{$log->row_id} {$verb}";
    }

    private function resolveReferencia(AuditLog $log): string
    {
        $novos = $log->new_values ?? [];

        return $novos['num_protocolo']
            ?? $novos['n_protocolo_fide']
            ?? $novos['protocolo']
            ?? $novos['titulo']
            ?? ($log->table_name . ' #' . $log->row_id);
    }

    private function tempoRelativo(Carbon $dt): string
    {
        $diff = (int) now()->diffInMinutes($dt);

        if ($diff < 60) {
            return "{$diff} min";
        }

        $horas   = (int) floor($diff / 60);
        $minutos = $diff % 60;

        if ($horas < 24) {
            return $minutos > 0 ? "{$horas}h {$minutos}m" : "{$horas}h";
        }

        $dias = (int) floor($horas / 24);
        return "{$dias}d";
    }
}
```

- [ ] **Step 4.2: Validar sintaxe**

```bash
"c:/Users/x24679188/Documents/Github/NewSDC/php.exe" -l app/Modules/Dashboard/Services/ActivityFeedService.php
```

Esperado: `No syntax errors detected`.

- [ ] **Step 4.3: Commit**

```bash
git add app/Modules/Dashboard/Services/ActivityFeedService.php
git commit -m "feat(activity-feed): ActivityFeedService com ownActions + assignedActions"
```

---

## Task 5: ActivityFeedController

**Files:**
- Criar: `app/Http/Controllers/ActivityFeedController.php`
- (Rota já adicionada na Task 3)

- [ ] **Step 5.1: Criar o controller**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Dashboard\Services\ActivityFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityFeedController extends Controller
{
    public function __construct(
        private readonly ActivityFeedService $feedService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->feedService->getFeed(
            userId: $request->user()->id,
            limit: 7,
        );

        return response()->json([
            'items'       => $items,
            'update_mode' => $request->user()->notification_update_mode ?? 'polling',
        ]);
    }
}
```

- [ ] **Step 5.2: Validar sintaxe**

```bash
"c:/Users/x24679188/Documents/Github/NewSDC/php.exe" -l app/Http/Controllers/ActivityFeedController.php
```

Esperado: `No syntax errors detected`.

- [ ] **Step 5.3: Testar o endpoint manualmente**

Com a app rodando, faça login e acesse:
```
GET /api/v1/activity-feed
```
Esperado: JSON `{ "items": [...], "update_mode": "polling" }`. Se `items` vier vazio, significa que o AuditLog ainda não tem registros para o usuário logado — isso é correto.

- [ ] **Step 5.4: Commit**

```bash
git add app/Http/Controllers/ActivityFeedController.php
git commit -m "feat(activity-feed): ActivityFeedController endpoint GET /api/v1/activity-feed"
```

---

## Task 6: useActivityFeed composable + TimelineWidget (Polling)

**Files:**
- Criar: `resources/js/composables/useActivityFeed.js`
- Modificar: `resources/js/Components/Dashboard/Widgets/TimelineWidget.vue`

- [ ] **Step 6.1: Criar o composable `useActivityFeed.js`**

```js
import { ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useActivityFeed() {
    const items = ref([]);
    const isLoading = ref(false);
    const updateMode = ref('polling');
    let pollInterval = null;
    let echoChannel = null;

    const page = usePage();

    async function fetchFeed() {
        isLoading.value = true;
        try {
            const response = await window.axios.get('/api/v1/activity-feed');
            items.value = response.data.items;
            updateMode.value = response.data.update_mode;
        } catch (e) {
            // silencioso — nao polui a UI com erros de rede
        } finally {
            isLoading.value = false;
        }
    }

    function startPolling() {
        fetchFeed();
        pollInterval = setInterval(fetchFeed, 60_000);
    }

    function stopPolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }

    function startRealtime(userId) {
        if (!window.Echo) return;

        echoChannel = window.Echo.private(`user.${userId}`)
            .listen('UserActivityEvent', (event) => {
                if (event.item) {
                    items.value = [event.item, ...items.value].slice(0, 7);
                }
            });
    }

    function stopRealtime() {
        if (echoChannel) {
            echoChannel.stopListening('UserActivityEvent');
            echoChannel = null;
        }
    }

    onMounted(async () => {
        await fetchFeed();

        const userId = page.props?.auth?.user?.id;
        const mode = page.props?.auth?.user?.notification_update_mode ?? 'polling';
        updateMode.value = mode;

        if (mode === 'realtime' && userId && window.Echo) {
            startRealtime(userId);
        } else {
            pollInterval = setInterval(fetchFeed, 60_000);
        }
    });

    onUnmounted(() => {
        stopPolling();
        stopRealtime();
    });

    return { items, isLoading, updateMode, refresh: fetchFeed };
}
```

- [ ] **Step 6.2: Substituir mock em `TimelineWidget.vue`**

Substituir o bloco `<script setup>` inteiro do `TimelineWidget.vue` por:

```html
<script setup>
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import { useActivityFeed } from '@/composables/useActivityFeed';

const { items, isLoading } = useActivityFeed();

function timelineIcon(type) {
    const map = {
        approval:    CheckCircleIcon,
        alert:       ExclamationTriangleIcon,
        new_process: DocumentTextIcon,
        analysis:    ClockIcon,
    };
    return map[type] || DocumentTextIcon;
}

function timelineBgColor(type) {
    const map = {
        approval:    'bg-emerald-500 shadow-emerald-500/40',
        alert:       'bg-amber-500 shadow-amber-500/40',
        new_process: 'bg-blue-500 shadow-blue-500/40',
        analysis:    'bg-violet-500 shadow-violet-500/40',
    };
    return map[type] || 'bg-slate-500';
}
</script>
```

No `<template>`, substituir `v-for="(h, index) in historico"` por `v-for="(h, index) in items"`.

Adicionar loading state ANTES do `<TransitionGroup>`:

```html
<!-- Loading skeleton -->
<div v-if="isLoading && items.length === 0" class="space-y-6 relative z-10">
    <div v-for="i in 3" :key="i" class="flex gap-4 animate-pulse">
        <div class="w-8 h-8 rounded-xl bg-slate-200 dark:bg-slate-700 flex-shrink-0 mt-1"></div>
        <div class="flex-1 bg-slate-100 dark:bg-slate-800 rounded-xl p-3 h-16"></div>
    </div>
</div>

<!-- Empty state -->
<div v-else-if="!isLoading && items.length === 0" class="flex flex-col items-center justify-center py-12 text-slate-400">
    <DocumentTextIcon class="w-10 h-10 mb-3 opacity-40" />
    <p class="text-sm">Nenhuma movimentacao recente</p>
</div>
```

Alterar o `<TransitionGroup>` para `v-else` (só renderiza quando há itens):

```html
<TransitionGroup v-else name="list" tag="div" class="space-y-6 relative z-10">
```

- [ ] **Step 6.3: Build e verificar**

```bash
cd "c:/Users/x24679188/Documents/Github/NewSDC/SDC"
npm run build 2>&1 | tail -5
```

Esperado: `built in XX.XXs` sem erros.

- [ ] **Step 6.4: Commit**

```bash
git add resources/js/composables/useActivityFeed.js resources/js/Components/Dashboard/Widgets/TimelineWidget.vue
git commit -m "feat(activity-feed): useActivityFeed composable + TimelineWidget polling"
```

---

## Task 7: SettingsModal — Persistência das Preferências

**Files:**
- Modificar: `resources/js/Components/Organisms/Settings/SettingsModal.vue`

- [ ] **Step 7.1: Carregar preferências no onMounted**

No `<script setup>` do `SettingsModal.vue`, adicionar após `const isSaving = ref(false);`:

```js
// Estado de modo de atualizacao
const updateMode = ref('polling');

// Carregar preferencias do servidor ao abrir
async function loadPreferences() {
    try {
        const res = await window.axios.get('/api/v1/notification-preferences');
        updateMode.value = res.data.update_mode ?? 'polling';

        const serverModules = res.data.modules ?? [];
        serverModules.forEach(serverMod => {
            const local = notificationModules.value.find(m => m.id === serverMod.module);
            if (local) {
                local.channels.bell  = serverMod.canal_sistema;
                local.channels.email = serverMod.canal_email;
                local.channels.push  = serverMod.canal_push;
            }
        });
    } catch (e) {
        // silencioso — mantém defaults
    }
}
```

No `onMounted`, adicionar chamada:

```js
onMounted(() => {
    document.addEventListener('keydown', closeOnEscape);
    loadPreferences();  // ADICIONAR esta linha
});
```

- [ ] **Step 7.2: Atualizar IDs dos módulos para alinhar com o DB**

No `notificationModules` ref, alterar os IDs para corresponder ao enum da migration:
- `'rat'` → já OK
- `'pae'` → já OK
- `'meteo'` → alterar para `'meteorologia'`
- `'demanda'` → alterar para `'demandas'`

```js
const notificationModules = ref([
    { id: 'rat',         name: 'Relatorios (RAT)',     description: 'Alertas sobre novos relatorios, vistorias e aprovacoes.',    icon: 'DocumentTextIcon', channels: { bell: true,  email: true,  push: false } },
    { id: 'pae',         name: 'Planos (PAE)',          description: 'Vencimentos de prazos e atualizacoes de status.',            icon: 'MapIcon',          channels: { bell: true,  email: false, push: true  } },
    { id: 'meteorologia',name: 'Meteorologia',          description: 'Alertas criticos de chuva e mudancas climaticas (INMET).',   icon: 'CloudIcon',        channels: { bell: true,  email: true,  push: true  } },
    { id: 'demandas',    name: 'Demandas/Chamados',     description: 'Atribuicoes de tarefas e novos comentarios.',                icon: 'CheckBadgeIcon',   channels: { bell: true,  email: false, push: false } },
    { id: 'decretacoes', name: 'Decretacoes',           description: 'Movimentacoes em decretos e reconhecimentos.',               icon: 'DocumentTextIcon', channels: { bell: true,  email: false, push: false } },
]);
```

- [ ] **Step 7.3: Adicionar seção "Modo de Atualização" no tab Notificações**

No template, dentro de `<div v-if="currentTab === 'notifications'" class="space-y-8">`, adicionar ANTES do `v-for` de módulos:

```html
<!-- Modo de Atualizacao do Feed -->
<div class="p-4 border border-slate-200 dark:border-slate-700 rounded-xl">
    <h4 class="font-bold text-slate-900 dark:text-white mb-1">Modo de Atualizacao</h4>
    <p class="text-xs text-slate-500 mb-4">Como o bloco "Ultimas Movimentacoes" do dashboard se atualiza.</p>
    <div class="flex gap-4">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" v-model="updateMode" value="polling" class="text-blue-600 focus:ring-blue-500">
            <div>
                <span class="text-sm font-medium text-slate-900 dark:text-white">Polling (60s)</span>
                <p class="text-xs text-slate-500">Consulta automatica a cada 60 segundos</p>
            </div>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" v-model="updateMode" value="realtime" class="text-blue-600 focus:ring-blue-500">
            <div>
                <span class="text-sm font-medium text-slate-900 dark:text-white">Tempo Real</span>
                <p class="text-xs text-slate-500">Atualizacoes instantaneas via WebSocket</p>
            </div>
        </label>
    </div>
</div>
```

- [ ] **Step 7.4: Substituir a função `save()` para chamar a API**

Substituir a função `save` existente (linha ~523):

```js
const save = async () => {
    isSaving.value = true;
    try {
        await window.axios.put('/api/v1/notification-preferences', {
            modules: notificationModules.value.map(m => ({
                module:        m.id,
                canal_sistema: m.channels.bell,
                canal_email:   m.channels.email,
                canal_push:    m.channels.push,
            })),
            update_mode: updateMode.value,
        });
        emit('close');
    } catch (e) {
        // manter modal aberto se falhar
    } finally {
        isSaving.value = false;
    }
};
```

- [ ] **Step 7.5: Build e testar**

```bash
npm run build 2>&1 | tail -5
```

Esperado: build sem erros.

Testar manualmente: abrir Configurações → Notificações → mudar um toggle → clicar "Salvar Alterações" → reabrir Settings → toggle deve manter o estado salvo.

- [ ] **Step 7.6: Commit**

```bash
git add resources/js/Components/Organisms/Settings/SettingsModal.vue
git commit -m "feat(activity-feed): SettingsModal persiste preferencias + toggle update_mode"
```

---

## Task 8: Instalar Laravel Reverb + Configurar Docker + Evento

**Files:**
- Modificar: `docker/docker-compose.yml`
- Criar: `app/Events/UserActivityEvent.php`
- Modificar: `routes/channels.php`
- Modificar: `resources/js/bootstrap.js`

- [ ] **Step 8.1: Instalar Reverb via Composer**

```bash
cd "c:/Users/x24679188/Documents/Github/NewSDC/SDC"
composer require laravel/reverb
"c:/Users/x24679188/Documents/Github/NewSDC/php.exe" artisan reverb:install
```

O `reverb:install` vai:
- Publicar config em `config/reverb.php`
- Adicionar variáveis no `.env`: `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`
- Alterar `BROADCAST_DRIVER=reverb`

- [ ] **Step 8.2: Instalar Echo + pusher-js no frontend**

```bash
npm install --save-dev laravel-echo pusher-js
```

- [ ] **Step 8.3: Configurar Echo no bootstrap.js**

No arquivo `resources/js/bootstrap.js`, adicionar ao final:

```js
// Echo/Reverb — configurado apenas se Reverb estiver habilitado
if (import.meta.env.VITE_REVERB_APP_KEY) {
    import('laravel-echo').then(({ default: Echo }) => {
        import('pusher-js').then(({ default: Pusher }) => {
            window.Pusher = Pusher;
            window.Echo = new Echo({
                broadcaster:     'reverb',
                key:             import.meta.env.VITE_REVERB_APP_KEY,
                wsHost:          import.meta.env.VITE_REVERB_HOST ?? 'localhost',
                wsPort:          import.meta.env.VITE_REVERB_PORT ?? 8080,
                wssPort:         import.meta.env.VITE_REVERB_PORT ?? 8080,
                forceTLS:        (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
                enabledTransports: ['ws', 'wss'],
            });
        });
    });
}
```

- [ ] **Step 8.4: Adicionar variáveis Reverb no .env**

Verificar se `reverb:install` adicionou as variáveis. Se não, adicionar manualmente ao `.env`:

```
BROADCAST_DRIVER=reverb
REVERB_APP_ID=sdc-app
REVERB_APP_KEY=sdc-reverb-key
REVERB_APP_SECRET=sdc-reverb-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

- [ ] **Step 8.5: Adicionar serviço Reverb ao docker-compose.yml**

No arquivo `docker/docker-compose.yml`, adicionar o serviço `reverb` dentro do bloco `services:`:

```yaml
  reverb:
    image: "${APP_IMAGE:-sdc-app}:${APP_TAG:-latest}"
    command: ["php", "artisan", "reverb:start", "--host=0.0.0.0", "--port=8080"]
    ports:
      - "8080:8080"
    env_file:
      - ../.env
    depends_on:
      - app
    restart: unless-stopped
    networks:
      - sdc-network
```

Se o `docker-compose.yml` já tiver um serviço `app` com `image:` diferente, use o mesmo nome de imagem.

- [ ] **Step 8.6: Configurar canal privado em routes/channels.php**

Verificar o arquivo `routes/channels.php`. Deve ter:

```php
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
```

Esse canal já existe (foi encontrado na varredura). Confirmar que está presente — nenhuma alteração necessária se já existe.

- [ ] **Step 8.7: Criar UserActivityEvent**

```php
<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserActivityEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $targetUserId,
        public readonly array  $item,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("user.{$this->targetUserId}");
    }

    public function broadcastAs(): string
    {
        return 'UserActivityEvent';
    }

    public function broadcastWith(): array
    {
        return ['item' => $this->item];
    }
}
```

- [ ] **Step 8.8: Validar sintaxe**

```bash
"c:/Users/x24679188/Documents/Github/NewSDC/php.exe" -l app/Events/UserActivityEvent.php
```

Esperado: `No syntax errors detected`.

- [ ] **Step 8.9: Commit**

```bash
git add composer.json composer.lock config/reverb.php app/Events/UserActivityEvent.php resources/js/bootstrap.js docker/docker-compose.yml
git commit -m "feat(activity-feed): instalar Reverb + UserActivityEvent + Echo config"
```

---

## Task 9: Observers nos Módulos + Realtime no Frontend

**Files:**
- Criar: `app/Modules/Pae/Observers/PaeProtocoloObserver.php`
- Criar: `app/Modules/Demandas/Observers/TaskObserver.php`
- Modificar: `app/Modules/Pae/PaeServiceProvider.php` (ou provider equivalente)
- Modificar: `app/Modules/Demandas/DemandasServiceProvider.php`

- [ ] **Step 9.1: Verificar se PaeServiceProvider existe**

```bash
find "c:/Users/x24679188/Documents/Github/NewSDC/SDC/app/Modules/Pae" -name "*ServiceProvider*" 2>/dev/null
```

Se não existir, os observers serão registrados no `AppServiceProvider` ou em um novo `PaeServiceProvider`. Se existir, use o arquivo encontrado.

- [ ] **Step 9.2: Criar PaeProtocoloObserver**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Pae\Observers;

use App\Events\UserActivityEvent;
use App\Modules\Dashboard\Services\ActivityFeedService;
use App\Modules\Pae\Models\PaeProtocolo;

class PaeProtocoloObserver
{
    public function __construct(
        private readonly ActivityFeedService $feedService,
    ) {}

    public function updated(PaeProtocolo $protocolo): void
    {
        $analistaId = $protocolo->analista_atual_id;

        if (!$analistaId) {
            return;
        }

        $item = [
            'id'        => $protocolo->id,
            'type'      => 'analysis',
            'municipio' => 'PAE',
            'acao'      => "PAE atualizado: {$protocolo->num_protocolo}",
            'data'      => '1 min',
            'protocolo' => $protocolo->num_protocolo ?? "PAE #{$protocolo->id}",
        ];

        UserActivityEvent::dispatch($analistaId, $item);
    }

    public function created(PaeProtocolo $protocolo): void
    {
        $analistaId = $protocolo->analista_atual_id;

        if (!$analistaId) {
            return;
        }

        $item = [
            'id'        => $protocolo->id,
            'type'      => 'new_process',
            'municipio' => 'PAE',
            'acao'      => "Novo PAE criado: {$protocolo->num_protocolo}",
            'data'      => '1 min',
            'protocolo' => $protocolo->num_protocolo ?? "PAE #{$protocolo->id}",
        ];

        UserActivityEvent::dispatch($analistaId, $item);
    }
}
```

- [ ] **Step 9.3: Criar TaskObserver**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Observers;

use App\Events\UserActivityEvent;
use App\Modules\Demandas\Models\Task;

class TaskObserver
{
    public function updated(Task $task): void
    {
        $responsavelId = $task->atribuido_para_id;

        if (!$responsavelId) {
            return;
        }

        $item = [
            'id'        => $task->id,
            'type'      => 'analysis',
            'municipio' => 'Demanda',
            'acao'      => "Demanda atualizada: {$task->titulo}",
            'data'      => '1 min',
            'protocolo' => $task->protocolo ?? "Task #{$task->id}",
        ];

        UserActivityEvent::dispatch($responsavelId, $item);
    }

    public function created(Task $task): void
    {
        $responsavelId = $task->atribuido_para_id;

        if (!$responsavelId) {
            return;
        }

        $item = [
            'id'        => $task->id,
            'type'      => 'new_process',
            'municipio' => 'Demanda',
            'acao'      => "Nova demanda atribuida: {$task->titulo}",
            'data'      => '1 min',
            'protocolo' => $task->protocolo ?? "Task #{$task->id}",
        ];

        UserActivityEvent::dispatch($responsavelId, $item);
    }
}
```

- [ ] **Step 9.4: Registrar observers no DemandasServiceProvider**

Abrir `app/Modules/Demandas/DemandasServiceProvider.php`. No método `boot()`, adicionar:

```php
use App\Modules\Demandas\Models\Task;
use App\Modules\Demandas\Observers\TaskObserver;

// ... dentro de boot():
Task::observe(TaskObserver::class);
```

- [ ] **Step 9.5: Registrar PaeProtocoloObserver**

Se `app/Modules/Pae/PaeServiceProvider.php` existir, adicionar em `boot()`:

```php
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Observers\PaeProtocoloObserver;

// ... dentro de boot():
PaeProtocolo::observe(PaeProtocoloObserver::class);
```

Se NÃO existir PaeServiceProvider, adicionar em `app/Providers/AppServiceProvider.php` no `boot()`:

```php
\App\Modules\Pae\Models\PaeProtocolo::observe(\App\Modules\Pae\Observers\PaeProtocoloObserver::class);
```

- [ ] **Step 9.6: Validar sintaxe dos observers**

```bash
"c:/Users/x24679188/Documents/Github/NewSDC/php.exe" -l app/Modules/Pae/Observers/PaeProtocoloObserver.php
"c:/Users/x24679188/Documents/Github/NewSDC/php.exe" -l app/Modules/Demandas/Observers/TaskObserver.php
```

Esperado: `No syntax errors detected` em ambos.

- [ ] **Step 9.7: Build final + verificar**

```bash
npm run build 2>&1 | tail -5
```

Esperado: built sem erros.

Para testar o realtime:
1. Nas Configurações, selecionar "Tempo Real" e salvar
2. Em outra aba, criar/atualizar um PAE atribuído ao usuário logado
3. O TimelineWidget deve atualizar instantaneamente sem recarregar a página

- [ ] **Step 9.8: Commit final**

```bash
git add app/Modules/Pae/Observers/PaeProtocoloObserver.php app/Modules/Demandas/Observers/TaskObserver.php app/Modules/Demandas/DemandasServiceProvider.php
git commit -m "feat(activity-feed): observers PAE+Demandas disparam UserActivityEvent (realtime)"
```

---

## Self-Review

**Cobertura do spec:**
- [x] `user_notification_preferences` migration — Task 1
- [x] `notification_update_mode` em users — Task 1
- [x] `UserNotificationPreference` model com `forUser()` — Task 2
- [x] GET/PUT `/api/v1/notification-preferences` — Task 3
- [x] `ActivityFeedService` com ownActions + assignedActions (PAE analista, Task atribuido) — Task 4
- [x] GET `/api/v1/activity-feed` — Task 5
- [x] `useActivityFeed` composable — Task 6
- [x] `TimelineWidget` conectado ao composable, loading + empty state — Task 6
- [x] `SettingsModal` carrega preferências no mount + salva na API — Task 7
- [x] Toggle update_mode Polling/Realtime no SettingsModal — Task 7
- [x] Reverb instalado + `UserActivityEvent` — Task 8
- [x] Echo configurado no bootstrap.js — Task 8
- [x] Observer PAE + Task disparam broadcast — Task 9

**Tipos consistentes:** `ActivityFeedService::getFeed()` retorna `array` de items com campos `{id, type, municipio, acao, data, protocolo}` — exatamente os campos que o template do `TimelineWidget` espera (`h.municipio`, `h.acao`, `h.data`, `h.protocolo`, `h.type`).

**Placeholders:** Nenhum.
